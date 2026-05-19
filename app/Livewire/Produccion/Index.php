<?php

namespace App\Livewire\Produccion;

use App\Models\Produccion;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Producción')]
class Index extends Component
{
    use WithPagination;

    public string $search      = '';
    public string $filterDesde = '';
    public string $filterHasta = '';

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        $this->filterDesde = now()->startOfMonth()->toDateString();
        $this->filterHasta = now()->toDateString();
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function crearNueva(): void
    {
        $nro = (Produccion::max('nro_produccion') ?? 0) + 1;

        $produccion = Produccion::create([
            'user_id'         => auth()->id(),
            'nro_produccion'  => $nro,
            'fecha'           => now()->toDateString(),
            'tipo_produccion' => 'troceo',
            'estado'          => 'abierta',
        ]);

        $this->redirect(route('produccion.detalle', $produccion), navigate: true);
    }

    public function confirmDelete(int $id): void { $this->confirmingDelete = $id; }

    public function delete(): void
    {
        $p = Produccion::findOrFail($this->confirmingDelete);
        if ($p->estado === 'cerrada') {
            session()->flash('error', 'No se puede eliminar una producción cerrada.');
            $this->confirmingDelete = null;
            return;
        }
        $p->delete();
        $this->confirmingDelete = null;
        session()->flash('success', 'Producción eliminada.');
    }

    public function render()
    {
        $producciones = Produccion::with(['empleado', 'insumos.articulo', 'productos'])
            ->when($this->search, fn ($q) => $q->whereHas('insumos.articulo', fn ($a) =>
                $a->where('descripcion', 'like', "%{$this->search}%")
            ))
            ->when($this->filterDesde, fn ($q) => $q->where('fecha', '>=', $this->filterDesde))
            ->when($this->filterHasta, fn ($q) => $q->where('fecha', '<=', $this->filterHasta))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(25);

        return view('livewire.produccion.index', [
            'producciones' => $producciones,
        ]);
    }
}
