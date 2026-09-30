<?php

namespace App\Livewire\Ventas;

use App\Models\Cliente;
use App\Models\Venta;
use App\Livewire\Concerns\AnulaComprobante;
use App\Services\Anulacion\AnulacionService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Ventas')]
class Index extends Component
{
    use WithPagination;
    use AnulaComprobante;

    /** Datos del modal de anulación. */
    public bool   $anularConCae  = false;
    public array  $anularCobros  = [];
    public string $anularCobrado = '';

    public string $search       = '';
    public string $filterEstado = '';
    public string $filterDesde  = '';
    public string $filterHasta  = '';
    public ?int   $filterCliente = null;

    protected $queryString = [
        'search'        => ['except' => ''],
        'filterEstado'  => ['except' => ''],
        'filterDesde'   => ['except' => ''],
        'filterHasta'   => ['except' => ''],
        'filterCliente' => ['except' => ''],
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

    protected function alAbrirAnulacion(int $id): void
    {
        $this->anularConCae = ! empty(Venta::find($id)?->cae);

        $cobros = app(AnulacionService::class)->cobrosAplicados($id);
        $this->anularCobros = $cobros->map(fn ($c) => [
            'nro'   => (int) $c->nro_recibo,
            'fecha' => $c->fecha ? \Illuminate\Support\Carbon::parse($c->fecha)->format('d/m/Y') : '',
            'monto' => number_format((float) $c->monto_aplicado, 2, ',', '.'),
        ])->all();
        $this->anularCobrado = number_format((float) $cobros->sum('monto_aplicado'), 2, ',', '.');
    }

    protected function ejecutarAnulacion(int $id, string $motivo): string
    {
        $venta = Venta::findOrFail($id);
        app(AnulacionService::class)->anularVenta($venta, $motivo);

        return "Venta #{$venta->nro_venta} anulada. Se revirtió stock, caja y cuenta corriente.";
    }

    public function render()
    {
        $ventas = Venta::query()
            ->with(['cliente', 'user'])
            ->when($this->search, fn ($q) => $q->whereHas('cliente', fn ($c) =>
                $c->where('razon_social', 'like', "%{$this->search}%")
                  ->orWhere('nombre_fantasia', 'like', "%{$this->search}%")
            )->orWhere('nro_venta', 'like', "%{$this->search}%"))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->when($this->filterCliente, fn ($q) => $q->where('cliente_id', $this->filterCliente))
            ->when($this->filterDesde, fn ($q) => $q->where('fecha', '>=', $this->filterDesde))
            ->when($this->filterHasta, fn ($q) => $q->where('fecha', '<=', $this->filterHasta))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(25);

        $totalPeriodo = Venta::query()
            ->where('estado', '!=', 'A')
            ->when($this->filterDesde, fn ($q) => $q->where('fecha', '>=', $this->filterDesde))
            ->when($this->filterHasta, fn ($q) => $q->where('fecha', '<=', $this->filterHasta))
            ->sum('total');

        return view('livewire.ventas.index', [
            'ventas'       => $ventas,
            'totalPeriodo' => $totalPeriodo,
        ]);
    }
}
