<?php

namespace App\Livewire\Rubros;

use App\Models\Rubro;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Rubros')]
class Index extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterEstado = 'A';

    // Form state
    public bool    $showForm    = false;
    public ?int    $rubroId     = null;
    public string  $descripcion = '';
    public float   $alicuota_iva = 21;
    public bool    $es_mayorista = false;
    public string  $estado      = 'A';

    public ?int $confirmingDelete = null;

    protected function rules(): array
    {
        return [
            'descripcion'  => 'required|string|max:100',
            'alicuota_iva' => 'required|numeric|in:0,10.5,21,27',
            'es_mayorista' => 'boolean',
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
        $rubro = Rubro::findOrFail($id);
        $this->rubroId      = $rubro->id;
        $this->descripcion  = $rubro->descripcion;
        $this->alicuota_iva = (float) $rubro->alicuota_iva;
        $this->es_mayorista = (bool) $rubro->es_mayorista;
        $this->estado       = $rubro->estado;
        $this->showForm     = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'descripcion'  => $this->descripcion,
            'alicuota_iva' => $this->alicuota_iva,
            'es_mayorista' => $this->es_mayorista,
            'estado'       => $this->estado,
        ];

        if ($this->rubroId) {
            Rubro::findOrFail($this->rubroId)->update($data);
            session()->flash('success', 'Rubro actualizado.');
        } else {
            Rubro::create($data);
            session()->flash('success', 'Rubro creado.');
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
            Rubro::findOrFail($this->confirmingDelete)->delete();
            $this->confirmingDelete = null;
            session()->flash('success', 'Rubro eliminado.');
        }
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = null;
    }

    private function resetForm(): void
    {
        $this->rubroId      = null;
        $this->descripcion  = '';
        $this->alicuota_iva = 21;
        $this->es_mayorista = false;
        $this->estado       = 'A';
        $this->resetErrorBag();
    }

    public function render()
    {
        $rubros = Rubro::query()
            ->when($this->search, fn ($q) => $q->where('descripcion', 'like', "%{$this->search}%"))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->orderBy('descripcion')
            ->paginate(20);

        return view('livewire.rubros.index', ['rubros' => $rubros]);
    }
}
