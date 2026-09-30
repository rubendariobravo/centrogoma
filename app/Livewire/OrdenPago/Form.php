<?php

namespace App\Livewire\OrdenPago;

use App\Models\Banco;
use App\Models\Caja;
use App\Models\CcProveedor;
use App\Models\Compra;
use App\Models\DetalleOrdenPago;
use App\Models\OrdenPago;
use App\Models\Proveedor;
use App\Services\Caja\CajaService;
use App\Services\Cheque\ChequeService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Nueva orden de pago')]
class Form extends Component
{
    public ?int $ordenPagoId = null;

    public ?int    $proveedor_id = null;
    public string  $fecha        = '';
    public float   $total        = 0;   // solo para readOnly
    public string  $forma_pago   = 'efectivo';
    public string  $referencia   = '';
    public string  $observacion  = '';

    // Datos derivados del proveedor seleccionado
    public float $saldoProveedor    = 0;
    public array $comprasPendientes = [];   // creación: compras disponibles
    public array $ordenDetalles     = [];   // readOnly: compras que fueron pagadas

    // Caja abierta
    public ?int $cajaId  = null;
    public bool $sinCaja = false;

    // Modo lectura
    public bool $readOnly = false;

    // Datos del cheque propio emitido (solo cuando forma_pago = 'cheque')
    public ?int   $cheque_banco_id      = null;
    public string $cheque_serie         = '';
    public string $cheque_nro           = '';
    public string $cheque_fecha_emision = '';
    public string $cheque_fecha_pago    = '';
    public bool   $cheque_es_echeq      = false;

    public function mount(?int $ordenPago = null): void
    {
        $this->fecha                = now()->toDateString();
        $this->cheque_fecha_emision = now()->toDateString();
        $this->cheque_fecha_pago    = now()->addDays(30)->toDateString();

        $caja = Caja::where('estado', 'abierta')->where('tipo', 'diaria')
            ->where('user_id', Auth::id())
            ->latest()->first();

        if ($caja) {
            $this->cajaId = $caja->id;
        } else {
            $this->sinCaja = true;
        }

        $proveedorId = (int) request()->query('proveedor_id');
        if ($proveedorId) {
            $this->proveedor_id = $proveedorId;
            $this->cargarDatosProveedor($proveedorId);
        }

        if ($ordenPago) {
            $this->cargarOrdenPago($ordenPago);
        }
    }

    private function cargarOrdenPago(int $id): void
    {
        $op = OrdenPago::with(['proveedor', 'detalles.compra'])->findOrFail($id);
        $this->ordenPagoId  = $op->id;
        $this->proveedor_id = $op->proveedor_id;
        $this->fecha        = $op->fecha->toDateString();
        $this->total        = (float) $op->total;
        $this->forma_pago   = $op->forma_pago;
        $this->referencia   = $op->referencia ?? '';
        $this->observacion  = $op->observacion ?? '';
        $this->readOnly     = true;

        if ($op->proveedor_id) {
            $proveedor = Proveedor::find($op->proveedor_id);
            $this->saldoProveedor = (float) ($proveedor?->saldo ?? 0);
        }

        $this->ordenDetalles = $op->detalles->map(fn ($d) => [
            'nro_factura' => ($d->compra?->letra ?? '') . '-' . ($d->compra?->nro_factura ?? ''),
            'fecha'       => $d->compra?->fecha?->format('d/m/Y'),
            'total'       => (float) ($d->compra?->total ?? 0),
            'monto'       => (float) $d->monto,
        ])->toArray();
    }

    public function updatedProveedorId(): void
    {
        if ($this->proveedor_id) {
            $this->cargarDatosProveedor($this->proveedor_id);
        } else {
            $this->saldoProveedor    = 0;
            $this->comprasPendientes = [];
        }
    }

    private function cargarDatosProveedor(int $id): void
    {
        $proveedor = Proveedor::find($id);
        $this->saldoProveedor = (float) ($proveedor?->saldo ?? 0);

        $this->comprasPendientes = Compra::where('proveedor_id', $id)
            ->where('estado', 'P')
            ->where('saldo', '>', 0)
            ->orderBy('fecha')
            ->get()
            ->map(fn ($c) => [
                'id'          => $c->id,
                'nro_factura' => $c->nro_factura,
                'letra'       => $c->letra,
                'saldo_inicial' => (bool) $c->saldo_inicial,
                'fecha'       => $c->fecha->format('d/m/Y'),
                'total'       => (float) $c->total,
                'saldo'       => (float) $c->saldo,
            ])
            ->toArray();
    }

    /**
     * @param array $detalles  [{compra_id: int, monto_aplicar: float}, ...]
     */
    public function save(array $detalles): void
    {
        if ($this->readOnly) {
            return;
        }

        $rules = [
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha'        => 'required|date',
            'forma_pago'   => 'required|in:efectivo,transferencia,cheque,tarjeta,otro',
        ];
        if ($this->forma_pago === 'cheque') {
            $rules['cheque_banco_id']   = 'required|exists:bancos,id';
            $rules['cheque_nro']        = 'required|string|max:30';
            $rules['cheque_fecha_pago'] = 'required|date';
        }
        $this->validate($rules, [
            'proveedor_id.required'       => 'Seleccione un proveedor.',
            'cheque_banco_id.required'   => 'Seleccione el banco del cheque.',
            'cheque_nro.required'        => 'Indique el número del cheque.',
            'cheque_fecha_pago.required' => 'Indique la fecha de pago del cheque.',
        ]);

        if (empty($detalles)) {
            $this->addError('detalles', 'Debe seleccionar al menos una factura y asignar un monto.');
            return;
        }

        $total = 0;
        $comprasValidadas = [];
        foreach ($detalles as $det) {
            $compraId     = (int) ($det['compra_id'] ?? 0);
            $montoAplicar = round((float) ($det['monto_aplicar'] ?? 0), 2);

            $compra = Compra::where('id', $compraId)
                ->where('proveedor_id', $this->proveedor_id)
                ->where('estado', 'P')
                ->first();

            if (! $compra) {
                $this->addError('detalles', "Factura #{$compraId} no válida.");
                return;
            }

            if ($montoAplicar <= 0 || $montoAplicar > (float) $compra->saldo + 0.01) {
                $this->addError('detalles', "Monto inválido para factura {$compra->letra}-{$compra->nro_factura}.");
                return;
            }

            $total += $montoAplicar;
            $comprasValidadas[] = ['compra' => $compra, 'monto' => $montoAplicar];
        }

        $total    = round($total, 2);
        $nroOrden = (OrdenPago::max('nro_orden') ?? 0) + 1;

        $orden = OrdenPago::create([
            'proveedor_id' => $this->proveedor_id,
            'caja_id'      => $this->cajaId,
            'user_id'      => Auth::id(),
            'nro_orden'    => $nroOrden,
            'fecha'        => $this->fecha,
            'total'        => $total,
            'forma_pago'   => $this->forma_pago,
            'referencia'   => $this->referencia ?: null,
            'observacion'  => $this->observacion ?: null,
            'estado'       => 'C',
        ]);

        foreach ($comprasValidadas as $item) {
            $compra     = $item['compra'];
            $monto      = $item['monto'];
            $nuevoSaldo = max(0, round((float) $compra->saldo - $monto, 2));

            $compra->update([
                'saldo'  => $nuevoSaldo,
                'estado' => $nuevoSaldo <= 0 ? 'C' : 'P',
            ]);

            DetalleOrdenPago::create([
                'orden_pago_id' => $orden->id,
                'compra_id'     => $compra->id,
                'monto'         => $monto,
            ]);
        }

        // Actualizar saldo del proveedor
        $proveedor  = Proveedor::find($this->proveedor_id);
        $nuevoSaldo = max(0, round((float) ($proveedor?->saldo ?? 0) - $total, 2));

        if ($proveedor) {
            $proveedor->update(['saldo' => $nuevoSaldo]);
        }

        // Movimiento en CC Proveedor
        CcProveedor::create([
            'proveedor_id'    => $this->proveedor_id,
            'fecha'           => $this->fecha,
            'concepto'        => "Orden de Pago #{$nroOrden} - {$this->forma_pago}",
            'debe'            => 0,
            'haber'           => $total,
            'saldo'           => $nuevoSaldo,
            'referencia_type' => OrdenPago::class,
            'referencia_id'   => $orden->id,
        ]);

        // Actualizar caja: egreso real solo si NO es pago con cheque. Un cheque propio
        // es diferido — la plata sale de la caja banco al debitarse, no al emitirse.
        // (El egreso de caja del cheque se registrará en la fase de débito de cheques.)
        if ($this->forma_pago !== 'cheque' && $this->cajaId && ($caja = Caja::find($this->cajaId))) {
            app(CajaService::class)->registrarMovimiento(
                $caja, 'egreso', 'pago_proveedor', $total,
                [
                    'forma_pago'      => $this->forma_pago,
                    'referencia_type' => OrdenPago::class,
                    'referencia_id'   => $orden->id,
                    'descripcion'     => "Orden de Pago #{$nroOrden}",
                    'fecha'           => $this->fecha,
                ]
            );
        }

        // Crear cheque propio emitido si el pago fue con cheque. La plata NO sale
        // de la caja ahora: el egreso se registra al debitarse desde /cheques.
        if ($this->forma_pago === 'cheque') {
            app(ChequeService::class)->registrarEmitido([
                'banco_id'        => $this->cheque_banco_id,
                'serie'           => $this->cheque_serie ?: null,
                'nro_cheque'      => $this->cheque_nro,
                'monto'           => $total,
                'proveedor_id'    => $this->proveedor_id,
                'caja_id'         => null, // la caja banco se elige al debitar el cheque
                'orden_pago_id'   => $orden->id,
                'es_echeq'        => $this->cheque_es_echeq,
                'fecha_emision'   => $this->fecha,
                'fecha_pago'      => $this->cheque_fecha_pago,
                'observacion'     => "Orden de Pago #{$nroOrden}",
                'referencia_type' => OrdenPago::class,
                'referencia_id'   => $orden->id,
            ]);
        }

        session()->flash('success', "Orden de Pago #{$nroOrden} registrada correctamente.");
        $this->redirect(route('ordenes-pago'), navigate: true);
    }

    public function render()
    {
        return view('livewire.ordenes-pago.form', [
            'proveedores' => Proveedor::where('estado', 'A')->orderBy('razon_social')->get(),
            'bancos'      => Banco::where('activo', true)->orderBy('descripcion')->get(),
        ]);
    }
}
