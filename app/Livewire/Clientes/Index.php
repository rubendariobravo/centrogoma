<?php

namespace App\Livewire\Clientes;

use App\Models\Cliente;
use App\Models\TipoIva;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Clientes')]
class Index extends Component
{
    use WithPagination;

    public string $search         = '';
    public string $filterEstado   = 'A';
    public string $filterTipoIva  = '';
    public string $filterMayorista = '';
    public string $sortBy         = 'razon_social';
    public string $sortDir        = 'asc';

    public ?int $confirmingDelete = null;

    protected $queryString = [
        'search'          => ['except' => ''],
        'filterEstado'    => ['except' => 'A'],
        'filterTipoIva'   => ['except' => ''],
        'filterMayorista' => ['except' => ''],
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
            Cliente::findOrFail($this->confirmingDelete)->delete();
            $this->confirmingDelete = null;
            session()->flash('success', 'Cliente eliminado correctamente.');
        }
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = null;
    }

    public function render()
    {
        $clientes = Cliente::query()
            ->with(['tipoIva', 'vendedor'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('razon_social', 'like', "%{$this->search}%")
                  ->orWhere('nombre_fantasia', 'like', "%{$this->search}%")
                  ->orWhere('cuit', 'like', "%{$this->search}%")
                  ->orWhere('dni', 'like', "%{$this->search}%");
            }))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->when($this->filterTipoIva, fn ($q) => $q->where('tipoiva_id', $this->filterTipoIva))
            ->when($this->filterMayorista !== '', fn ($q) => $q->where('es_mayorista', $this->filterMayorista === '1'))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(20);

        return view('livewire.clientes.index', [
            'clientes' => $clientes,
            'tiposIva' => TipoIva::where('estado', 'A')->orderBy('descripcion')->get(),
        ]);
    }
}
