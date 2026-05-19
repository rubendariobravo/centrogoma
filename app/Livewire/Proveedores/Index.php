<?php

namespace App\Livewire\Proveedores;

use App\Models\Proveedor;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Proveedores')]
class Index extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterEstado = 'A';
    public string $sortBy       = 'razon_social';
    public string $sortDir      = 'asc';

    public ?int $confirmingDelete = null;

    protected $queryString = [
        'search'       => ['except' => ''],
        'filterEstado' => ['except' => 'A'],
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

    public function delete(): void
    {
        if ($this->confirmingDelete) {
            Proveedor::findOrFail($this->confirmingDelete)->delete();
            $this->confirmingDelete = null;
            session()->flash('success', 'Proveedor eliminado correctamente.');
        }
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = null;
    }

    public function render()
    {
        $proveedores = Proveedor::query()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('razon_social', 'like', "%{$this->search}%")
                  ->orWhere('nombre_fantasia', 'like', "%{$this->search}%")
                  ->orWhere('cuit', 'like', "%{$this->search}%");
            }))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(20);

        return view('livewire.proveedores.index', ['proveedores' => $proveedores]);
    }
}
