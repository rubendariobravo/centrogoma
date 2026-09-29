<?php

namespace App\Livewire\Cajas;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Services\Caja\CajaService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Caja')]
class Index extends Component
{
    use WithPagination;

    public ?Caja $cajaAbierta = null;

    // Apertura (caja diaria del usuario)
    public bool  $showApertura  = false;
    public float $saldo_inicial = 0;
    public string $obs_apertura = '';

    /**
     * Cierre de la última caja diaria del usuario, para proponerlo como saldo de
     * apertura. El saldo inicial se tipeaba de cero cada mañana y cualquier error
     * ahí corre todo el arqueo del día (18/08/2026: se abrió con $63.750 cuando el
     * cierre anterior había sido $79.600, y hubo que compensar $15.850 a mano).
     */
    public float   $cierreAnterior      = 0;
    public ?string $cierreAnteriorFecha = null;

    // Cierre
    public bool   $showCierre    = false;
    public float  $saldo_final   = 0;
    public string $obs_cierre    = '';
    public bool   $transferirAlCerrar      = false;
    public ?int   $transferDestinoCierre   = null;

    // Crear caja general / banco (admin)
    public bool   $showNuevaCaja = false;
    public string $nueva_nombre  = '';
    public string $nueva_tipo    = 'general';
    public float  $nueva_saldo   = 0;

    // Transferencia entre cajas
    public bool   $showTransfer   = false;
    public ?int   $transferOrigen = null;
    public ?int   $transferDestino = null;
    public ?float $transferMonto  = null;
    public string $transferDesc   = '';

    // Movimiento manual (ajuste ingreso/egreso)
    public bool   $showAjuste      = false;
    public ?int   $ajusteCajaId    = null;
    public string $ajusteTipo      = 'ingreso';
    public ?float $ajusteMonto     = null;
    public string $ajusteDesc      = '';
    public string $ajusteFormaPago = 'efectivo';
    public string $ajusteFecha     = '';

    public function mount(): void
    {
        $this->refrescarCajaAbierta();
    }

    private function refrescarCajaAbierta(): void
    {
        $this->cajaAbierta = Caja::where('estado', 'abierta')
            ->where('tipo', 'diaria')
            ->where('user_id', auth()->id())
            ->latest()
            ->first();
    }

    private function esAdmin(): bool
    {
        return auth()->user()->hasRole(['super-admin', 'admin']);
    }

    /**
     * Abre el modal de apertura proponiendo el cierre de la última caja del usuario.
     */
    public function abrirApertura(): void
    {
        $ultima = Caja::where('tipo', 'diaria')
            ->where('user_id', auth()->id())
            ->where('estado', 'cerrada')
            ->orderByDesc('fecha_apertura')
            ->orderByDesc('id')
            ->first();

        $this->cierreAnterior      = $ultima ? round((float) $ultima->saldo_final, 2) : 0;
        $this->cierreAnteriorFecha = $ultima?->fecha_cierre?->format('d/m/Y');
        $this->saldo_inicial       = $this->cierreAnterior;
        $this->obs_apertura        = '';

        $this->resetValidation();
        $this->showApertura = true;
    }

    public function abrirCaja(): void
    {
        $this->validate([
            'saldo_inicial' => 'required|numeric|min:0',
        ], [
            'saldo_inicial.required' => 'Ingrese el saldo inicial.',
            'saldo_inicial.min'      => 'El saldo inicial no puede ser negativo.',
        ]);

        // Dos cajas diarias abiertas a la vez rompen el arqueo sin dejar rastro: las
        // ventas caen en una y el panel muestra la otra (`refrescarCajaAbierta()` toma
        // la última). Pasó el 16/07/2026.
        $yaAbierta = Caja::where('tipo', 'diaria')
            ->where('user_id', auth()->id())
            ->where('estado', 'abierta')
            ->first();

        if ($yaAbierta) {
            $this->cajaAbierta  = $yaAbierta;
            $this->showApertura = false;
            $this->addError('saldo_inicial', 'Ya tiene una caja abierta. Ciérrela antes de abrir una nueva.');
            return;
        }

        $caja = Caja::create([
            'user_id'        => auth()->id(),
            'tipo'           => 'diaria',
            'fecha_apertura' => now()->toDateString(),
            'hora_apertura'  => now(),
            'saldo_inicial'  => $this->saldo_inicial,
            'estado'         => 'abierta',
            'observacion'    => $this->obs_apertura ?: null,
        ]);

        $this->cajaAbierta   = $caja;
        $this->showApertura  = false;
        $this->saldo_inicial = 0;
        $this->obs_apertura  = '';

        $this->dispatch('caja-updated');
        session()->flash('success', 'Caja abierta correctamente.');
    }

    public function crearCajaGeneral(): void
    {
        if (! $this->esAdmin()) {
            abort(403);
        }

        $this->validate([
            'nueva_nombre' => 'required|string|max:100',
            'nueva_tipo'   => 'required|in:general,banco',
            'nueva_saldo'  => 'required|numeric|min:0',
        ], [
            'nueva_nombre.required' => 'Ingrese un nombre para la caja.',
        ]);

        Caja::create([
            'user_id'        => null,
            'nombre'         => $this->nueva_nombre,
            'tipo'           => $this->nueva_tipo,
            'fecha_apertura' => now()->toDateString(),
            'hora_apertura'  => now(),
            'saldo_inicial'  => $this->nueva_saldo,
            'estado'         => 'abierta',
        ]);

        $this->showNuevaCaja = false;
        $this->nueva_nombre  = '';
        $this->nueva_tipo    = 'general';
        $this->nueva_saldo   = 0;

        $this->dispatch('caja-updated');
        session()->flash('success', 'Caja creada correctamente.');
    }

    public function abrirTransferencia(): void
    {
        $this->transferOrigen  = $this->cajaAbierta?->id;
        $this->transferDestino = null;
        $this->transferMonto   = null;
        $this->transferDesc    = '';
        $this->resetValidation();
        $this->showTransfer    = true;
    }

    public function transferir(): void
    {
        $this->validate([
            'transferOrigen'  => 'required|exists:cajas,id',
            'transferDestino' => 'required|exists:cajas,id|different:transferOrigen',
            'transferMonto'   => 'required|numeric|min:0.01',
        ], [
            'transferDestino.different' => 'El destino debe ser distinto del origen.',
            'transferMonto.min'         => 'El monto debe ser mayor a cero.',
        ]);

        $origen  = Caja::find($this->transferOrigen);
        $destino = Caja::find($this->transferDestino);

        if (! $origen?->estaAbierta() || ! $destino?->estaAbierta()) {
            $this->addError('transferOrigen', 'Ambas cajas deben estar abiertas.');
            return;
        }

        // Solo el dueño de la caja origen (o un admin) puede transferir desde ella
        if (! $this->esAdmin() && $origen->user_id !== auth()->id()) {
            $this->addError('transferOrigen', 'No tiene permiso sobre la caja origen.');
            return;
        }

        if (round((float) $this->transferMonto, 2) > $origen->saldo_transferible + 0.01) {
            $this->addError('transferMonto', sprintf(
                'El monto supera el efectivo disponible en la caja origen ($%s).',
                number_format($origen->saldo_transferible, 2, ',', '.')
            ));
            return;
        }

        app(CajaService::class)->transferir(
            $origen, $destino, (float) $this->transferMonto, $this->transferDesc ?: null
        );

        $this->showTransfer = false;
        $this->refrescarCajaAbierta();
        $this->dispatch('caja-updated');
        session()->flash('success', 'Transferencia registrada correctamente.');
    }

    public function abrirAjuste(): void
    {
        $this->ajusteCajaId    = $this->cajaAbierta?->id;
        $this->ajusteTipo      = 'ingreso';
        $this->ajusteMonto     = null;
        $this->ajusteDesc      = '';
        $this->ajusteFormaPago = 'efectivo';
        $this->ajusteFecha     = now()->toDateString();
        $this->resetValidation();
        $this->showAjuste      = true;
    }

    public function registrarAjuste(): void
    {
        $this->validate([
            'ajusteCajaId'    => 'required|exists:cajas,id',
            'ajusteTipo'      => 'required|in:ingreso,egreso',
            'ajusteMonto'     => 'required|numeric|min:0.01',
            'ajusteDesc'      => 'required|string|min:3|max:200',
            'ajusteFormaPago' => 'required|in:efectivo,transferencia,cheque,otro',
            'ajusteFecha'     => 'required|date',
        ], [
            'ajusteCajaId.required'    => 'Seleccione una caja.',
            'ajusteMonto.required'     => 'Ingrese el monto.',
            'ajusteMonto.min'          => 'El monto debe ser mayor a cero.',
            'ajusteDesc.required'      => 'Ingrese una descripción del movimiento.',
            'ajusteDesc.min'           => 'La descripción debe tener al menos 3 caracteres.',
            'ajusteFecha.required'     => 'Ingrese la fecha.',
        ]);

        $caja = Caja::find($this->ajusteCajaId);

        if (! $caja?->estaAbierta()) {
            $this->addError('ajusteCajaId', 'La caja seleccionada no está abierta.');
            return;
        }

        // No-admin solo puede registrar en su propia caja diaria
        if (! $this->esAdmin() && $caja->user_id !== auth()->id()) {
            $this->addError('ajusteCajaId', 'No tiene permiso sobre esa caja.');
            return;
        }

        app(CajaService::class)->registrarMovimiento(
            $caja,
            $this->ajusteTipo,
            'ajuste',
            (float) $this->ajusteMonto,
            [
                'forma_pago'  => $this->ajusteFormaPago,
                'descripcion' => $this->ajusteDesc,
                'fecha'       => $this->ajusteFecha,
            ]
        );

        $this->showAjuste = false;
        $this->refrescarCajaAbierta();
        $this->dispatch('caja-updated');

        $tipo = $this->ajusteTipo === 'ingreso' ? 'Ingreso' : 'Egreso';
        session()->flash('success', "{$tipo} registrado correctamente en {$caja->nombreEtiqueta()}.");
    }

    public function cerrarCaja(): void
    {
        if (! $this->cajaAbierta) {
            return;
        }

        $rules = ['saldo_final' => 'required|numeric|min:0'];
        if ($this->transferirAlCerrar) {
            $rules['transferDestinoCierre'] = 'required|exists:cajas,id';
        }

        $this->validate($rules, [
            'saldo_final.required'           => 'Ingrese el saldo final.',
            'saldo_final.min'                => 'El saldo final no puede ser negativo.',
            'transferDestinoCierre.required' => 'Seleccione la caja destino.',
        ]);

        $caja = $this->cajaAbierta;

        $caja->update([
            'fecha_cierre' => now()->toDateString(),
            'hora_cierre'  => now(),
            'saldo_final'  => $this->saldo_final,
            'estado'       => 'cerrada',
            'observacion'  => $this->obs_cierre ?: $caja->observacion,
        ]);

        // Transferencia opcional del saldo contado a una caja general/banco
        if ($this->transferirAlCerrar && $this->transferDestinoCierre && $this->saldo_final > 0) {
            $destino = Caja::find($this->transferDestinoCierre);
            if ($destino && $destino->estaAbierta() && $destino->id !== $caja->id) {
                app(CajaService::class)->transferir(
                    $caja, $destino, (float) $this->saldo_final,
                    'Cierre de caja ' . $caja->nombreEtiqueta()
                );
            }
        }

        $this->cajaAbierta           = null;
        $this->showCierre            = false;
        $this->saldo_final           = 0;
        $this->obs_cierre            = '';
        $this->transferirAlCerrar    = false;
        $this->transferDestinoCierre = null;

        $this->dispatch('caja-updated');
        session()->flash('success', 'Caja cerrada correctamente.');
    }

    public function render()
    {
        $esAdmin = $this->esAdmin();

        $query = Caja::with('user')
            ->orderByDesc('fecha_apertura')
            ->orderByDesc('id');

        if (! $esAdmin) {
            $query->where('user_id', auth()->id());
        }

        $historial = $query->paginate(15);

        // Cajas abiertas disponibles como destino de transferencias / panel de cajas activas
        $cajasAbiertas = Caja::with('user')
            ->where('estado', 'abierta')
            ->when(! $esAdmin, fn ($q) => $q->where(function ($s) {
                $s->where('user_id', auth()->id())->orWhereIn('tipo', ['general', 'banco']);
            }))
            ->orderBy('tipo')
            ->get();

        // Destinos para "transferir al cerrar": cajas generales / bancos abiertas
        $cajasGenerales = Caja::where('estado', 'abierta')
            ->whereIn('tipo', ['general', 'banco'])
            ->orderBy('nombre')
            ->get();

        return view('livewire.cajas.index', [
            'historial'         => $historial,
            'esAdmin'           => $esAdmin,
            'cajasAbiertas'     => $cajasAbiertas,
            'cajasGenerales'    => $cajasGenerales,
            'ultimosEfectivo'   => $this->ultimosMovimientosEfectivo(),
        ]);
    }

    /**
     * Últimos movimientos que tocaron el efectivo de mi caja, con el saldo que dejó
     * cada uno.
     *
     * El panel mostraba un único número: mirándolo dos veces en la mañana el salto
     * parece inventado, cuando en realidad lo movió una venta en efectivo que entró
     * en el medio (18/08/2026: de $599.358 a $790.458 por la venta #4705 de
     * $191.100). Con el detalle a la vista, la diferencia se explica sola.
     *
     * @return array<int, array{descripcion: string, concepto: string, signo: string, monto: string, saldo: string, hora: string}>
     */
    private function ultimosMovimientosEfectivo(): array
    {
        if (! $this->cajaAbierta) {
            return [];
        }

        $movs = MovimientoCaja::where('caja_id', $this->cajaAbierta->id)
            ->orderBy('id')
            ->get(['id', 'tipo', 'concepto', 'monto', 'forma_pago', 'descripcion', 'created_at']);

        $corrido = (float) $this->cajaAbierta->saldo_inicial;
        $filas   = [];

        foreach ($movs as $mov) {
            if (! Caja::movimientoEsEfectivo($mov->forma_pago, $mov->concepto)) {
                continue; // no toca el cajón: no explica ningún salto del efectivo
            }

            $esIngreso = $mov->tipo === 'ingreso';
            $corrido  += ($esIngreso ? 1 : -1) * (float) $mov->monto;

            $filas[] = [
                'descripcion' => $mov->descripcion ?: ucfirst(str_replace('_', ' ', (string) $mov->concepto)),
                'concepto'    => (string) $mov->concepto,
                'signo'       => $esIngreso ? '+' : '−',
                'monto'       => number_format((float) $mov->monto, 2, ',', '.'),
                'saldo'       => number_format(round($corrido, 2), 2, ',', '.'),
                'hora'        => $mov->created_at?->format('H:i') ?? '',
            ];
        }

        return array_slice(array_reverse($filas), 0, 12);
    }
}
