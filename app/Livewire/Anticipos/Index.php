<?php

namespace App\Livewire\Anticipos;

use App\Models\AnticipoEmpleado;
use App\Models\AnticipoPago;
use App\Models\Caja;
use App\Models\Empleado;
use App\Models\FormaPago;
use App\Services\Caja\CajaService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Anticipos de Sueldo')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'empleado')]
    public ?int $filterEmpleado = null;

    public string $search          = '';
    public string $filterEstado    = '';
    public string $filterFormaPago = '';
    public string $fechaDesde      = '';
    public string $fechaHasta      = '';

    // Form modal
    public bool   $showForm      = false;
    public ?int   $anticipoId    = null;
    public ?int   $empleado_id   = null;
    public string $fecha         = '';
    public string $monto         = '';
    public string $observaciones = '';

    /**
     * Líneas de pago del anticipo. Un anticipo puede pagarse con varios medios
     * (mitad efectivo / mitad transferencia) y cada línea sale de su propia caja.
     *
     * Estructura: ['forma_pago' => string, 'caja_id' => ?int, 'monto' => string, 'referencia' => string]
     * Los montos viajan como STRING: un float de valor entero (ej. 30000.0) se
     * serializa distinto en PHP y JS y rompe el checksum del snapshot de Livewire.
     */
    public array $pagos = [];

    // Modal rechazo
    public bool   $showRechazarModal = false;
    public ?int   $rechazandoId      = null;
    public string $motivoRechazo     = '';

    // Delete
    public ?int $confirmingDelete = null;

    protected function rules(): array
    {
        return [
            'empleado_id'        => 'required|integer|exists:empleados,id',
            'fecha'              => 'required|date',
            'monto'              => 'required|numeric|min:0.01',
            'observaciones'      => 'nullable|string|max:1000',
            'pagos'              => 'required|array|min:1',
            'pagos.*.forma_pago' => 'required|in:' . implode(',', FormaPago::codigosActivos()),
            'pagos.*.caja_id'    => 'required|exists:cajas,id',
            'pagos.*.monto'      => 'required|numeric|min:0.01',
            'pagos.*.referencia' => 'nullable|string|max:100',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'pagos.*.forma_pago' => 'forma de pago',
            'pagos.*.caja_id'    => 'caja',
            'pagos.*.monto'      => 'monto',
            'pagos.*.referencia' => 'referencia',
        ];
    }

    /** Suma de las líneas de pago cargadas. */
    public function getTotalPagosProperty(): float
    {
        return round(array_sum(array_map(
            fn ($p) => (float) str_replace(',', '.', (string) ($p['monto'] ?? 0)),
            $this->pagos
        )), 2);
    }

    /** Lo que falta asignar entre las líneas para llegar al monto del anticipo. */
    public function getRestoPagosProperty(): float
    {
        return round((float) str_replace(',', '.', $this->monto ?: '0') - $this->totalPagos, 2);
    }

    /** Línea de pago vacía, con la caja diaria del usuario preseleccionada. */
    private function nuevaLineaPago(string $monto = ''): array
    {
        $disponibles = $this->cajasDisponibles();

        return [
            'forma_pago' => FormaPago::codigoEfectivo(),
            'caja_id'    => $disponibles->firstWhere('tipo', 'diaria')?->id ?? $disponibles->first()?->id,
            'monto'      => $monto,
            'referencia' => '',
        ];
    }

    public function agregarPago(): void
    {
        $resto          = $this->restoPagos;
        $this->pagos[]  = $this->nuevaLineaPago($resto > 0 ? (string) $resto : '');
    }

    public function quitarPago(int $index): void
    {
        if (count($this->pagos) <= 1) return;

        unset($this->pagos[$index]);
        $this->pagos = array_values($this->pagos);
    }

    /** Asigna a esta línea lo que falta para cubrir el monto del anticipo. */
    public function completarResto(int $index): void
    {
        if (! isset($this->pagos[$index])) return;

        $actual = (float) str_replace(',', '.', (string) ($this->pagos[$index]['monto'] ?? 0));
        $nuevo  = round($actual + $this->restoPagos, 2);

        $this->pagos[$index]['monto'] = $nuevo > 0 ? (string) $nuevo : '';
    }

    /**
     * Con una sola línea de pago, el monto del anticipo la arrastra: el caso común
     * (un solo medio) no obliga a tipear el importe dos veces. Con un split manual
     * ya cargado no se toca nada.
     */
    public function updatedMonto(): void
    {
        if (count($this->pagos) === 1) {
            $this->pagos[0]['monto'] = $this->monto;
        }
    }

    /** Cajas abiertas que el usuario puede usar para pagar un anticipo (misma lógica que Gastos). */
    public function cajasDisponibles()
    {
        return Caja::with('user')
            ->where('estado', 'abierta')
            ->where(function ($q) {
                $q->where(function ($d) {
                    $d->where('tipo', 'diaria')->where('user_id', auth()->id());
                })->orWhereIn('tipo', ['general', 'banco']);
            })
            ->orderBy('tipo')
            ->get();
    }

    public function updatingSearch(): void         { $this->resetPage(); }
    public function updatingFilterEmpleado(): void { $this->resetPage(); }
    public function updatingFilterEstado(): void   { $this->resetPage(); }
    public function updatingFilterFormaPago(): void { $this->resetPage(); }
    public function updatingFechaDesde(): void     { $this->resetPage(); }
    public function updatingFechaHasta(): void     { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->empleado_id = $this->filterEmpleado;
        $this->fecha       = now()->format('Y-m-d');
        $this->showForm    = true;
    }

    public function openEdit(int $id): void
    {
        $anticipo = AnticipoEmpleado::with('pagos')->findOrFail($id);
        if ($anticipo->estado !== 'pendiente') return;

        $this->anticipoId    = $anticipo->id;
        $this->empleado_id   = $anticipo->empleado_id;
        $this->fecha         = $anticipo->fecha->format('Y-m-d');
        $this->monto         = (string) $anticipo->monto;
        $this->observaciones = $anticipo->observaciones ?? '';

        $this->pagos = $anticipo->pagos->map(fn ($p) => [
            'forma_pago' => $p->forma_pago,
            'caja_id'    => $p->caja_id,
            'monto'      => (string) $p->monto,
            'referencia' => $p->referencia ?? '',
        ])->all();

        // Anticipos anteriores a la tabla de líneas: se arma una desde los campos del cabezal.
        if (empty($this->pagos)) {
            $this->pagos = [[
                'forma_pago' => $anticipo->forma_pago,
                'caja_id'    => $anticipo->caja_id,
                'monto'      => (string) $anticipo->monto,
                'referencia' => $anticipo->referencia ?? '',
            ]];
        }

        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        // Las líneas tienen que cubrir exactamente el monto del anticipo.
        if (abs($this->restoPagos) > 0.009) {
            $this->addError('pagos', $this->restoPagos > 0
                ? 'Falta asignar $' . number_format($this->restoPagos, 2, ',', '.') . ' entre las formas de pago.'
                : 'Las formas de pago superan el monto del anticipo en $' . number_format(abs($this->restoPagos), 2, ',', '.') . '.');
            return;
        }

        // Cada línea sale de su propia caja y todas tienen que estar abiertas.
        $cajas = Caja::whereIn('id', array_column($this->pagos, 'caja_id'))->get()->keyBy('id');

        foreach ($this->pagos as $i => $pago) {
            $caja = $cajas->get($pago['caja_id']);
            if (! $caja || ! $caja->estaAbierta()) {
                $this->addError("pagos.{$i}.caja_id", 'La caja seleccionada no está abierta.');
                return;
            }
        }

        if ($this->anticipoId && AnticipoEmpleado::findOrFail($this->anticipoId)->estado !== 'pendiente') {
            session()->flash('error', 'Solo se pueden editar anticipos pendientes.');
            return;
        }

        $service = app(CajaService::class);

        // Denormalizado en el cabezal: la línea de mayor monto es la "principal".
        // Se conserva por retrocompatibilidad con los anticipos previos a esta tabla.
        $principal = collect($this->pagos)
            ->sortByDesc(fn ($p) => (float) str_replace(',', '.', (string) $p['monto']))
            ->first();

        $data = [
            'empleado_id'   => $this->empleado_id,
            'caja_id'       => $principal['caja_id'],
            'fecha'         => $this->fecha,
            'monto'         => $this->monto,
            'forma_pago'    => $principal['forma_pago'],
            'referencia'    => $principal['referencia'] ?: null,
            'observaciones' => $this->observaciones ?: null,
        ];

        DB::transaction(function () use ($data, $service, $cajas) {
            if ($this->anticipoId) {
                $anticipo = AnticipoEmpleado::findOrFail($this->anticipoId);
                // Revierte los egresos anteriores (todas las líneas) y regraba
                $service->revertirMovimientosDe(AnticipoEmpleado::class, $anticipo->id);
                $anticipo->pagos()->delete();
                $anticipo->update($data);
                session()->flash('success', 'Anticipo actualizado.');
            } else {
                $anticipo = AnticipoEmpleado::create($data);
                session()->flash('success', 'Anticipo registrado y descontado de la caja.');
            }

            $this->aplicarEnCaja($service, $anticipo, $cajas);
        });

        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * Persiste las líneas de pago y registra un egreso por línea en SU caja
     * (libro + contador). Efectivo y transferencia salen de cajas distintas.
     */
    private function aplicarEnCaja(CajaService $service, AnticipoEmpleado $anticipo, $cajas): void
    {
        $empleado = Empleado::find($anticipo->empleado_id);
        $fecha    = $anticipo->fecha instanceof \Carbon\Carbon
            ? $anticipo->fecha->toDateString()
            : (string) $anticipo->fecha;

        foreach ($this->pagos as $linea) {
            $monto = round((float) str_replace(',', '.', (string) $linea['monto']), 2);

            $pago = AnticipoPago::create([
                'anticipo_empleado_id' => $anticipo->id,
                'caja_id'              => $linea['caja_id'],
                'forma_pago'           => $linea['forma_pago'],
                'monto'                => $monto,
                'referencia'           => $linea['referencia'] ?: null,
            ]);

            $service->registrarMovimiento(
                $cajas->get($pago->caja_id), 'egreso', 'anticipo', $monto,
                [
                    'forma_pago'      => $pago->forma_pago,
                    'referencia_type' => AnticipoEmpleado::class,
                    'referencia_id'   => $anticipo->id,
                    'descripcion'     => 'Anticipo sueldo: ' . ($empleado?->nombre_completo ?? 'Empleado'),
                    'fecha'           => $fecha,
                ]
            );
        }
    }

    public function aprobar(int $id): void
    {
        $anticipo = AnticipoEmpleado::findOrFail($id);
        if ($anticipo->estado !== 'pendiente') return;

        $anticipo->update([
            'estado'           => 'aprobado',
            'aprobado_por'     => auth()->id(),
            'fecha_aprobacion' => now()->toDateString(),
        ]);

        session()->flash('success', 'Anticipo aprobado.');
    }

    public function openRechazar(int $id): void
    {
        $this->rechazandoId      = $id;
        $this->motivoRechazo     = '';
        $this->showRechazarModal = true;
    }

    public function rechazar(): void
    {
        $this->validate(['motivoRechazo' => 'required|string|min:5|max:500']);

        $anticipo = AnticipoEmpleado::findOrFail($this->rechazandoId);
        if ($anticipo->estado === 'pendiente') {
            // El anticipo ya descontó de la caja al crearse → al rechazar, se devuelve.
            app(CajaService::class)->revertirMovimientosDe(AnticipoEmpleado::class, $anticipo->id);
            $anticipo->update([
                'estado'         => 'rechazado',
                'aprobado_por'   => auth()->id(),
                'motivo_rechazo' => $this->motivoRechazo,
            ]);
            session()->flash('success', 'Anticipo rechazado. Se devolvió el monto a la caja.');
        }

        $this->showRechazarModal = false;
        $this->rechazandoId      = null;
        $this->motivoRechazo     = '';
    }

    public function liquidar(int $id): void
    {
        $anticipo = AnticipoEmpleado::findOrFail($id);
        if ($anticipo->estado !== 'aprobado') return;

        $anticipo->update(['estado' => 'liquidado']);
        session()->flash('success', 'Anticipo marcado como liquidado.');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        if (!$this->confirmingDelete) return;

        $anticipo = AnticipoEmpleado::findOrFail($this->confirmingDelete);
        if ($anticipo->estado !== 'pendiente') {
            session()->flash('error', 'Solo se pueden eliminar anticipos pendientes.');
            $this->confirmingDelete = null;
            return;
        }

        // Revierte los egresos en caja antes de dar de baja. Las líneas de pago se
        // borran con él: sin ellas ninguna suma futura por medio de pago puede
        // inflarse con las de un anticipo dado de baja (no hay UI de restauración).
        app(CajaService::class)->revertirMovimientosDe(AnticipoEmpleado::class, $anticipo->id);
        $anticipo->pagos()->delete();
        $anticipo->delete();
        $this->confirmingDelete = null;
        session()->flash('success', 'Anticipo eliminado.');
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = null;
    }

    private function resetForm(): void
    {
        $this->anticipoId    = null;
        $this->empleado_id   = null;
        $this->fecha         = '';
        $this->monto         = '';
        $this->observaciones = '';
        $this->pagos         = [$this->nuevaLineaPago()];
        $this->resetErrorBag();
    }

    /**
     * Filtros comunes a la grilla y al totalizador, para que el total refleje
     * exactamente el mismo conjunto que la tabla.
     */
    private function baseQuery()
    {
        return AnticipoEmpleado::query()
            ->when($this->filterEmpleado, fn ($q) => $q->where('empleado_id', $this->filterEmpleado))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            // Un anticipo mixto tiene varias formas de pago: se busca en las líneas,
            // no en el campo denormalizado del cabezal (que es solo la principal).
            ->when($this->filterFormaPago, fn ($q) => $q->whereHas('pagos', fn ($p) =>
                $p->where('forma_pago', $this->filterFormaPago)
            ))
            ->when($this->fechaDesde, fn ($q) => $q->where('fecha', '>=', $this->fechaDesde))
            ->when($this->fechaHasta, fn ($q) => $q->where('fecha', '<=', $this->fechaHasta))
            ->when($this->search, fn ($q) => $q->whereHas('empleado', fn ($q) =>
                $q->where('nombre', 'like', "%{$this->search}%")
                  ->orWhere('apellido', 'like', "%{$this->search}%")
            ));
    }

    public function render()
    {
        return view('livewire.anticipos.index', [
            'anticipos'        => $this->baseQuery()
                                        ->with(['empleado', 'aprobador', 'pagos.caja'])
                                        ->latest('fecha')
                                        ->paginate(20),
            'empleados'        => Empleado::where('estado', 'A')->orderBy('apellido')->orderBy('nombre')->get(),
            'totalMonto'       => $this->baseQuery()->sum('monto'),
            'empleadoFiltrado' => $this->filterEmpleado ? Empleado::find($this->filterEmpleado) : null,
            'formasPago'       => FormaPago::activosParaSelect(),
            'cajas'            => $this->cajasDisponibles(),
        ]);
    }
}
