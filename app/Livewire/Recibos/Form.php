<?php

namespace App\Livewire\Recibos;

use App\Models\Caja;
use App\Models\CcCliente;
use App\Models\Banco;
use App\Models\Cliente;
use App\Models\Recibo;
use App\Models\ReciboDetalle;
use App\Models\Venta;
use App\Services\Caja\CajaService;
use App\Services\Cheque\ChequeService;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Concerns\BuscaCliente;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Nuevo recibo')]
class Form extends Component
{
    use BuscaCliente;
    public ?int $reciboId = null;

    public ?int    $cliente_id  = null;
    public string  $fecha       = '';
    public float   $total       = 0;   // solo para modo readOnly
    public string  $forma_pago  = 'efectivo';
    public string  $referencia  = '';
    public string  $observacion = '';

    // Datos derivados del cliente seleccionado
    public float $saldoCliente      = 0;
    public array $ventasPendientes  = [];  // creación: ventas disponibles para seleccionar
    public array $reciboDetalles    = [];  // readOnly: ventas que fueron cobradas

    // Caja abierta
    public ?int $cajaId  = null;
    public bool $sinCaja = false;

    // Modo lectura
    public bool $readOnly = false;

    // Datos del cheque de tercero (solo cuando forma_pago = 'cheque')
    public ?int   $cheque_banco_id        = null;
    public string $cheque_serie           = '';
    public string $cheque_nro             = '';
    public string $cheque_librador_nombre = '';
    public string $cheque_librador_cuit   = '';
    public string $cheque_fecha_emision   = '';
    public string $cheque_fecha_pago      = '';
    public bool   $cheque_es_echeq        = false;

    public function mount(?int $recibo = null): void
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

        if ($recibo) {
            $this->cargarRecibo($recibo);
        }
    }

    private function cargarRecibo(int $id): void
    {
        $r = Recibo::with(['cliente', 'detalles.venta'])->findOrFail($id);
        $this->reciboId    = $r->id;
        $this->cliente_id  = $r->cliente_id;
        $this->fecha       = $r->fecha->toDateString();
        $this->total       = (float) $r->total;
        $this->forma_pago  = $r->forma_pago;
        $this->referencia  = $r->referencia ?? '';
        $this->observacion = $r->observacion ?? '';
        $this->readOnly    = true;

        if ($r->cliente_id) {
            $cliente = Cliente::find($r->cliente_id);
            $this->saldoCliente = (float) ($cliente?->saldo ?? 0);
        }

        $this->reciboDetalles = $r->detalles->map(fn ($d) => [
            'nro_venta' => $d->venta?->nro_venta,
            'fecha'     => $d->venta?->fecha?->format('d/m/Y'),
            'total'     => (float) ($d->venta?->total ?? 0),
            'monto'     => (float) $d->monto_aplicado,
        ])->toArray();
    }

    public function updatedClienteId(): void
    {
        if ($this->cliente_id) {
            $this->cargarDatosCliente($this->cliente_id);
        } else {
            $this->saldoCliente    = 0;
            $this->ventasPendientes = [];
        }
    }

    private function cargarDatosCliente(int $id): void
    {
        $cliente = Cliente::find($id);
        $this->saldoCliente = (float) ($cliente?->saldo ?? 0);

        $this->ventasPendientes = Venta::where('cliente_id', $id)
            ->where('estado', 'P')
            ->where('saldo', '>', 0)
            ->orderBy('fecha')
            ->get()
            ->map(fn ($v) => [
                'id'        => $v->id,
                'nro_venta' => $v->nro_venta,
                'saldo_inicial' => (bool) $v->saldo_inicial,
                'fecha'     => $v->fecha->format('d/m/Y'),
                'total'     => (float) $v->total,
                'saldo'     => (float) $v->saldo,
            ])
            ->toArray();
    }

    /**
     * @param array $detalles  [{venta_id: int, monto_aplicar: float}, ...]
     */
    public function save(array $detalles): void
    {
        if ($this->readOnly) {
            return;
        }

        $rules = [
            'cliente_id' => 'required|exists:clientes,id',
            'fecha'      => 'required|date',
            'forma_pago' => 'required|in:efectivo,transferencia,cheque,tarjeta,otro',
        ];
        if ($this->forma_pago === 'cheque') {
            $rules['cheque_banco_id']        = 'required|exists:bancos,id';
            $rules['cheque_nro']             = 'required|string|max:30';
            $rules['cheque_librador_nombre'] = 'required|string|max:150';
            $rules['cheque_fecha_pago']      = 'required|date';
        }
        $this->validate($rules, [
            'cliente_id.required'       => 'Seleccione un cliente.',
            'cheque_banco_id.required'        => 'Seleccione el banco del cheque.',
            'cheque_nro.required'             => 'Indique el número del cheque.',
            'cheque_librador_nombre.required' => 'Indique el librador del cheque.',
            'cheque_fecha_pago.required'      => 'Indique la fecha de cobro del cheque.',
        ]);

        if (empty($detalles)) {
            $this->addError('detalles', 'Debe seleccionar al menos una factura y asignar un monto.');
            return;
        }

        // Validar cada detalle
        $total = 0;
        $ventasValidadas = [];
        foreach ($detalles as $det) {
            $ventaId     = (int) ($det['venta_id'] ?? 0);
            $montoAplicar = round((float) ($det['monto_aplicar'] ?? 0), 2);

            $venta = Venta::where('id', $ventaId)
                ->where('cliente_id', $this->cliente_id)
                ->where('estado', 'P')
                ->first();

            if (! $venta) {
                $this->addError('detalles', "Factura #{$ventaId} no válida.");
                return;
            }

            if ($montoAplicar <= 0 || $montoAplicar > (float) $venta->saldo + 0.01) {
                $this->addError('detalles', "Monto inválido para factura #{$venta->nro_venta}.");
                return;
            }

            $total += $montoAplicar;
            $ventasValidadas[] = ['venta' => $venta, 'monto' => $montoAplicar];
        }

        $total = round($total, 2);

        $nroRecibo = (Recibo::max('nro_recibo') ?? 0) + 1;

        $recibo = Recibo::create([
            'cliente_id'  => $this->cliente_id,
            'caja_id'     => $this->cajaId,
            'user_id'     => Auth::id(),
            'nro_recibo'  => $nroRecibo,
            'fecha'       => $this->fecha,
            'total'       => $total,
            'forma_pago'  => $this->forma_pago,
            'referencia'  => $this->referencia ?: null,
            'observacion' => $this->observacion ?: null,
            'estado'      => 'C',
        ]);

        // Aplicar montos específicos a cada venta y crear detalles
        foreach ($ventasValidadas as $item) {
            $venta      = $item['venta'];
            $monto      = $item['monto'];
            $nuevoSaldo = max(0, round((float) $venta->saldo - $monto, 2));

            $venta->update([
                'saldo'  => $nuevoSaldo,
                'estado' => $nuevoSaldo <= 0 ? 'C' : 'P',
            ]);

            ReciboDetalle::create([
                'recibo_id'      => $recibo->id,
                'venta_id'       => $venta->id,
                'monto_aplicado' => $monto,
            ]);
        }

        // Actualizar saldo del cliente
        $cliente       = Cliente::find($this->cliente_id);
        $nuevoSaldoCliente = max(0, round((float) ($cliente?->saldo ?? 0) - $total, 2));
        $cliente?->update(['saldo' => $nuevoSaldoCliente]);

        // Crear movimiento en CC Cliente
        CcCliente::create([
            'cliente_id'     => $this->cliente_id,
            'fecha'          => $this->fecha,
            'concepto'       => "Recibo #{$nroRecibo} - {$this->forma_pago}",
            'debe'           => 0,
            'haber'          => $total,
            'saldo'          => $nuevoSaldoCliente,
            'referencia_type'=> Recibo::class,
            'referencia_id'  => $recibo->id,
        ]);

        // Actualizar caja (libro de movimientos + contador cacheado)
        if ($this->cajaId && ($caja = Caja::find($this->cajaId))) {
            app(CajaService::class)->registrarMovimiento(
                $caja, 'ingreso', 'cobro', $total,
                [
                    'forma_pago'      => $this->forma_pago,
                    'referencia_type' => Recibo::class,
                    'referencia_id'   => $recibo->id,
                    'descripcion'     => "Recibo #{$nroRecibo}",
                    'fecha'           => $this->fecha,
                ]
            );
        }

        // Crear cheque de tercero en cartera si el cobro fue con cheque.
        // La plata entra a la caja banco al acreditarlo desde /cheques.
        if ($this->forma_pago === 'cheque') {
            app(ChequeService::class)->registrarRecibido([
                'banco_id'        => $this->cheque_banco_id,
                'serie'           => $this->cheque_serie ?: null,
                'nro_cheque'      => $this->cheque_nro,
                'monto'           => $total,
                'cliente_id'      => $this->cliente_id,
                'caja_origen_id'  => $this->cajaId,
                'recibo_id'       => $recibo->id,
                'es_echeq'        => $this->cheque_es_echeq,
                'fecha_recepcion' => $this->fecha,
                'fecha_pago'      => $this->cheque_fecha_pago,
                'librador_nombre' => $this->cheque_librador_nombre,
                'librador_cuit'   => $this->cheque_librador_cuit ?: null,
                'observacion'     => "Recibo #{$nroRecibo}",
                'referencia_type' => Recibo::class,
                'referencia_id'   => $recibo->id,
            ]);
        }

        session()->flash('success', "Recibo #{$nroRecibo} registrado correctamente.");
        $this->redirect(route('recibos'), navigate: true);
    }

    public function render()
    {
        return view('livewire.recibos.form', [
            'bancos'   => Banco::where('activo', true)->orderBy('descripcion')->get(),
        ]);
    }
}
