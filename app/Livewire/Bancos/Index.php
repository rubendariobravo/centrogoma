<?php

namespace App\Livewire\Bancos;

use App\Models\Banco;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Bancos')]
class Index extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterEstado = '1';

    public bool    $showForm    = false;
    public ?int    $bancoId     = null;
    public string  $descripcion = '';
    public string  $cuit        = '';
    public bool    $activo      = true;

    public ?int $confirmingDelete = null;

    protected function rules(): array
    {
        return [
            'descripcion' => 'required|string|max:100',
            'cuit'        => 'nullable|string|max:13',
            'activo'      => 'boolean',
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
        $b = Banco::findOrFail($id);
        $this->bancoId     = $b->id;
        $this->descripcion = $b->descripcion;
        $this->cuit        = $b->cuit ?? '';
        $this->activo      = (bool) $b->activo;
        $this->showForm    = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'descripcion' => $this->descripcion,
            'cuit'        => $this->cuit ?: null,
            'activo'      => $this->activo,
        ];

        if ($this->bancoId) {
            Banco::findOrFail($this->bancoId)->update($data);
            session()->flash('success', 'Banco actualizado.');
        } else {
            Banco::create($data);
            session()->flash('success', 'Banco creado.');
        }

        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        if ($this->confirmingDelete) {
            $banco = Banco::withCount('cheques')->findOrFail($this->confirmingDelete);
            if ($banco->cheques_count > 0) {
                // No borrar bancos con cheques; solo desactivar.
                $banco->update(['activo' => false]);
                session()->flash('success', 'El banco tiene cheques asociados: se desactivó en lugar de eliminarse.');
            } else {
                $banco->delete();
                session()->flash('success', 'Banco eliminado.');
            }
            $this->confirmingDelete = null;
        }
    }

    public function resetForm(): void
    {
        $this->bancoId     = null;
        $this->descripcion = '';
        $this->cuit        = '';
        $this->activo      = true;
        $this->showForm    = false;
        $this->resetValidation();
    }

    public function render()
    {
        $bancos = Banco::query()
            ->withCount('cheques')
            ->when($this->search, fn ($q) => $q->where('descripcion', 'like', "%{$this->search}%"))
            ->when($this->filterEstado !== '', fn ($q) => $q->where('activo', $this->filterEstado === '1'))
            ->orderBy('descripcion')
            ->paginate(20);

        return view('livewire.bancos.index', ['bancos' => $bancos]);
    }
}
