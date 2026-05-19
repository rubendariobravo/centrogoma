<?php

namespace App\Livewire\Pesadas;

use App\Models\Articulo;
use App\Models\MovimientoArticulo;
use App\Models\Pesada;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Pesadas')]
class Index extends Component
{
    use WithPagination;

    public string $search      = '';
    public string $filterDesde = '';
    public string $filterHasta = '';

    // Formulario
    public bool   $showForm    = false;
    public ?int   $articulo_id = null;
    public string $fecha       = '';
    public float  $stock_sistema = 0;
    public float  $stock_real    = 0;
    public float  $diferencia    = 0;
    public string $observacion   = '';

    protected $rules = [
        'articulo_id' => 'required|exists:articulos,id',
        'fecha'       => 'required|date',
        'stock_real'  => 'required|numeric|min:0',
    ];

    public function mount(): void
    {
        $this->filterDesde = now()->startOfMonth()->toDateString();
        $this->filterHasta = now()->toDateString();
        $this->fecha       = now()->toDateString();
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function updatedArticuloId(): void
    {
        if ($this->articulo_id) {
            $art = Articulo::find($this->articulo_id);
            $this->stock_sistema = (float) ($art?->stock ?? 0);
            $this->calcularDiferencia();
        }
    }

    public function updatedStockReal(): void { $this->calcularDiferencia(); }

    private function calcularDiferencia(): void
    {
        $this->diferencia = round($this->stock_real - $this->stock_sistema, 3);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();
        $merma_porcentaje = $this->stock_sistema > 0
            ? round(abs($this->diferencia) / $this->stock_sistema * 100, 3)
            : 0;

        $nro = (Pesada::max('nro_pesada') ?? 0) + 1;
        $pesada = Pesada::create([
            'user_id'          => auth()->id(),
            'articulo_id'      => $this->articulo_id,
            'nro_pesada'       => $nro,
            'fecha'            => $this->fecha,
            'stock_sistema'    => $this->stock_sistema,
            'stock_real'       => $this->stock_real,
            'diferencia'       => $this->diferencia,
            'merma_porcentaje' => $merma_porcentaje,
            'estado'           => 'aplicada',
            'observacion'      => $this->observacion ?: null,
        ]);

        // Ajustar stock del artículo al stock real
        $art = Articulo::find($this->articulo_id);
        if ($art) {
            $art->update(['stock' => $this->stock_real]);
            MovimientoArticulo::create([
                'articulo_id'     => $art->id,
                'tipo'            => $this->diferencia >= 0 ? 'entrada' : 'salida',
                'cantidad'        => abs($this->diferencia),
                'fecha'           => $this->fecha,
                'referencia_type' => Pesada::class,
                'referencia_id'   => $pesada->id,
                'observacion'     => "Ajuste por pesada #{$nro}",
            ]);
        }

        $this->resetForm();
        session()->flash('success', 'Pesada registrada y stock ajustado.');
    }

    public function resetForm(): void
    {
        $this->articulo_id   = null;
        $this->fecha         = now()->toDateString();
        $this->stock_sistema = 0;
        $this->stock_real    = 0;
        $this->diferencia    = 0;
        $this->observacion   = '';
        $this->showForm      = false;
        $this->resetValidation();
    }

    public function render()
    {
        $pesadas = Pesada::with(['articulo', 'user'])
            ->when($this->search, fn ($q) => $q->whereHas('articulo', fn ($a) =>
                $a->where('descripcion', 'like', "%{$this->search}%")
            ))
            ->when($this->filterDesde, fn ($q) => $q->where('fecha', '>=', $this->filterDesde))
            ->when($this->filterHasta, fn ($q) => $q->where('fecha', '<=', $this->filterHasta))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(25);

        return view('livewire.pesadas.index', [
            'pesadas'   => $pesadas,
            'articulos' => Articulo::where('estado', 'A')->where('modifica_stock', true)->orderBy('descripcion')->get(),
        ]);
    }
}
