<?php

namespace App\Livewire\Cheques;

use App\Models\Cheque;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Cheques')]
class Index extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $filterTipo    = '';
    public string $filterEstado  = '';
    public string $filterDesde   = '';
    public string $filterHasta   = '';

    // Formulario inline para nuevo/editar cheque
    public bool   $showForm      = false;
    public ?int   $editingId     = null;

    public string $tipo          = 'recibido';
    public string $banco         = '';
    public string $nro_cheque    = '';
    public string $titular       = '';
    public string $cuit_titular  = '';
    public string $fecha_emision = '';
    public string $fecha_cobro   = '';
    public float  $monto         = 0;
    public string $referencia    = '';
    public string $observacion   = '';
    public ?int   $cliente_id    = null;
    public ?int   $proveedor_id  = null;

    protected $queryString = [
        'search'       => ['except' => ''],
        'filterTipo'   => ['except' => ''],
        'filterEstado' => ['except' => ''],
        'filterDesde'  => ['except' => ''],
        'filterHasta'  => ['except' => ''],
    ];

    protected function rules(): array
    {
        return [
            'tipo'          => 'required|in:recibido,emitido',
            'banco'         => 'required|string|max:100',
            'nro_cheque'    => 'required|string|max:50',
            'titular'       => 'required|string|max:150',
            'cuit_titular'  => 'nullable|string|max:20',
            'fecha_emision' => 'required|date',
            'fecha_cobro'   => 'required|date',
            'monto'         => 'required|numeric|min:0.01',
            'referencia'    => 'nullable|string|max:150',
            'observacion'   => 'nullable|string',
            'cliente_id'    => 'nullable|exists:clientes,id',
            'proveedor_id'  => 'nullable|exists:proveedores,id',
        ];
    }

    public function mount(): void
    {
        $this->filterDesde   = now()->startOfMonth()->toDateString();
        $this->filterHasta   = now()->addMonths(3)->toDateString();
        $this->fecha_emision = now()->toDateString();
        $this->fecha_cobro   = now()->addDays(30)->toDateString();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function nuevo(): void
    {
        $this->resetForm();
        $this->showForm  = true;
        $this->editingId = null;
    }

    public function editar(int $id): void
    {
        $cheque = Cheque::findOrFail($id);
        $this->editingId     = $cheque->id;
        $this->tipo          = $cheque->tipo;
        $this->banco         = $cheque->banco;
        $this->nro_cheque    = $cheque->nro_cheque;
        $this->titular       = $cheque->titular;
        $this->cuit_titular  = $cheque->cuit_titular ?? '';
        $this->fecha_emision = $cheque->fecha_emision->toDateString();
        $this->fecha_cobro   = $cheque->fecha_cobro->toDateString();
        $this->monto         = $cheque->monto;
        $this->referencia    = $cheque->referencia ?? '';
        $this->observacion   = $cheque->observacion ?? '';
        $this->cliente_id    = $cheque->cliente_id;
        $this->proveedor_id  = $cheque->proveedor_id;
        $this->showForm      = true;
    }

    public function guardar(): void
    {
        $this->validate();

        $data = [
            'tipo'          => $this->tipo,
            'banco'         => $this->banco,
            'nro_cheque'    => $this->nro_cheque,
            'titular'       => $this->titular,
            'cuit_titular'  => $this->cuit_titular ?: null,
            'fecha_emision' => $this->fecha_emision,
            'fecha_cobro'   => $this->fecha_cobro,
            'monto'         => $this->monto,
            'referencia'    => $this->referencia ?: null,
            'observacion'   => $this->observacion ?: null,
            'cliente_id'    => $this->cliente_id,
            'proveedor_id'  => $this->proveedor_id,
        ];

        if ($this->editingId) {
            Cheque::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Cheque actualizado.');
        } else {
            $data['estado'] = 'en_cartera';
            Cheque::create($data);
            session()->flash('success', 'Cheque registrado.');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $cheque = Cheque::findOrFail($id);
        $cheque->update(['estado' => $estado]);
        session()->flash('success', 'Estado actualizado: ' . $cheque->fresh()->label_estado . '.');
    }

    public function cancelarForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->editingId     = null;
        $this->tipo          = 'recibido';
        $this->banco         = '';
        $this->nro_cheque    = '';
        $this->titular       = '';
        $this->cuit_titular  = '';
        $this->fecha_emision = now()->toDateString();
        $this->fecha_cobro   = now()->addDays(30)->toDateString();
        $this->monto         = 0;
        $this->referencia    = '';
        $this->observacion   = '';
        $this->cliente_id    = null;
        $this->proveedor_id  = null;
        $this->resetValidation();
    }

    public function render()
    {
        $cheques = Cheque::query()
            ->with(['cliente', 'proveedor'])
            ->when($this->search, fn ($q) => $q
                ->where('titular', 'like', "%{$this->search}%")
                ->orWhere('nro_cheque', 'like', "%{$this->search}%")
                ->orWhere('banco', 'like', "%{$this->search}%")
            )
            ->when($this->filterTipo,   fn ($q) => $q->where('tipo', $this->filterTipo))
            ->when($this->filterEstado, fn ($q) => $q->where('estado', $this->filterEstado))
            ->when($this->filterDesde,  fn ($q) => $q->where('fecha_cobro', '>=', $this->filterDesde))
            ->when($this->filterHasta,  fn ($q) => $q->where('fecha_cobro', '<=', $this->filterHasta))
            ->orderBy('fecha_cobro')
            ->orderByDesc('id')
            ->paginate(25);

        $totalCartera = Cheque::where('tipo', 'recibido')
            ->where('estado', 'en_cartera')
            ->sum('monto');

        $totalPorVencer = Cheque::where('tipo', 'recibido')
            ->where('estado', 'en_cartera')
            ->where('fecha_cobro', '<=', now()->addDays(7))
            ->sum('monto');

        return view('livewire.cheques.index', [
            'cheques'       => $cheques,
            'totalCartera'  => $totalCartera,
            'totalPorVencer'=> $totalPorVencer,
            'clientes'      => \App\Models\Cliente::where('estado', 'A')->orderBy('razon_social')->get(),
            'proveedores'   => \App\Models\Proveedor::where('estado', 'A')->orderBy('razon_social')->get(),
        ]);
    }
}
