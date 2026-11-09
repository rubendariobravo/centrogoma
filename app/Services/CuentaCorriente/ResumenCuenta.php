<?php

namespace App\Services\CuentaCorriente;

use App\Models\CcCliente;
use App\Models\CcProveedor;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Datos del resumen de cuenta corriente (clientes y proveedores) y del listado de saldos, para imprimir y exportar.
 *
 * En las dos CC el saldo es SUM(debe − haber): positivo = el cliente nos debe / le debemos al proveedor. El saldo
 * corrido se recalcula en orden de fecha (la columna `saldo` de cada asiento es el de la ficha al momento de grabar,
 * y un comprobante con fecha anterior lo desordena).
 */
class ResumenCuenta
{
    public const TIPOS = ['clientes', 'proveedores'];

    public function __construct(private string $tipo)
    {
        abort_unless(in_array($tipo, self::TIPOS, true), 404);
    }

    public function esClientes(): bool
    {
        return $this->tipo === 'clientes';
    }

    public function cuenta(int $id): Model
    {
        return $this->esClientes() ? Cliente::findOrFail($id) : Proveedor::findOrFail($id);
    }

    /**
     * Movimientos entre fechas con saldo anterior y saldo corrido.
     *
     * @return array{saldo_anterior: float, movimientos: Collection, debe: float, haber: float, saldo: float}
     */
    public function movimientos(int $id, ?string $desde = null, ?string $hasta = null): array
    {
        $modelo = $this->esClientes() ? CcCliente::class : CcProveedor::class;
        $fk     = $this->esClientes() ? 'cliente_id' : 'proveedor_id';

        $saldoAnterior = $desde
            ? (float) $modelo::where($fk, $id)->where('fecha', '<', $desde)->sum(\DB::raw('debe - haber'))
            : 0.0;

        $corrido = $saldoAnterior;
        $movs = $modelo::where($fk, $id)
            ->when($desde, fn ($q) => $q->where('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->where('fecha', '<=', $hasta))
            ->orderBy('fecha')->orderBy('id')
            ->get()
            ->map(function ($m) use (&$corrido) {
                $corrido = round($corrido + (float) $m->debe - (float) $m->haber, 2);

                return [
                    'fecha'    => $m->fecha,
                    'concepto' => $m->concepto,
                    'debe'     => (float) $m->debe,
                    'haber'    => (float) $m->haber,
                    'saldo'    => $corrido,
                ];
            });

        return [
            'saldo_anterior' => round($saldoAnterior, 2),
            'movimientos'    => $movs,
            'debe'           => round($movs->sum('debe'), 2),
            'haber'          => round($movs->sum('haber'), 2),
            'saldo'          => $corrido,
        ];
    }

    /** Comprobantes con saldo pendiente de una cuenta (o de todas si $ids es null), del más viejo al más nuevo. */
    public function pendientes(?array $ids = null): Collection
    {
        $hoy = Carbon::today();

        if ($this->esClientes()) {
            return Venta::query()
                ->where('estado', 'P')->where('saldo', '>', 0)
                ->when($ids !== null, fn ($q) => $q->whereIn('cliente_id', $ids))
                ->orderBy('fecha')->orderBy('id')
                ->get(['id', 'cliente_id', 'nro_venta', 'fecha', 'letra_comprobante', 'tipo_comprobante', 'total', 'saldo', 'saldo_inicial'])
                ->map(fn ($v) => [
                    'cuenta_id'   => $v->cliente_id,
                    'comprobante' => $v->saldo_inicial ? 'Saldo inicial' : 'Venta ' . ($v->letra_comprobante ? $v->letra_comprobante . ' ' : '') . '#' . $v->nro_venta,
                    'fecha'       => $v->fecha,
                    'dias'        => (int) $v->fecha->diffInDays($hoy),
                    'total'       => round((float) $v->total, 2),
                    'saldo'       => round((float) $v->saldo, 2),
                ]);
        }

        return Compra::query()
            ->where('estado', 'P')->where('saldo', '>', 0)
            ->when($ids !== null, fn ($q) => $q->whereIn('proveedor_id', $ids))
            ->orderBy('fecha')->orderBy('id')
            ->get(['id', 'proveedor_id', 'nro_factura', 'letra', 'fecha', 'total', 'saldo', 'saldo_inicial'])
            ->map(fn ($c) => [
                'cuenta_id'   => $c->proveedor_id,
                'comprobante' => $c->saldo_inicial ? 'Saldo inicial' : 'Factura ' . trim(($c->letra ?? '') . ' ' . ($c->nro_factura ?: '#' . $c->id)),
                'fecha'       => $c->fecha,
                'dias'        => (int) $c->fecha->diffInDays($hoy),
                'total'       => round((float) $c->total, 2),
                'saldo'       => round((float) $c->saldo, 2),
            ]);
    }

    /**
     * Cuentas con saldo, de mayor a menor, cada una con sus comprobantes pendientes.
     *
     * @param  string  $filtro  deudores (saldo > 0) | a_favor (saldo < 0) | todos (≠ 0)
     */
    public function saldos(string $filtro = 'deudores'): Collection
    {
        $modelo = $this->esClientes() ? Cliente::class : Proveedor::class;

        $cuentas = $modelo::query()
            // saldo es decimal(15,2): se compara exacto contra 0 (un float negativo como binding no es confiable).
            ->when($filtro === 'deudores', fn ($q) => $q->where('saldo', '>', 0))
            ->when($filtro === 'a_favor', fn ($q) => $q->where('saldo', '<', 0))
            ->when($filtro === 'todos', fn ($q) => $q->where('saldo', '<>', 0))
            ->orderByDesc('saldo')
            ->get(['id', 'razon_social', 'cuit', 'telefono', 'saldo']);

        $pendientes = $this->pendientes($cuentas->pluck('id')->all())->groupBy('cuenta_id');

        return $cuentas->map(fn ($c) => [
            'id'          => $c->id,
            'nombre'      => $c->razon_social,
            'cuit'        => $c->cuit,
            'telefono'    => $c->telefono,
            'saldo'       => round((float) $c->saldo, 2),
            'pendientes'  => $pendientes->get($c->id, collect())->values(),
        ]);
    }
}
