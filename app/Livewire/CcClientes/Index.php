<?php

namespace App\Livewire\CcClientes;

use App\Models\CcCliente;
use App\Models\Cliente;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Cuentas corrientes - Clientes')]
class Index extends Component
{
    use WithPagination;

    public string $search      = '';
    public string $filterSaldo = 'deudores'; // all, deudores, saldado

    public ?int $clienteSeleccionado = null;
    public array $movimientos        = [];
    public float $saldoSeleccionado  = 0;
    public string $clienteNombre     = '';

    public function mount(): void
    {
        $clienteId = (int) request()->query('clienteId');
        if ($clienteId) {
            $this->filterSaldo = 'all';
            $this->seleccionarCliente($clienteId);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function seleccionarCliente(int $clienteId): void
    {
        if ($this->clienteSeleccionado === $clienteId) {
            $this->clienteSeleccionado = null;
            $this->movimientos         = [];
            $this->saldoSeleccionado   = 0;
            $this->clienteNombre       = '';
            return;
        }

        $this->clienteSeleccionado = $clienteId;
        $cliente = Cliente::find($clienteId);
        $this->saldoSeleccionado = (float) ($cliente?->saldo ?? 0);
        $this->clienteNombre     = $cliente?->razon_social ?? '';

        $this->movimientos = CcCliente::where('cliente_id', $clienteId)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn ($m) => [
                'id'      => $m->id,
                'fecha'   => $m->fecha->format('d/m/Y'),
                'concepto'=> $m->concepto,
                'debe'    => (float) $m->debe,
                'haber'   => (float) $m->haber,
                'saldo'   => (float) $m->saldo,
            ])
            ->toArray();
    }

    public function render()
    {
        $clientes = Cliente::query()
            ->when($this->search, fn ($q) => $q
                ->where('razon_social', 'like', "%{$this->search}%")
                ->orWhere('nombre_fantasia', 'like', "%{$this->search}%")
                ->orWhere('cuit', 'like', "%{$this->search}%")
            )
            ->when($this->filterSaldo === 'deudores', fn ($q) => $q->where('saldo', '>', 0))
            ->when($this->filterSaldo === 'saldado', fn ($q) => $q->where('saldo', '<=', 0))
            ->where('estado', 'A')
            ->orderByDesc('saldo')
            ->paginate(20);

        return view('livewire.cc-clientes.index', [
            'clientes' => $clientes,
        ]);
    }
}
