<?php

namespace App\Livewire\Presupuestos;

use App\Models\Cliente;
use App\Models\Presupuesto;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Presupuestos')]
class Index extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterEstado = '';
    public string $filterDesde  = '';
    public string $filterHasta  = '';
    public ?int   $filterCliente = null;
    public ?int   $confirmingAnular = null;

    protected $queryString = [
        'search'         => ['except' => ''],
        'filterEstado'   => ['except' => ''],
        'filterDesde'    => ['except' => ''],
        'filterHasta'    => ['except' => ''],
        'filterCliente'  => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->filterDesde = now()->startOfMonth()->toDateString();
        $this->filterHasta = now()->toDateString();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function anular(int $id): void
    {
        $presupuesto = Presupuesto::findOrFail($id);
        if (in_array($presupuesto->estado, ['A', 'V'])) {
            $presupuesto->update(['estado' => 'N']);
            session()->flash('success', "Presupuesto #{$presupuesto->nro_presupuesto} anulado.");
        }
        $this->confirmingAnular = null;
    }

    public function convertirAVenta(int $id): void
    {
        $presupuesto = Presupuesto::findOrFail($id);
        if (! in_array($presupuesto->estado, ['A', 'V'])) {
            session()->flash('error', 'Solo se pueden convertir presupuestos activos o vencidos.');
            return;
        }
        $this->redirect(route('ventas.create') . '?presupuesto_id=' . $id, navigate: true);
    }

    public function render()
    {
        $presupuestos = Presupuesto::query()
            ->with(['cliente', 'user'])
            ->when($this->search, fn ($q) => $q
                ->whereHas('cliente', fn ($c) =>
                    $c->where('razon_social', 'like', "%{$this->search}%")
                      ->orWhere('nombre_fantasia', 'like', "%{$this->search}%")
                )->orWhere('nro_presupuesto', 'like', "%{$this->search}%")
            )
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->when($this->filterCliente, fn ($q) => $q->where('cliente_id', $this->filterCliente))
            ->when($this->filterDesde, fn ($q) => $q->where('fecha', '>=', $this->filterDesde))
            ->when($this->filterHasta, fn ($q) => $q->where('fecha', '<=', $this->filterHasta))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(25);

        $totalPeriodo = Presupuesto::query()
            ->whereNotIn('estado', ['N'])
            ->when($this->filterDesde, fn ($q) => $q->where('fecha', '>=', $this->filterDesde))
            ->when($this->filterHasta, fn ($q) => $q->where('fecha', '<=', $this->filterHasta))
            ->sum('total');

        return view('livewire.presupuestos.index', [
            'presupuestos' => $presupuestos,
            'totalPeriodo' => $totalPeriodo,
            'clientes'     => Cliente::where('estado', 'A')->orderBy('razon_social')->get(),
        ]);
    }
}
