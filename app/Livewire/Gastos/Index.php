<?php

namespace App\Livewire\Gastos;

use App\Models\Caja;
use App\Models\CategoriaGasto;
use App\Models\FormaPago;
use App\Models\Gasto;
use App\Models\Proveedor;
use App\Services\Caja\CajaService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Gastos')]
class Index extends Component
{
    use WithPagination;

    // Filtros
    public string $search        = '';
    public string $filterDesde   = '';
    public string $filterHasta   = '';
    public ?int   $filterCategoria = null;
    public ?int   $filterCaja      = null;

    // Formulario
    public bool   $showForm           = false;
    public ?int   $gastoId            = null;
    public string $fecha              = '';
    public ?int   $categoria_gasto_id = null;
    public ?int   $caja_id            = null;
    public ?int   $proveedor_id       = null;
    public ?float $monto              = null;
    public string $forma_pago         = 'efectivo';
    public string $comprobante        = '';
    public string $descripcion        = '';

    public ?int $confirmingDelete = null;

    // ABM de categorías (modal)
    public bool   $showCategorias  = false;
    public ?int   $categoriaEditId = null;
    public string $categoriaNombre = '';

    public function mount(): void
    {
        $this->filterDesde = now()->startOfMonth()->toDateString();
        $this->filterHasta = now()->toDateString();
        $this->fecha       = now()->toDateString();
        $this->forma_pago  = FormaPago::codigoEfectivo();
    }

    protected function rules(): array
    {
        return [
            'fecha'              => 'required|date',
            'categoria_gasto_id' => 'required|exists:categorias_gasto,id',
            'caja_id'            => 'required|exists:cajas,id',
            'proveedor_id'       => 'nullable|exists:proveedores,id',
            'monto'              => 'required|numeric|min:0.01',
            'forma_pago'         => 'required|in:' . implode(',', FormaPago::codigosActivos()),
            'comprobante'        => 'nullable|string|max:100',
            'descripcion'        => 'nullable|string|max:255',
        ];
    }

    /** Cajas abiertas que el usuario puede usar para imputar un gasto. */
    public function cajasDisponibles()
    {
        return Caja::with('user')
            ->where('estado', 'abierta')
            ->where(function ($q) {
                $q->where(function ($d) {
                    $d->where('tipo', 'diaria')->where('user_id', auth()->id());
                })->orWhereIn('tipo', ['general', 'banco']);
            })
            ->orderBy('tipo')
            ->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $disponibles = $this->cajasDisponibles();
        // Pre-selecciona la caja diaria del usuario si la tiene abierta
        $this->caja_id = $disponibles->firstWhere('tipo', 'diaria')?->id
            ?? $disponibles->first()?->id;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $g = Gasto::findOrFail($id);
        $this->gastoId            = $g->id;
        $this->fecha              = $g->fecha->toDateString();
        $this->categoria_gasto_id = $g->categoria_gasto_id;
        $this->caja_id            = $g->caja_id;
        $this->proveedor_id       = $g->proveedor_id;
        $this->monto              = (float) $g->monto;
        $this->forma_pago         = $g->forma_pago;
        $this->comprobante        = $g->comprobante ?? '';
        $this->descripcion        = $g->descripcion ?? '';
        $this->showForm           = true;
    }

    public function save(): void
    {
        $this->validate();

        $caja = Caja::find($this->caja_id);
        if (! $caja || ! $caja->estaAbierta()) {
            $this->addError('caja_id', 'La caja seleccionada no está abierta.');
            return;
        }

        $service = app(CajaService::class);

        $data = [
            'caja_id'            => $this->caja_id,
            'categoria_gasto_id' => $this->categoria_gasto_id,
            'proveedor_id'       => $this->proveedor_id,
            'user_id'            => auth()->id(),
            'fecha'              => $this->fecha,
            'monto'              => round((float) $this->monto, 2),
            'forma_pago'         => $this->forma_pago,
            'comprobante'        => $this->comprobante ?: null,
            'descripcion'        => $this->descripcion ?: null,
            'estado'             => 'A',
        ];

        if ($this->gastoId) {
            $gasto = Gasto::findOrFail($this->gastoId);
            // Revierte el efecto en caja anterior y vuelve a aplicarlo con los nuevos valores
            $service->revertirMovimientosDe(Gasto::class, $gasto->id);
            $gasto->update($data);
        } else {
            $gasto = Gasto::create($data);
        }

        $this->aplicarEnCaja($service, $gasto, $caja);

        $this->resetForm();
        session()->flash('success', 'Gasto registrado correctamente.');
    }

    private function aplicarEnCaja(CajaService $service, Gasto $gasto, Caja $caja): void
    {
        $categoria = CategoriaGasto::find($gasto->categoria_gasto_id);
        $service->registrarMovimiento(
            $caja, 'egreso', 'gasto', (float) $gasto->monto,
            [
                'forma_pago'         => $gasto->forma_pago,
                'referencia_type'    => Gasto::class,
                'referencia_id'      => $gasto->id,
                'categoria_gasto_id' => $gasto->categoria_gasto_id,
                'descripcion'        => 'Gasto: ' . ($categoria?->nombre ?? 'Varios') . ($gasto->descripcion ? " — {$gasto->descripcion}" : ''),
                'fecha'              => $gasto->fecha->toDateString(),
            ]
        );
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        $gasto = Gasto::findOrFail($this->confirmingDelete);
        // Revierte el efecto en caja antes de dar de baja
        app(CajaService::class)->revertirMovimientosDe(Gasto::class, $gasto->id);
        $gasto->update(['estado' => 'N']);
        $gasto->delete();

        $this->confirmingDelete = null;
        session()->flash('success', 'Gasto eliminado.');
    }

    public function resetForm(): void
    {
        $this->gastoId            = null;
        $this->fecha              = now()->toDateString();
        $this->categoria_gasto_id = null;
        $this->caja_id            = null;
        $this->proveedor_id       = null;
        $this->monto              = null;
        $this->forma_pago         = FormaPago::codigoEfectivo();
        $this->comprobante        = '';
        $this->descripcion        = '';
        $this->showForm           = false;
        $this->resetValidation();
    }

    // ── Categorías de gasto ─────────────────────────────────────────

    public function abrirCategorias(): void
    {
        $this->cancelarCategoria();
        $this->showCategorias = true;
    }

    public function editarCategoria(int $id): void
    {
        $cat = CategoriaGasto::findOrFail($id);
        $this->categoriaEditId = $cat->id;
        $this->categoriaNombre = $cat->nombre;
        $this->resetValidation('categoriaNombre');
    }

    public function cancelarCategoria(): void
    {
        $this->categoriaEditId = null;
        $this->categoriaNombre = '';
        $this->resetValidation('categoriaNombre');
    }

    public function guardarCategoria(): void
    {
        $this->categoriaNombre = trim($this->categoriaNombre);
        $this->validate([
            'categoriaNombre' => 'required|string|max:100|unique:categorias_gasto,nombre' . ($this->categoriaEditId ? ',' . $this->categoriaEditId : ''),
        ], [
            'categoriaNombre.required' => 'Ingrese el nombre de la categoría.',
            'categoriaNombre.unique'   => 'Ya existe una categoría con ese nombre.',
        ]);

        if ($this->categoriaEditId) {
            CategoriaGasto::findOrFail($this->categoriaEditId)->update(['nombre' => $this->categoriaNombre]);
        } else {
            CategoriaGasto::create(['nombre' => $this->categoriaNombre, 'activo' => true]);
        }

        $this->cancelarCategoria();
    }

    /** Las categorías no se borran (tienen gastos históricos): se activan/desactivan. */
    public function toggleCategoria(int $id): void
    {
        $cat = CategoriaGasto::findOrFail($id);
        $cat->update(['activo' => ! $cat->activo]);
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterCategoria(): void { $this->resetPage(); }
    public function updatingFilterCaja(): void { $this->resetPage(); }

    public function render()
    {
        $query = Gasto::with(['categoria', 'proveedor', 'caja.user', 'user'])
            ->when($this->search, fn ($q) => $q
                ->where(fn ($s) => $s->where('descripcion', 'like', "%{$this->search}%")
                    ->orWhere('comprobante', 'like', "%{$this->search}%")))
            ->when($this->filterCategoria, fn ($q) => $q->where('categoria_gasto_id', $this->filterCategoria))
            ->when($this->filterCaja, fn ($q) => $q->where('caja_id', $this->filterCaja))
            ->when($this->filterDesde, fn ($q) => $q->where('fecha', '>=', $this->filterDesde))
            ->when($this->filterHasta, fn ($q) => $q->where('fecha', '<=', $this->filterHasta));

        $totalFiltrado = (clone $query)->sum('monto');

        $gastos = $query->orderByDesc('fecha')->orderByDesc('id')->paginate(25);

        return view('livewire.gastos.index', [
            'gastos'         => $gastos,
            'totalFiltrado'  => $totalFiltrado,
            'categorias'     => CategoriaGasto::activas()->get(),
            'todasCategorias'=> $this->showCategorias
                ? CategoriaGasto::withCount('gastos')->orderByDesc('activo')->orderBy('nombre')->get()
                : collect(),
            'proveedores'    => Proveedor::where('estado', 'A')->orderBy('razon_social')->get(),
            'formasPago'     => FormaPago::activosParaSelect(),
            'cajas'          => $this->cajasDisponibles(),
        ]);
    }
}
