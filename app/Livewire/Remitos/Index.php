<?php

namespace App\Livewire\Remitos;

use App\Models\Empleado;
use App\Models\Remito;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Remitos')]
class Index extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterDesde  = '';
    public string $filterHasta  = '';
    public string $filterEstado = '';

    // Formulario
    public bool   $showForm    = false;
    public ?int   $remitoId    = null;
    public ?int   $empleado_id = null;
    public string $fecha       = '';
    public float  $total_peso  = 0;
    public float  $total_cajas = 0;
    public string $estado      = 'abierto';
    public string $observacion = '';

    public ?int $confirmingDelete = null;

    protected $rules = [
        'fecha'       => 'required|date',
        'total_peso'  => 'required|numeric|min:0',
        'total_cajas' => 'required|numeric|min:0',
    ];

    public function mount(): void
    {
        $this->filterDesde = now()->startOfMonth()->toDateString();
        $this->filterHasta = now()->toDateString();
        $this->fecha       = now()->toDateString();
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $r = Remito::findOrFail($id);
        $this->remitoId    = $r->id;
        $this->empleado_id = $r->empleado_id;
        $this->fecha       = $r->fecha->toDateString();
        $this->total_peso  = (float) $r->total_peso;
        $this->total_cajas = (float) $r->total_cajas;
        $this->estado      = $r->estado;
        $this->observacion = $r->observacion ?? '';
        $this->showForm    = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'empleado_id' => $this->empleado_id,
            'user_id'     => auth()->id(),
            'fecha'       => $this->fecha,
            'total_peso'  => $this->total_peso,
            'total_cajas' => $this->total_cajas,
            'estado'      => $this->estado,
            'observacion' => $this->observacion ?: null,
        ];

        if ($this->remitoId) {
            Remito::findOrFail($this->remitoId)->update($data);
        } else {
            $nro = (Remito::max('nro_remito') ?? 0) + 1;
            Remito::create(array_merge($data, ['nro_remito' => $nro]));
        }

        $this->resetForm();
        session()->flash('success', 'Remito guardado.');
    }

    public function confirmDelete(int $id): void { $this->confirmingDelete = $id; }

    public function delete(): void
    {
        Remito::findOrFail($this->confirmingDelete)->delete();
        $this->confirmingDelete = null;
        session()->flash('success', 'Remito eliminado.');
    }

    public function resetForm(): void
    {
        $this->remitoId    = null;
        $this->empleado_id = null;
        $this->fecha       = now()->toDateString();
        $this->total_peso  = 0;
        $this->total_cajas = 0;
        $this->estado      = 'abierto';
        $this->observacion = '';
        $this->showForm    = false;
        $this->resetValidation();
    }

    public function render()
    {
        $remitos = Remito::with(['empleado', 'user'])
            ->when($this->search, fn ($q) => $q->where('nro_remito', 'like', "%{$this->search}%")
                ->orWhere('observacion', 'like', "%{$this->search}%"))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->when($this->filterDesde, fn ($q) => $q->where('fecha', '>=', $this->filterDesde))
            ->when($this->filterHasta, fn ($q) => $q->where('fecha', '<=', $this->filterHasta))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(25);

        return view('livewire.remitos.index', [
            'remitos'   => $remitos,
            'empleados' => Empleado::where('estado', 'A')->orderBy('nombre')->get(),
        ]);
    }
}
