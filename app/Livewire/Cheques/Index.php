<?php

namespace App\Livewire\Cheques;

use App\Models\Banco;
use App\Models\Caja;
use App\Models\Cheque;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Services\Cheque\ChequeService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Cheques')]
class Index extends Component
{
    use WithPagination;

    // Filtros
    #[Url] public string $filterTipo   = 'tercero';
    #[Url] public string $filterEstado = '';
    public ?int   $filterBanco   = null;
    public string $search        = '';
    public string $filterDesde   = '';
    public string $filterHasta   = '';

    // Formulario alta / edición
    public bool    $showForm        = false;
    public ?int    $chequeId        = null;
    public string  $tipo            = 'tercero';
    public ?int    $banco_id        = null;
    public string  $serie           = '';
    public string  $nro_cheque      = '';
    public string  $librador_nombre = '';
    public string  $librador_cuit   = '';
    public bool    $es_diferido     = true;
    public bool    $es_echeq        = false;
    public string  $fecha_emision   = '';
    public string  $fecha_recepcion = '';
    public string  $fecha_pago      = '';
    public ?float  $monto           = null;
    public ?int    $cliente_id      = null;
    public ?int    $proveedor_id    = null;
    public string  $observacion     = '';

    // Modal de acción de estado
    public ?int    $actionChequeId = null;
    public string  $actionType     = '';      // depositar | acreditar | debitar | rechazar | anular
    public ?int    $actionCajaId   = null;
    public string  $actionObs      = '';

    // Panel de historial
    public ?int   $historialChequeId = null;
    public array  $historial         = [];

    public function mount(): void
    {
        $this->fecha_recepcion = now()->toDateString();
        $this->fecha_emision   = now()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'tipo'            => 'required|in:tercero,propio',
            'banco_id'        => 'required|exists:bancos,id',
            'serie'           => 'nullable|string|max:10',
            'nro_cheque'      => 'required|string|max:30',
            'librador_nombre' => 'nullable|string|max:150',
            'librador_cuit'   => 'nullable|string|max:13',
            'es_diferido'     => 'boolean',
            'es_echeq'        => 'boolean',
            'fecha_emision'   => 'nullable|date',
            'fecha_recepcion' => 'nullable|date',
            'fecha_pago'      => 'required|date',
            'monto'           => 'required|numeric|min:0.01',
            'cliente_id'      => 'nullable|exists:clientes,id',
            'proveedor_id'    => 'nullable|exists:proveedores,id',
            'observacion'     => 'nullable|string|max:255',
        ];
    }

    /** Cajas banco/general abiertas donde se acreditan/depositan los cheques. */
    public function cajasBanco()
    {
        return Caja::where('estado', 'abierta')
            ->whereIn('tipo', ['banco', 'general'])
            ->orderBy('tipo')
            ->get();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterTipo(): void { $this->resetPage(); }
    public function updatingFilterEstado(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->tipo = $this->filterTipo === 'propio' ? 'propio' : 'tercero';
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $c = Cheque::findOrFail($id);
        if (! in_array($c->estado, ['cartera', 'emitido'], true)) {
            session()->flash('error', 'Solo se puede editar un cheque en su estado inicial.');
            return;
        }
        $this->chequeId        = $c->id;
        $this->tipo            = $c->tipo;
        $this->banco_id        = $c->banco_id;
        $this->serie           = $c->serie ?? '';
        $this->nro_cheque      = $c->nro_cheque ?? '';
        $this->librador_nombre = $c->librador_nombre ?? '';
        $this->librador_cuit   = $c->librador_cuit ?? '';
        $this->es_diferido     = (bool) $c->es_diferido;
        $this->es_echeq        = (bool) $c->es_echeq;
        $this->fecha_emision   = $c->fecha_emision?->toDateString() ?? '';
        $this->fecha_recepcion = $c->fecha_recepcion?->toDateString() ?? '';
        $this->fecha_pago      = $c->fecha_pago?->toDateString() ?? '';
        $this->monto           = (float) $c->monto;
        $this->cliente_id      = $c->cliente_id;
        $this->proveedor_id    = $c->proveedor_id;
        $this->observacion     = $c->observacion ?? '';
        $this->showForm        = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'tipo'            => $this->tipo,
            'banco_id'        => $this->banco_id,
            'serie'           => $this->serie ?: null,
            'nro_cheque'      => $this->nro_cheque,
            'librador_nombre' => $this->librador_nombre ?: null,
            'librador_cuit'   => $this->librador_cuit ?: null,
            'es_diferido'     => $this->es_diferido,
            'es_echeq'        => $this->es_echeq,
            'fecha_emision'   => $this->fecha_emision ?: null,
            'fecha_recepcion' => $this->fecha_recepcion ?: null,
            'fecha_pago'      => $this->fecha_pago,
            'monto'           => round((float) $this->monto, 2),
            'cliente_id'      => $this->tipo === 'tercero' ? $this->cliente_id : null,
            'proveedor_id'    => $this->tipo === 'propio' ? $this->proveedor_id : null,
            'observacion'     => $this->observacion ?: null,
        ];

        if ($this->chequeId) {
            Cheque::findOrFail($this->chequeId)->update($data);
            session()->flash('success', 'Cheque actualizado.');
        } else {
            $data['estado']  = $this->tipo === 'propio' ? 'emitido' : 'cartera';
            $data['user_id'] = auth()->id();
            $cheque = Cheque::create($data);
            app(ChequeService::class)->log(
                $cheque,
                $this->tipo === 'propio' ? 'emision' : 'recepcion',
                'Alta manual del cheque'
            );
            session()->flash('success', 'Cheque registrado.');
        }

        $this->resetForm();
    }

    /* ---------- Acciones de estado ---------- */

    public function openAction(int $id, string $type): void
    {
        $this->actionChequeId = $id;
        $this->actionType     = $type;
        $this->actionObs      = '';
        $cheque = Cheque::find($id);
        // Pre-selecciona caja banco para depositar/acreditar/debitar
        $this->actionCajaId = $cheque?->caja_id ?? $this->cajasBanco()->first()?->id;
    }

    public function cancelAction(): void
    {
        $this->reset(['actionChequeId', 'actionType', 'actionCajaId', 'actionObs']);
    }

    public function confirmAction(): void
    {
        $cheque  = Cheque::findOrFail($this->actionChequeId);
        $service = app(ChequeService::class);

        try {
            match ($this->actionType) {
                'depositar' => $service->depositar($cheque, $this->actionCajaId ?: null, $this->actionObs ?: null),
                'acreditar' => $service->acreditar($cheque, $this->actionCajaId ?: null, $this->actionObs ?: null),
                'debitar'   => $service->debitar($cheque, $this->actionCajaId ?: null, $this->actionObs ?: null),
                'rechazar'  => $service->rechazar($cheque, $this->actionObs ?: null),
                'anular'    => $service->anular($cheque, $this->actionObs ?: null),
                default     => null,
            };
            session()->flash('success', 'Cheque actualizado correctamente.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->cancelAction();
    }

    /* ---------- Historial ---------- */

    public function verHistorial(int $id): void
    {
        if ($this->historialChequeId === $id) {
            $this->cerrarHistorial();
            return;
        }
        $cheque = Cheque::with('movimientos.user')->findOrFail($id);
        $this->historialChequeId = $id;
        $this->historial = $cheque->movimientos->map(fn ($m) => [
            'fecha'     => $m->fecha?->format('d/m/Y'),
            'tipo_mov'  => ucfirst($m->tipo_mov),
            'estado'    => Cheque::ESTADO_LABELS[$m->estado_resultante] ?? $m->estado_resultante,
            'monto'     => number_format((float) $m->monto, 2, ',', '.'),
            'usuario'   => $m->user?->name ?? '—',
            'obs'       => $m->observacion,
        ])->toArray();
    }

    public function cerrarHistorial(): void
    {
        $this->historialChequeId = null;
        $this->historial = [];
    }

    public function resetForm(): void
    {
        $this->reset([
            'chequeId', 'banco_id', 'serie', 'nro_cheque', 'librador_nombre',
            'librador_cuit', 'monto', 'cliente_id', 'proveedor_id', 'observacion',
        ]);
        $this->tipo            = 'tercero';
        $this->es_diferido     = true;
        $this->es_echeq        = false;
        $this->fecha_emision   = now()->toDateString();
        $this->fecha_recepcion = now()->toDateString();
        $this->fecha_pago      = '';
        $this->showForm        = false;
        $this->resetValidation();
    }

    public function render()
    {
        $query = Cheque::with(['banco', 'cliente', 'proveedor', 'caja'])
            ->when($this->filterTipo, fn ($q) => $q->where('tipo', $this->filterTipo))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->when($this->filterBanco, fn ($q) => $q->where('banco_id', $this->filterBanco))
            ->when($this->filterDesde, fn ($q) => $q->where('fecha_pago', '>=', $this->filterDesde))
            ->when($this->filterHasta, fn ($q) => $q->where('fecha_pago', '<=', $this->filterHasta))
            ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                ->where('nro_cheque', 'like', "%{$this->search}%")
                ->orWhere('librador_nombre', 'like', "%{$this->search}%")));

        $totalFiltrado = (clone $query)->sum('monto');

        $cheques = $query->orderBy('fecha_pago')->paginate(25);

        return view('livewire.cheques.index', [
            'cheques'       => $cheques,
            'totalFiltrado' => $totalFiltrado,
            'bancos'        => Banco::where('activo', true)->orderBy('descripcion')->get(),
            'clientes'      => Cliente::where('estado', 'A')->orderBy('razon_social')->get(),
            'proveedores'   => Proveedor::where('estado', 'A')->orderBy('razon_social')->get(),
            'cajasBanco'    => $this->cajasBanco(),
        ]);
    }
}
