<?php

namespace App\Livewire\Listas;

use App\Models\ListaPrecio;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Listas de precios')]
class Index extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterTipo   = '';
    public string $filterActivo = '1';
    public string $sortBy       = 'orden';
    public string $sortDir      = 'asc';

    public ?int $confirmingDelete = null;

    protected $queryString = [
        'search'       => ['except' => ''],
        'filterTipo'   => ['except' => ''],
        'filterActivo' => ['except' => '1'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sort(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy  = $field;
            $this->sortDir = 'asc';
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDelete = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = null;
    }

    public function delete(): void
    {
        if (! $this->confirmingDelete) return;

        $lista = ListaPrecio::findOrFail($this->confirmingDelete);

        if ($lista->es_default) {
            session()->flash('error', 'No se puede eliminar la lista marcada como default.');
            $this->confirmingDelete = null;
            return;
        }

        // Validacion: si hay clientes o tramos asociados, no permitir borrar.
        if ($lista->clientes()->exists() || $lista->articuloPrecios()->exists()) {
            session()->flash('error', 'La lista tiene clientes o precios asociados. Desactivela en lugar de eliminarla.');
            $this->confirmingDelete = null;
            return;
        }

        $lista->delete();
        $this->confirmingDelete = null;
        session()->flash('success', 'Lista de precios eliminada.');
    }

    public function render()
    {
        $listas = ListaPrecio::query()
            ->withCount(['articuloPrecios', 'clientes'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('codigo', 'like', "%{$this->search}%")
                  ->orWhere('nombre', 'like', "%{$this->search}%")
                  ->orWhere('descripcion', 'like', "%{$this->search}%");
            }))
            ->when($this->filterTipo, fn ($q) => $q->where('tipo', $this->filterTipo))
            ->when($this->filterActivo !== '', fn ($q) => $q->where('activo', $this->filterActivo === '1'))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(20);

        return view('livewire.listas.index', [
            'listas' => $listas,
        ]);
    }
}
