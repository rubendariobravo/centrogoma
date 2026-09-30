<?php

namespace App\Livewire\OrdenPago;

use App\Models\OrdenPago;
use App\Models\Proveedor;
use App\Livewire\Concerns\AnulaComprobante;
use App\Services\Anulacion\AnulacionService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Órdenes de Pago')]
class Index extends Component
{
    use WithPagination;
    use AnulaComprobante;

    public string $search          = '';
    public string $filterEstado    = '';
    public string $filterDesde     = '';
    public string $filterHasta     = '';
    public ?int   $filterProveedor = null;

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

    protected function ejecutarAnulacion(int $id, string $motivo): string
    {
        $orden = OrdenPago::findOrFail($id);
        app(AnulacionService::class)->anularOrdenPago($orden, $motivo);

        return "Orden de Pago #{$orden->nro_orden} anulada. Se revirtió la cuenta corriente del proveedor y la caja.";
    }

    public function render()
    {
        $ordenes = OrdenPago::query()
            ->with(['proveedor', 'user'])
            ->when($this->search, fn ($q) => $q->whereHas('proveedor', fn ($p) =>
                $p->where('razon_social', 'like', "%{$this->search}%")
                  ->orWhere('nombre_fantasia', 'like', "%{$this->search}%")
            )->orWhere('nro_orden', 'like', "%{$this->search}%"))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->when($this->filterProveedor, fn ($q) => $q->where('proveedor_id', $this->filterProveedor))
            ->when($this->filterDesde, fn ($q) => $q->where('fecha', '>=', $this->filterDesde))
            ->when($this->filterHasta, fn ($q) => $q->where('fecha', '<=', $this->filterHasta))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(25);

        $totalPeriodo = OrdenPago::query()
            ->where('estado', '!=', 'A')
            ->when($this->filterDesde, fn ($q) => $q->where('fecha', '>=', $this->filterDesde))
            ->when($this->filterHasta, fn ($q) => $q->where('fecha', '<=', $this->filterHasta))
            ->sum('total');

        return view('livewire.ordenes-pago.index', [
            'ordenes'      => $ordenes,
            'totalPeriodo' => $totalPeriodo,
            'proveedores'  => Proveedor::where('estado', 'A')->orderBy('razon_social')->get(),
        ]);
    }
}
