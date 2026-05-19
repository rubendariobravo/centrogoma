<?php

namespace App\Livewire\Compras;

use App\Models\Compra;
use App\Models\Proveedor;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Compras')]
class Index extends Component
{
    use WithPagination;

    public string  $search          = '';
    public string  $filterEstado    = '';
    public string  $filterDesde     = '';
    public string  $filterHasta     = '';
    public ?int    $filterProveedor = null;

    protected $queryString = [
        'search'          => ['except' => ''],
        'filterEstado'    => ['except' => ''],
        'filterDesde'     => ['except' => ''],
        'filterHasta'     => ['except' => ''],
        'filterProveedor' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->filterDesde = now()->startOfMonth()->toDateString();
        $this->filterHasta = now()->toDateString();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $compras = Compra::query()
            ->with(['proveedor', 'user'])
            ->when($this->search, fn ($q) => $q->whereHas('proveedor', fn ($c) =>
                $c->where('razon_social', 'like', "%{$this->search}%")
                  ->orWhere('nombre_fantasia', 'like', "%{$this->search}%")
            )->orWhere('nro_factura', 'like', "%{$this->search}%"))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->when($this->filterProveedor, fn ($q) => $q->where('proveedor_id', $this->filterProveedor))
            ->when($this->filterDesde, fn ($q) => $q->where('fecha', '>=', $this->filterDesde))
            ->when($this->filterHasta, fn ($q) => $q->where('fecha', '<=', $this->filterHasta))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(25);

        $totalPeriodo = Compra::query()
            ->where('estado', '!=', 'A')
            ->when($this->filterDesde, fn ($q) => $q->where('fecha', '>=', $this->filterDesde))
            ->when($this->filterHasta, fn ($q) => $q->where('fecha', '<=', $this->filterHasta))
            ->sum('total');

        return view('livewire.compras.index', [
            'compras'      => $compras,
            'totalPeriodo' => $totalPeriodo,
            'proveedores'  => Proveedor::where('estado', 'A')->orderBy('razon_social')->get(),
        ]);
    }
}
