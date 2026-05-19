<?php

namespace App\Livewire\CcProveedores;

use App\Models\CcProveedor;
use App\Models\Proveedor;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Cuentas corrientes - Proveedores')]
class Index extends Component
{
    use WithPagination;

    public string $search      = '';
    public string $filterSaldo = 'deudores';

    public ?int $proveedorSeleccionado = null;
    public array $movimientos          = [];
    public float $saldoSeleccionado    = 0;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function seleccionarProveedor(int $proveedorId): void
    {
        if ($this->proveedorSeleccionado === $proveedorId) {
            $this->proveedorSeleccionado = null;
            $this->movimientos           = [];
            $this->saldoSeleccionado     = 0;
            return;
        }

        $this->proveedorSeleccionado = $proveedorId;
        $proveedor = Proveedor::find($proveedorId);
        $this->saldoSeleccionado = (float) ($proveedor?->saldo ?? 0);

        $this->movimientos = CcProveedor::where('proveedor_id', $proveedorId)
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
        $proveedores = Proveedor::query()
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

        return view('livewire.cc-proveedores.index', [
            'proveedores' => $proveedores,
        ]);
    }
}
