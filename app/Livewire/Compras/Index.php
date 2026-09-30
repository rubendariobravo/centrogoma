<?php

namespace App\Livewire\Compras;

use App\Models\Compra;
use App\Models\Proveedor;
use App\Services\Compra\CompraService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    // Modal de anulación
    public ?int   $anularId        = null;
    public string $motivoAnulacion = '';
    public array  $anularPagos     = [];
    public string $anularPagado    = '';

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

    public function confirmarAnular(int $id): void
    {
        $this->anularId        = $id;
        $this->motivoAnulacion = '';
        $this->resetErrorBag();

        $pagos = $this->pagosAplicados($id);
        $this->anularPagos = $pagos->map(fn ($p) => [
            'nro'   => (int) $p->nro_orden,
            'fecha' => $p->fecha ? \Illuminate\Support\Carbon::parse($p->fecha)->format('d/m/Y') : '',
            'monto' => number_format((float) $p->monto, 2, ',', '.'),
        ])->all();
        $this->anularPagado = number_format((float) $pagos->sum('monto'), 2, ',', '.');
    }

    public function cancelarAnular(): void
    {
        $this->anularId        = null;
        $this->motivoAnulacion = '';
        $this->anularPagos     = [];
        $this->anularPagado    = '';
    }

    /**
     * Órdenes de pago vigentes que ya aplicaron un pago a esta compra. Anular igual es
     * válido (ej. factura cargada dos veces): lo pagado queda como crédito a favor en la
     * CC del proveedor, sin imputar a otras facturas. Solo se advierte.
     */
    private function pagosAplicados(int $compraId): Collection
    {
        return DB::table('detalle_ordenes_pago as d')
            ->join('ordenes_pago as o', 'o.id', '=', 'd.orden_pago_id')
            ->where('d.compra_id', $compraId)
            ->where('o.estado', '!=', 'A')
            ->whereNull('o.deleted_at')
            ->where('d.monto', '>', 0.001)
            ->orderBy('o.nro_orden')
            ->get(['o.nro_orden', 'o.fecha', 'd.monto']);
    }

    public function anular(): void
    {
        $this->validate([
            'motivoAnulacion' => 'required|string|min:5',
        ], [
            'motivoAnulacion.required' => 'Indique el motivo de la anulación.',
            'motivoAnulacion.min'      => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $compra = Compra::findOrFail($this->anularId);

        if ($compra->estado === 'A') {
            $this->cancelarAnular();
            return;
        }

        $pagado = round((float) $this->pagosAplicados($compra->id)->sum('monto'), 2);

        DB::transaction(function () use ($compra) {
            app(CompraService::class)->revertir($compra);

            $compra->estado      = 'A';
            $compra->saldo       = 0;
            $compra->observacion = trim(($compra->observacion ? $compra->observacion . "\n" : '')
                . 'ANULADA ' . now()->format('d/m/Y H:i') . ': ' . $this->motivoAnulacion);
            $compra->save();
        });

        $id = $compra->id;
        $this->cancelarAnular();

        session()->flash('success', $pagado > 0.01
            ? sprintf('Compra #%s anulada. Los $%s ya pagados quedan como crédito a favor en la cuenta corriente del proveedor.',
                $id, number_format($pagado, 2, ',', '.'))
            : "Compra #{$id} anulada.");
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
            ->comerciales()
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
