<?php

namespace App\Livewire\Descartes;

use App\Models\Articulo;
use App\Models\Descarte;
use App\Models\Empleado;
use App\Models\MovimientoArticulo;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Descartes')]
class Index extends Component
{
    use WithPagination;

    public string $search      = '';
    public string $filterDesde = '';
    public string $filterHasta = '';

    // Formulario
    public bool   $showForm    = false;
    public ?int   $descarteId  = null;
    public ?int   $articulo_id = null;
    public ?int   $empleado_id = null;
    public string $fecha       = '';
    public float  $cantidad    = 0;
    public string $motivo      = '';
    public string $observacion = '';

    public ?int $confirmingDelete = null;

    protected $rules = [
        'articulo_id' => 'required|exists:articulos,id',
        'fecha'       => 'required|date',
        'cantidad'    => 'required|numeric|min:0.001',
    ];

    public function mount(): void
    {
        $this->filterDesde = now()->startOfMonth()->toDateString();
        $this->filterHasta = now()->toDateString();
        $this->fecha       = now()->toDateString();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $d = Descarte::findOrFail($id);
        $this->descarteId  = $d->id;
        $this->articulo_id = $d->articulo_id;
        $this->empleado_id = $d->empleado_id;
        $this->fecha       = $d->fecha->toDateString();
        $this->cantidad    = $d->cantidad;
        $this->motivo      = $d->motivo ?? '';
        $this->observacion = $d->observacion ?? '';
        $this->showForm    = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'articulo_id' => $this->articulo_id,
            'empleado_id' => $this->empleado_id,
            'user_id'     => auth()->id(),
            'fecha'       => $this->fecha,
            'cantidad'    => $this->cantidad,
            'motivo'      => $this->motivo ?: null,
            'observacion' => $this->observacion ?: null,
        ];

        if ($this->descarteId) {
            Descarte::findOrFail($this->descarteId)->update($data);
        } else {
            $descarte = Descarte::create($data);

            $art = Articulo::find($this->articulo_id);
            if ($art?->modifica_stock) {
                $art->decrement('stock', $this->cantidad);
                MovimientoArticulo::create([
                    'articulo_id'     => $art->id,
                    'tipo'            => 'salida',
                    'cantidad'        => $this->cantidad,
                    'fecha'           => $this->fecha,
                    'referencia_type' => Descarte::class,
                    'referencia_id'   => $descarte->id,
                    'observacion'     => "Descarte" . ($this->motivo ? ": {$this->motivo}" : ''),
                ]);
            }
        }

        $this->resetForm();
        session()->flash('success', 'Descarte registrado.');
    }

    public function confirmDelete(int $id): void { $this->confirmingDelete = $id; }

    public function delete(): void
    {
        Descarte::findOrFail($this->confirmingDelete)->delete();
        $this->confirmingDelete = null;
        session()->flash('success', 'Descarte eliminado.');
    }

    public function resetForm(): void
    {
        $this->descarteId  = null;
        $this->articulo_id = null;
        $this->empleado_id = null;
        $this->fecha       = now()->toDateString();
        $this->cantidad    = 0;
        $this->motivo      = '';
        $this->observacion = '';
        $this->showForm    = false;
        $this->resetValidation();
    }

    public function render()
    {
        $descartes = Descarte::with(['articulo', 'empleado'])
            ->when($this->search, fn ($q) => $q
                ->whereHas('articulo', fn ($a) => $a->where('descripcion', 'like', "%{$this->search}%"))
                ->orWhere('motivo', 'like', "%{$this->search}%")
            )
            ->when($this->filterDesde, fn ($q) => $q->where('fecha', '>=', $this->filterDesde))
            ->when($this->filterHasta, fn ($q) => $q->where('fecha', '<=', $this->filterHasta))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(25);

        return view('livewire.descartes.index', [
            'descartes' => $descartes,
            'articulos' => Articulo::where('estado', 'A')->orderBy('descripcion')->get(),
            'empleados' => Empleado::where('estado', 'A')->orderBy('nombre')->get(),
        ]);
    }
}
