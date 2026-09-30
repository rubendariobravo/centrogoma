<?php

namespace App\Services\Anulacion;

use App\Models\CcCliente;
use App\Models\CcProveedor;
use App\Models\Cheque;
use App\Models\Cliente;
use App\Models\MovimientoArticulo;
use App\Models\OrdenPago;
use App\Models\Presupuesto;
use App\Models\Proveedor;
use App\Models\Recibo;
use App\Models\Venta;
use App\Services\Caja\CajaService;
use App\Services\Cheque\ChequeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Anulación de ventas, recibos y órdenes de pago: revierte cada efecto que el comprobante
 * aplicó al registrarse (stock, caja, cuenta corriente, cheques, documentos aplicados) y lo
 * marca estado 'A' con el motivo. Nada se borra: los detalles quedan para auditoría.
 *
 * Las reglas que impiden anular se informan con AnulacionException.
 */
class AnulacionService
{
    /** Estados de un cheque de tercero que ya salió de nuestras manos o ya reabrió la deuda. */
    private const CHEQUE_TERCERO_BLOQUEANTE = ['rechazado', 'aplicado', 'vendido'];

    public function __construct(
        private CajaService $cajas,
        private ChequeService $cheques,
    ) {
    }

    // ── Ventas ──────────────────────────────────────────────────────

    /**
     * Recibos vigentes que aplicaron cobros a la venta. Mientras existan, la venta no se
     * puede anular: el cobro quedaría aplicado a un comprobante anulado y el cliente
     * terminaría debiendo lo que ya pagó.
     */
    public function cobrosAplicados(int $ventaId): Collection
    {
        return DB::table('recibo_detalles as rd')
            ->join('recibos as r', 'r.id', '=', 'rd.recibo_id')
            ->where('rd.venta_id', $ventaId)
            ->where('r.estado', '!=', 'A')
            ->whereNull('r.deleted_at')
            ->where('rd.monto_aplicado', '>', 0.001)
            ->orderBy('r.nro_recibo')
            ->get(['r.nro_recibo', 'r.fecha', 'rd.monto_aplicado']);
    }

    public function anularVenta(Venta $venta, string $motivo): void
    {
        $this->assertMotivo($motivo);

        if ($venta->estado === 'A') {
            throw new AnulacionException('La venta ya está anulada.');
        }

        if (! empty($venta->cae)) {
            throw new AnulacionException(
                'La venta tiene CAE: debe anularse con una nota de crédito electrónica (facturación AFIP pendiente de implementar).'
            );
        }

        $cobros = $this->cobrosAplicados($venta->id);
        if ($cobros->isNotEmpty()) {
            throw new AnulacionException(sprintf(
                'La venta tiene cobros aplicados por $%s (recibo %s). Anule primero el recibo y después la venta.',
                number_format((float) $cobros->sum('monto_aplicado'), 2, ',', '.'),
                $cobros->pluck('nro_recibo')->map(fn ($n) => "#{$n}")->implode(', ')
            ));
        }

        $chequesVenta = Cheque::where('venta_id', $venta->id)->where('estado', '!=', 'anulado')->get();
        $this->assertChequesTerceroAnulables($chequesVenta);

        DB::transaction(function () use ($venta, $motivo, $chequesVenta) {
            $venta->loadMissing('detalles.articulo');

            foreach ($venta->detalles as $det) {
                $art = $det->articulo;
                if (! $art || ! $art->modifica_stock) {
                    continue;
                }

                $stockAnterior = (float) $art->stock;
                $art->increment('stock', $det->cantidad);

                MovimientoArticulo::create([
                    'articulo_id'     => $art->id,
                    'user_id'         => Auth::id(),
                    'tipo'            => 'entrada',
                    'cantidad'        => $det->cantidad,
                    'stock_anterior'  => $stockAnterior,
                    'stock_posterior' => $stockAnterior + (float) $det->cantidad,
                    'fecha'           => now()->toDateString(),
                    'referencia_type' => Venta::class,
                    'referencia_id'   => $venta->id,
                    'observacion'     => "Anulación venta #{$venta->nro_venta}",
                ]);
            }

            $this->cajas->revertirMovimientosDe(Venta::class, $venta->id);

            foreach ($chequesVenta as $cheque) {
                $this->cheques->anular($cheque, "Anulación venta #{$venta->nro_venta}");
            }

            // Cuenta corriente: sin cobros aplicados, el saldo pendiente es lo que la venta cargó.
            $saldoCargado = round((float) $venta->saldo, 2);
            if ($venta->cliente_id && $saldoCargado > 0.001 && ($cliente = Cliente::find($venta->cliente_id))) {
                $nuevoSaldo = round((float) $cliente->saldo - $saldoCargado, 2);
                $cliente->update(['saldo' => $nuevoSaldo]);

                CcCliente::create([
                    'cliente_id'      => $cliente->id,
                    'fecha'           => now()->toDateString(),
                    'concepto'        => "Anulación venta #{$venta->nro_venta}",
                    'debe'            => 0,
                    'haber'           => $saldoCargado,
                    'saldo'           => $nuevoSaldo,
                    'referencia_type' => Venta::class,
                    'referencia_id'   => $venta->id,
                ]);
            }

            // El presupuesto que originó la venta vuelve a quedar activo.
            Presupuesto::where('venta_id', $venta->id)->where('estado', 'F')
                ->update(['estado' => 'A', 'venta_id' => null]);

            $venta->update([
                'estado'       => 'A',
                'saldo'        => 0,
                'totalanulado' => (float) $venta->total,
                'observacion'  => $this->marcar($venta->observacion, 'ANULADA', $motivo),
            ]);
        });
    }

    // ── Recibos ─────────────────────────────────────────────────────

    public function anularRecibo(Recibo $recibo, string $motivo): void
    {
        $this->assertMotivo($motivo);

        if ($recibo->estado === 'A') {
            throw new AnulacionException('El recibo ya está anulado.');
        }

        $chequesRecibo = Cheque::where('recibo_id', $recibo->id)->where('estado', '!=', 'anulado')->get();
        $this->assertChequesTerceroAnulables($chequesRecibo);

        DB::transaction(function () use ($recibo, $motivo, $chequesRecibo) {
            $recibo->loadMissing('detalles.venta');

            // Devolver lo aplicado a cada venta cobrada (vuelve a Pendiente).
            foreach ($recibo->detalles as $det) {
                if ($venta = $det->venta) {
                    $nuevoSaldo = round((float) $venta->saldo + (float) $det->monto_aplicado, 2);
                    $venta->update(['saldo' => $nuevoSaldo, 'estado' => $nuevoSaldo > 0.01 ? 'P' : 'C']);
                }
            }

            if ($cliente = Cliente::find($recibo->cliente_id)) {
                $nuevoSaldo = round((float) $cliente->saldo + (float) $recibo->total, 2);
                $cliente->update(['saldo' => $nuevoSaldo]);

                CcCliente::create([
                    'cliente_id'      => $recibo->cliente_id,
                    'fecha'           => now()->toDateString(),
                    'concepto'        => "Anulación Recibo #{$recibo->nro_recibo}",
                    'debe'            => $recibo->total,
                    'haber'           => 0,
                    'saldo'           => $nuevoSaldo,
                    'referencia_type' => Recibo::class,
                    'referencia_id'   => $recibo->id,
                ]);
            }

            $this->cajas->revertirMovimientosDe(Recibo::class, $recibo->id);

            foreach ($chequesRecibo as $cheque) {
                $this->cheques->anular($cheque, "Anulación Recibo #{$recibo->nro_recibo}");
            }

            $recibo->update([
                'estado'      => 'A',
                'observacion' => $this->marcar($recibo->observacion, 'ANULADO', $motivo),
            ]);
        });
    }

    // ── Órdenes de pago ─────────────────────────────────────────────

    public function anularOrdenPago(OrdenPago $orden, string $motivo): void
    {
        $this->assertMotivo($motivo);

        if ($orden->estado === 'A') {
            throw new AnulacionException('La orden de pago ya está anulada.');
        }

        DB::transaction(function () use ($orden, $motivo) {
            $orden->loadMissing('detalles.compra');

            // Devolver el saldo a cada compra pagada (vuelve a Pendiente).
            foreach ($orden->detalles as $det) {
                if ($compra = $det->compra) {
                    $nuevoSaldo = round((float) $compra->saldo + (float) $det->monto, 2);
                    $compra->update(['saldo' => $nuevoSaldo, 'estado' => $nuevoSaldo > 0.01 ? 'P' : 'C']);
                }
            }

            if ($proveedor = Proveedor::find($orden->proveedor_id)) {
                $nuevoSaldo = round((float) $proveedor->saldo + (float) $orden->total, 2);
                $proveedor->update(['saldo' => $nuevoSaldo]);

                CcProveedor::create([
                    'proveedor_id'    => $orden->proveedor_id,
                    'fecha'           => now()->toDateString(),
                    'concepto'        => "Anulación Orden de Pago #{$orden->nro_orden}",
                    'debe'            => $orden->total,
                    'haber'           => 0,
                    'saldo'           => $nuevoSaldo,
                    'referencia_type' => OrdenPago::class,
                    'referencia_id'   => $orden->id,
                ]);
            }

            // Egreso directo (efectivo/transferencia). Con cheque propio no hubo egreso:
            // si ya se debitó, lo revierte la anulación del cheque.
            $this->cajas->revertirMovimientosDe(OrdenPago::class, $orden->id);

            Cheque::where('orden_pago_id', $orden->id)->where('estado', '!=', 'anulado')->get()
                ->each(fn (Cheque $c) => $this->cheques->anular($c, "Anulación Orden de Pago #{$orden->nro_orden}"));

            $orden->update([
                'estado'      => 'A',
                'observacion' => $this->marcar($orden->observacion, 'ANULADA', $motivo),
            ]);
        });
    }

    // ── Helpers ─────────────────────────────────────────────────────

    private function assertMotivo(string $motivo): void
    {
        if (mb_strlen(trim($motivo)) < 5) {
            throw new AnulacionException('Indique el motivo de la anulación (mínimo 5 caracteres).');
        }
    }

    /**
     * Un cheque de tercero rechazado ya reabrió la deuda del cliente (anular el cobro la
     * duplicaría); uno endosado o vendido ya no está en cartera. Se resuelven desde Cheques.
     */
    private function assertChequesTerceroAnulables(Collection $cheques): void
    {
        $bloqueante = $cheques->first(fn (Cheque $c) => in_array($c->estado, self::CHEQUE_TERCERO_BLOQUEANTE, true));
        if ($bloqueante) {
            throw new AnulacionException(sprintf(
                'El cheque %s está %s. Resuélvalo desde Cheques antes de anular este comprobante.',
                $bloqueante->nro_cheque ?: '#' . $bloqueante->id,
                mb_strtolower(Cheque::ESTADO_LABELS[$bloqueante->estado] ?? $bloqueante->estado)
            ));
        }
    }

    private function marcar(?string $observacion, string $palabra, string $motivo): string
    {
        $obs   = trim($observacion ?? '');
        $marca = $palabra . ' (' . now()->format('d/m/Y H:i') . '): ' . trim($motivo);

        return $obs ? ($obs . "\n" . $marca) : $marca;
    }
}
