<?php

namespace App\Console\Commands;

use App\Models\CcCliente;
use App\Models\CcProveedor;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Venta;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Saldos de cuenta corriente de clientes y proveedores al corte, desde la BD Delphi.
 *
 *   php artisan centrogoma:importar-saldos [--corte=YYYY-MM-DD] [--dry-run] [--solo=clientes,proveedores]
 *
 * Requiere haber importado los maestros (`centrogoma:importar clientes proveedores`).
 *
 * SALDO: el mismo que muestra el Delphi (qSaldoCliente / qSaldoProveedor):
 * `SUM(egreso) − SUM(ingreso)` de `cccliente` / `ccproveedor` hasta el corte, sin mirar
 * `estado` (es una marca manual de "pagada") ni la columna `saldo` (saldo corrido que la
 * pantalla reescribe). Positivo = el cliente nos debe / le debemos al proveedor.
 *
 * CARGA: cada deuda es un COMPROBANTE DE SALDO INICIAL (venta/compra con saldo_inicial = 1,
 * sin renglones, pendiente) que se cobra con recibo o se paga con orden de pago, + su
 * asiento en la CC + el saldo de la ficha. Los saldos A FAVOR no pueden ser un comprobante
 * (sería negativo): se cargan solo como asiento en la CC (haber) y se listan para revisar.
 *
 * REPETIBLE: pensado para correrse de nuevo el día de la salida a producción contra la BD
 * legacy actualizada. Cada corrida deshace lo que cargó la anterior y lo vuelve a cargar,
 * salvo el saldo de una cuenta que ya se cobró/pagó (o anuló) en la app, que se deja como
 * está y se avisa.
 */
class ImportarSaldosLegacy extends Command
{
    protected $signature = 'centrogoma:importar-saldos
        {--corte= : Fecha de corte YYYY-MM-DD, inclusive (default: hoy)}
        {--dry-run : Calcula y muestra todo, sin guardar}
        {--solo= : clientes,proveedores (default: ambos)}
        {--epsilon=1 : Saldos menores a este importe (en valor absoluto) se toman como cero}';

    protected $description = 'Carga los saldos de cuenta corriente al corte desde la BD legacy Delphi';

    private const PASOS = ['clientes', 'proveedores'];

    /** Cuentas de mostrador del legacy (CONSUMIDOR FINAL): cobran sin cuenta corriente real. */
    private const CUENTAS_MOSTRADOR = [1];

    private const CONCEPTO = 'Saldo inicial al ';

    private Carbon $corte;
    private float $epsilon;
    private array $stats = [];
    private array $aRevisar = [];

    public function handle(): int
    {
        $this->corte   = Carbon::parse($this->option('corte') ?: now()->toDateString())->startOfDay();
        $this->epsilon = (float) $this->option('epsilon');
        $solo = $this->option('solo') ? array_map('trim', explode(',', $this->option('solo'))) : self::PASOS;

        if ($desconocidos = array_diff($solo, self::PASOS)) {
            $this->error('Pasos desconocidos: ' . implode(', ', $desconocidos));
            return self::FAILURE;
        }

        $this->info('Corte: ' . $this->corte->format('d/m/Y') . ' inclusive' . ($this->option('dry-run') ? '  [DRY-RUN]' : ''));

        DB::beginTransaction();
        try {
            if (in_array('clientes', $solo, true)) {
                $this->info('── clientes');
                $this->cargarClientes();
            }
            if (in_array('proveedores', $solo, true)) {
                $this->info('── proveedores');
                $this->cargarProveedores();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            DB::rollBack();
            $this->warn('DRY-RUN: no se guardó nada.');
        } else {
            DB::commit();
        }

        $this->newLine();
        $this->table(['Concepto', 'Valor'], collect($this->stats)
            ->map(fn ($v, $k) => [$k, is_float($v) ? number_format($v, 2, ',', '.') : $v])->values()->all());

        if ($this->aRevisar) {
            $this->newLine();
            $this->warn('Para revisar con administración:');
            $this->table(['Motivo', 'Id legacy', 'Nombre', 'Importe'], $this->aRevisar);
        }

        return self::SUCCESS;
    }

    // ── Clientes ────────────────────────────────────────────────────

    private function cargarClientes(): void
    {
        $this->informarPosterioresAlCorte('cccliente', 'id_cliente', 'cliente');

        $saldos = $this->saldosLegacy('cccliente', 'id_cliente');
        $mapa   = Cliente::whereNotNull('legacy_id')->pluck('id', 'legacy_id');

        foreach ($saldos as $legacyId => $saldo) {
            $legacyId = (int) $legacyId;

            if (in_array($legacyId, self::CUENTAS_MOSTRADOR, true)) {
                if (abs($saldo) >= $this->epsilon) {
                    $this->aRevisar[] = ['cuenta de mostrador (no se carga)', $legacyId, $this->nombreLegacy('cliente', 'id_cliente', $legacyId), $this->pesos($saldo)];
                }
                continue;
            }

            $clienteId = $mapa[$legacyId] ?? null;
            if (! $clienteId) {
                if (abs($saldo) >= $this->epsilon) {
                    $this->sumar('clientes con saldo sin importar (correr centrogoma:importar clientes)', 1);
                    $this->aRevisar[] = ['cliente no importado', $legacyId, $this->nombreLegacy('cliente', 'id_cliente', $legacyId), $this->pesos($saldo)];
                }
                continue;
            }

            if (! $this->deshacerCliente($clienteId, $legacyId, $saldo)) {
                continue;
            }

            if (abs($saldo) < $this->epsilon) {
                continue;
            }

            $saldo > 0 ? $this->deudaCliente($clienteId, $saldo) : $this->aFavorCliente($clienteId, $legacyId, $saldo);
        }
    }

    private function deudaCliente(int $clienteId, float $saldo): void
    {
        $venta = Venta::create([
            'cliente_id'       => $clienteId,
            'nro_venta'        => (int) Venta::max('nro_venta') + 1,
            'fecha'            => $this->corte->toDateString(),
            'tipo_comprobante' => 'factura',
            'letra_comprobante' => null,
            'saldo_inicial'    => true,
            'neto'             => 0, 'neto_21' => 0, 'neto_10_5' => 0, 'monto_iva21' => 0, 'monto_iva10_5' => 0,
            'total'            => $saldo,
            'saldo'            => $saldo,
            'estado'           => 'P',
            'observacion'      => 'Saldo de cuenta corriente al ' . $this->corte->format('d/m/Y') . ' según el sistema anterior.',
        ]);

        $this->asientoCliente($clienteId, debe: $saldo, haber: 0, refType: Venta::class, refId: $venta->id);
        $this->sumar('clientes deudores cargados', 1);
        $this->sumar('total deuda de clientes', $saldo);
    }

    private function aFavorCliente(int $clienteId, int $legacyId, float $saldo): void
    {
        $this->asientoCliente($clienteId, debe: 0, haber: -$saldo, refType: Cliente::class, refId: $clienteId);
        $this->sumar('clientes con saldo a favor (solo asiento CC)', 1);
        $this->sumar('total a favor de clientes', $saldo);
        $this->aRevisar[] = ['cliente con saldo a favor', $legacyId, Cliente::find($clienteId)?->razon_social, $this->pesos($saldo)];
    }

    private function asientoCliente(int $clienteId, float $debe, float $haber, string $refType, int $refId): void
    {
        $cliente = Cliente::find($clienteId);
        $nuevo   = round((float) $cliente->saldo + $debe - $haber, 2);
        $cliente->update(['saldo' => $nuevo]);

        CcCliente::create([
            'cliente_id'      => $clienteId,
            'fecha'           => $this->corte->toDateString(),
            'concepto'        => self::CONCEPTO . $this->corte->format('d/m/Y'),
            'debe'            => $debe,
            'haber'           => $haber,
            'saldo'           => $nuevo,
            'referencia_type' => $refType,
            'referencia_id'   => $refId,
        ]);
    }

    /**
     * Deshace el saldo inicial de una corrida anterior. Devuelve false si ya se usó en la app
     * (cobrado con un recibo vigente, o anulado): se deja como está y se avisa.
     */
    private function deshacerCliente(int $clienteId, int $legacyId, float $saldoLegacy): bool
    {
        $ventas = Venta::withTrashed()->where('cliente_id', $clienteId)->where('saldo_inicial', true)->get();

        foreach ($ventas as $venta) {
            $cobrada = DB::table('recibo_detalles as rd')->join('recibos as r', 'r.id', '=', 'rd.recibo_id')
                ->where('rd.venta_id', $venta->id)->where('r.estado', '!=', 'A')->exists();

            if ($cobrada || $venta->estado === 'A' || $venta->trashed()) {
                $this->sumar('clientes con saldo inicial ya usado (no se recalcula)', 1);
                $this->aRevisar[] = ['saldo inicial ya cobrado/anulado', $legacyId, Cliente::find($clienteId)?->razon_social,
                    'legacy ' . $this->pesos($saldoLegacy) . ' / cargado ' . $this->pesos((float) $venta->total)];
                return false;
            }
        }

        foreach ($ventas as $venta) {
            $this->revertirAsientosCliente($clienteId, Venta::class, $venta->id);
            $venta->forceDelete();
            $this->sumar('saldos de clientes de una corrida anterior reemplazados', 1);
        }
        $this->revertirAsientosCliente($clienteId, Cliente::class, $clienteId);

        return true;
    }

    private function revertirAsientosCliente(int $clienteId, string $refType, int $refId): void
    {
        $asientos = CcCliente::where('cliente_id', $clienteId)->where('referencia_type', $refType)
            ->where('referencia_id', $refId)->where('concepto', 'like', self::CONCEPTO . '%')->get();

        if ($asientos->isEmpty()) {
            return;
        }

        $efecto  = $asientos->sum(fn ($a) => (float) $a->debe - (float) $a->haber);
        $cliente = Cliente::find($clienteId);
        $cliente->update(['saldo' => round((float) $cliente->saldo - $efecto, 2)]);
        CcCliente::whereIn('id', $asientos->pluck('id'))->delete();
    }

    // ── Proveedores ─────────────────────────────────────────────────

    private function cargarProveedores(): void
    {
        $this->informarPosterioresAlCorte('ccproveedor', 'id_proveedor', 'proveedor');

        $saldos = $this->saldosLegacy('ccproveedor', 'id_proveedor');
        $mapa   = Proveedor::whereNotNull('legacy_id')->pluck('id', 'legacy_id');

        foreach ($saldos as $legacyId => $saldo) {
            $legacyId    = (int) $legacyId;
            $proveedorId = $mapa[$legacyId] ?? null;

            if (! $proveedorId) {
                if (abs($saldo) >= $this->epsilon) {
                    $this->sumar('proveedores con saldo sin importar (correr centrogoma:importar proveedores)', 1);
                    $this->aRevisar[] = ['proveedor no importado', $legacyId, $this->nombreLegacy('proveedor', 'id_proveedor', $legacyId), $this->pesos($saldo)];
                }
                continue;
            }

            if (! $this->deshacerProveedor($proveedorId, $legacyId, $saldo)) {
                continue;
            }

            if (abs($saldo) < $this->epsilon) {
                continue;
            }

            $saldo > 0 ? $this->deudaProveedor($proveedorId, $saldo) : $this->aFavorProveedor($proveedorId, $legacyId, $saldo);
        }
    }

    private function deudaProveedor(int $proveedorId, float $saldo): void
    {
        $compra = Compra::create([
            'proveedor_id'  => $proveedorId,
            'fecha'         => $this->corte->toDateString(),
            'letra'         => 'X',
            'nro_factura'   => 'SALDO-INICIAL',
            'forma_pago'    => 'cuenta_corriente',
            'saldo_inicial' => true,
            'neto' => 0, 'neto_21' => 0, 'neto_10_5' => 0, 'monto_iva21' => 0, 'monto_iva10_5' => 0,
            'total'         => $saldo,
            'saldo'         => $saldo,
            'estado'        => 'P',
            'procesada'     => true,
            'procesada_at'  => now(),
            'observacion'   => 'Saldo de cuenta corriente al ' . $this->corte->format('d/m/Y') . ' según el sistema anterior.',
        ]);

        $this->asientoProveedor($proveedorId, debe: $saldo, haber: 0, refType: Compra::class, refId: $compra->id);
        $this->sumar('proveedores acreedores cargados', 1);
        $this->sumar('total deuda con proveedores', $saldo);
    }

    private function aFavorProveedor(int $proveedorId, int $legacyId, float $saldo): void
    {
        $this->asientoProveedor($proveedorId, debe: 0, haber: -$saldo, refType: Proveedor::class, refId: $proveedorId);
        $this->sumar('proveedores con saldo a favor nuestro (solo asiento CC)', 1);
        $this->sumar('total a favor nuestro con proveedores', $saldo);
        $this->aRevisar[] = ['proveedor con saldo a favor nuestro', $legacyId, Proveedor::find($proveedorId)?->razon_social, $this->pesos($saldo)];
    }

    private function asientoProveedor(int $proveedorId, float $debe, float $haber, string $refType, int $refId): void
    {
        $proveedor = Proveedor::find($proveedorId);
        $nuevo     = round((float) $proveedor->saldo + $debe - $haber, 2);
        $proveedor->update(['saldo' => $nuevo]);

        CcProveedor::create([
            'proveedor_id'    => $proveedorId,
            'fecha'           => $this->corte->toDateString(),
            'concepto'        => self::CONCEPTO . $this->corte->format('d/m/Y'),
            'debe'            => $debe,
            'haber'           => $haber,
            'saldo'           => $nuevo,
            'referencia_type' => $refType,
            'referencia_id'   => $refId,
        ]);
    }

    private function deshacerProveedor(int $proveedorId, int $legacyId, float $saldoLegacy): bool
    {
        $compras = Compra::withTrashed()->where('proveedor_id', $proveedorId)->where('saldo_inicial', true)->get();

        foreach ($compras as $compra) {
            $pagada = DB::table('detalle_ordenes_pago as d')->join('ordenes_pago as o', 'o.id', '=', 'd.orden_pago_id')
                ->where('d.compra_id', $compra->id)->where('o.estado', '!=', 'A')->exists();

            if ($pagada || $compra->estado === 'A' || $compra->trashed()) {
                $this->sumar('proveedores con saldo inicial ya usado (no se recalcula)', 1);
                $this->aRevisar[] = ['saldo inicial ya pagado/anulado', $legacyId, Proveedor::find($proveedorId)?->razon_social,
                    'legacy ' . $this->pesos($saldoLegacy) . ' / cargado ' . $this->pesos((float) $compra->total)];
                return false;
            }
        }

        foreach ($compras as $compra) {
            $this->revertirAsientosProveedor($proveedorId, Compra::class, $compra->id);
            $compra->forceDelete();
            $this->sumar('saldos de proveedores de una corrida anterior reemplazados', 1);
        }
        $this->revertirAsientosProveedor($proveedorId, Proveedor::class, $proveedorId);

        return true;
    }

    private function revertirAsientosProveedor(int $proveedorId, string $refType, int $refId): void
    {
        $asientos = CcProveedor::where('proveedor_id', $proveedorId)->where('referencia_type', $refType)
            ->where('referencia_id', $refId)->where('concepto', 'like', self::CONCEPTO . '%')->get();

        if ($asientos->isEmpty()) {
            return;
        }

        $efecto    = $asientos->sum(fn ($a) => (float) $a->debe - (float) $a->haber);
        $proveedor = Proveedor::find($proveedorId);
        $proveedor->update(['saldo' => round((float) $proveedor->saldo - $efecto, 2)]);
        CcProveedor::whereIn('id', $asientos->pluck('id'))->delete();
    }

    // ── Helpers ─────────────────────────────────────────────────────

    /** @return \Illuminate\Support\Collection<int, float>  legacy_id => saldo (positivo = deuda) */
    private function saldosLegacy(string $tabla, string $fk)
    {
        return DB::connection('centro_goma_legacy')->table($tabla)
            ->whereDate('fecha', '<=', $this->corte)
            ->groupBy($fk)
            ->selectRaw("$fk AS id, SUM(COALESCE(egreso, 0)) - SUM(COALESCE(ingreso, 0)) AS saldo")
            ->pluck('saldo', 'id')
            ->map(fn ($s) => round((float) $s, 2));
    }

    /** Movimientos con fecha posterior al corte (en el legacy suelen ser fechas mal tipeadas). */
    private function informarPosterioresAlCorte(string $tabla, string $fk, string $maestro): void
    {
        $filas = DB::connection('centro_goma_legacy')->table($tabla)
            ->whereDate('fecha', '>', $this->corte)
            ->get([$fk, 'fecha', 'comprobante', 'ingreso', 'egreso']);

        foreach ($filas as $f) {
            $importe = (float) ($f->egreso ?? 0) - (float) ($f->ingreso ?? 0);
            $this->aRevisar[] = ["{$tabla} posterior al corte ({$f->fecha}, no incluido)", $f->{$fk},
                $this->nombreLegacy($maestro, $fk, (int) $f->{$fk}), $this->pesos($importe)];
        }
    }

    private function nombreLegacy(string $tabla, string $pk, int $id): string
    {
        return (string) DB::connection('centro_goma_legacy')->table($tabla)->where($pk, $id)->value('razon_social');
    }

    private function sumar(string $clave, int|float $valor): void
    {
        $this->stats[$clave] = ($this->stats[$clave] ?? 0) + $valor;
    }

    private function pesos(float $v): string
    {
        return '$' . number_format($v, 2, ',', '.');
    }
}
