<?php

namespace App\Livewire\Articulos;

use App\Models\Articulo;
use App\Models\Rubro;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Artículos')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterRubro = '';
    public string $filterEstado = 'A';
    public string $filterTipo = '';
    public string $sortBy = 'descripcion';
    public string $sortDir = 'asc';
    public ?int $confirmingDelete = null;

    protected $queryString = [
        'search'       => ['except' => ''],
        'filterRubro'  => ['except' => ''],
        'filterEstado' => ['except' => 'A'],
        'filterTipo'   => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterRubro(): void
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

    public function delete(): void
    {
        if ($this->confirmingDelete) {
            Articulo::findOrFail($this->confirmingDelete)->delete();
            $this->confirmingDelete = null;
            session()->flash('success', 'Artículo eliminado correctamente.');
        }
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = null;
    }

    public function render()
    {
        $articulos = Articulo::query()
            ->with(['marca', 'rubro'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('descripcion', 'like', "%{$this->search}%")
                  ->orWhere('codigo', 'like', "%{$this->search}%");
            }))
            ->when($this->filterRubro, fn ($q) => $q->where('rubro_id', $this->filterRubro))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->when($this->filterTipo, fn ($q) => $q->where('tipo_venta', $this->filterTipo))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(20);

        return view('livewire.articulos.index', [
            'articulos' => $articulos,
            'rubros'    => Rubro::where('estado', 'A')->orderBy('descripcion')->get(),
        ]);
    }
}
