<?php

namespace App\Livewire\Marcas;

use App\Models\Marca;
use App\Models\Proveedor;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Marcas')]
class Index extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterEstado = 'A';

    // Form state
    public bool    $showForm    = false;
    public ?int    $marcaId     = null;
    public string  $descripcion = '';
    public float   $comision    = 0;
    public ?int    $proveedor_id = null;
    public string  $estado      = 'A';

    public ?int $confirmingDelete = null;

    protected function rules(): array
    {
        return [
            'descripcion'  => 'required|string|max:100',
            'comision'     => 'required|numeric|min:0|max:100',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'estado'       => 'required|in:A,I',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $marca = Marca::findOrFail($id);
        $this->marcaId      = $marca->id;
        $this->descripcion  = $marca->descripcion;
        $this->comision     = (float) $marca->comision;
        $this->proveedor_id = $marca->proveedor_id;
        $this->estado       = $marca->estado;
        $this->showForm     = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'descripcion'  => $this->descripcion,
            'comision'     => $this->comision,
            'proveedor_id' => $this->proveedor_id ?: null,
            'estado'       => $this->estado,
        ];

        if ($this->marcaId) {
            Marca::findOrFail($this->marcaId)->update($data);
            session()->flash('success', 'Marca actualizada.');
        } else {
            Marca::create($data);
            session()->flash('success', 'Marca creada.');
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        if ($this->confirmingDelete) {
            Marca::findOrFail($this->confirmingDelete)->delete();
            $this->confirmingDelete = null;
            session()->flash('success', 'Marca eliminada.');
        }
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = null;
    }

    private function resetForm(): void
    {
        $this->marcaId      = null;
        $this->descripcion  = '';
        $this->comision     = 0;
        $this->proveedor_id = null;
        $this->estado       = 'A';
        $this->resetErrorBag();
    }

    public function render()
    {
        $marcas = Marca::query()
            ->with('proveedor')
            ->when($this->search, fn ($q) => $q->where('descripcion', 'like', "%{$this->search}%"))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->orderBy('descripcion')
            ->paginate(20);

        return view('livewire.marcas.index', [
            'marcas'      => $marcas,
            'proveedores' => Proveedor::where('estado', 'A')->orderBy('razon_social')->get(),
        ]);
    }
}
