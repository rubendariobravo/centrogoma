<?php

namespace App\Livewire\Recibos;

use App\Models\Cliente;
use App\Models\Recibo;
use App\Livewire\Concerns\AnulaComprobante;
use App\Services\Anulacion\AnulacionService;
use App\Livewire\Concerns\BuscaCliente;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Recibos')]
class Index extends Component
{
    use BuscaCliente;

    protected function clienteCampo(): string
    {
        return 'filterCliente';
    }

    public function updatedFilterCliente(): void
    {
        $this->resetPage();
    }
    use WithPagination;
    use AnulaComprobante;

    public string $search        = '';
    public string $filterEstado  = '';
    public string $filterDesde   = '';
    public string $filterHasta   = '';
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

    protected function ejecutarAnulacion(int $id, string $motivo): string
    {
        $recibo = Recibo::findOrFail($id);
        app(AnulacionService::class)->anularRecibo($recibo, $motivo);

        return "Recibo #{$recibo->nro_recibo} anulado. Se revirtió la cuenta corriente del cliente y la caja.";
    }

    public function render()
    {
        $recibos = Recibo::query()
            ->with(['cliente', 'user'])
            ->when($this->search, fn ($q) => $q->whereHas('cliente', fn ($c) =>
                $c->where('razon_social', 'like', "%{$this->search}%")
                  ->orWhere('nombre_fantasia', 'like', "%{$this->search}%")
            )->orWhere('nro_recibo', 'like', "%{$this->search}%"))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->when($this->filterCliente, fn ($q) => $q->where('cliente_id', $this->filterCliente))
            ->when($this->filterDesde, fn ($q) => $q->where('fecha', '>=', $this->filterDesde))
            ->when($this->filterHasta, fn ($q) => $q->where('fecha', '<=', $this->filterHasta))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(25);

        $totalPeriodo = Recibo::query()
            ->where('estado', '!=', 'A')
            ->when($this->filterDesde, fn ($q) => $q->where('fecha', '>=', $this->filterDesde))
            ->when($this->filterHasta, fn ($q) => $q->where('fecha', '<=', $this->filterHasta))
            ->sum('total');

        return view('livewire.recibos.index', [
            'recibos'      => $recibos,
            'totalPeriodo' => $totalPeriodo,
        ]);
    }
}
