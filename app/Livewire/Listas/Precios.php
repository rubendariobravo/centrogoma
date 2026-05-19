<?php

namespace App\Livewire\Listas;

use App\Imports\ListaPreciosImport;
use App\Models\Articulo;
use App\Models\ArticuloPrecio;
use App\Models\ListaPrecio;
use App\Models\Rubro;
use App\Support\Pricing;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Title('Tramos por lista')]
class Precios extends Component
{
    use WithPagination, WithFileUploads;

    public int    $listaId;
    public string $search      = '';
    public string $filterRubro = '';

    #[Computed]
    public function lista(): ListaPrecio
    {
        return ListaPrecio::findOrFail($this->listaId);
    }

    public bool $verNetos = false;

    // — Modal tramo —
    public bool   $showTramoModal        = false;
    public ?int   $editingTramoId        = null;
    public ?int   $editingArticuloId     = null;
    public string $editingArticuloNombre = '';
    public float  $editingArticuloIva    = 21;
    public string $tramoDesde            = '1';
    public string $tramoHasta            = '';
    public string $tramoPrecio           = '0';
    public bool   $tramoActivo           = true;
    public ?int   $confirmingDeleteTramo = null;

    // — Feature 1: Ajuste % bulk —
    public bool   $showAjustePanel   = false;
    public string $ajustePct         = '';
    public string $ajusteTipo        = 'subir';
    public string $ajusteRubroFiltro = '';

    // — Feature 2: Importar Excel —
    public bool    $showImportPanel = false;
    public         $importFile      = null;
    public string  $importModo      = 'final';
    public ?string $importResultado = null;
    public array   $importErrores   = [];

    // — Feature 3: Grilla editable —
    public bool  $modoGrilla    = false;
    public array $grillaPrecios = [];
    public array $grillaIvas    = [];

    public function mount(int $lista): void
    {
        ListaPrecio::findOrFail($lista);
        $this->listaId = $lista;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterRubro(): void
    {
        $this->resetPage();
    }

    public function toggleVerNetos(): void
    {
        $this->verNetos = ! $this->verNetos;
        if ($this->modoGrilla) {
            $this->cargarGrilla();
        }
    }

    // ─── Modal tramo ───────────────────────────────────────────────────────────

    public function abrirModalNuevo(int $articuloId): void
    {
        $articulo = Articulo::findOrFail($articuloId);
        $this->editingTramoId        = null;
        $this->editingArticuloId     = $articulo->id;
        $this->editingArticuloNombre = $articulo->codigo . ' — ' . $articulo->descripcion;
        $this->editingArticuloIva    = (float) $articulo->alicuota_iva;

        $maxHasta = ArticuloPrecio::where('articulo_id', $articulo->id)
            ->where('lista_precio_id', $this->listaId)
            ->whereNotNull('cantidad_hasta')
            ->max('cantidad_hasta');

        $this->tramoDesde  = (string) ($maxHasta !== null ? ((float) $maxHasta + 1) : 1);
        $this->tramoHasta  = '';
        $precioNeto        = (float) $articulo->precio_unitario;
        $this->tramoPrecio = (string) ($this->verNetos
            ? $precioNeto
            : Pricing::final($precioNeto, $this->editingArticuloIva));
        $this->tramoActivo = true;

        $this->resetErrorBag();
        $this->showTramoModal = true;
    }

    public function abrirModalEditar(int $tramoId): void
    {
        $tramo = ArticuloPrecio::with('articulo')->findOrFail($tramoId);
        if ($tramo->lista_precio_id !== $this->listaId) {
            session()->flash('error', 'Tramo no pertenece a esta lista.');
            return;
        }

        $this->editingTramoId        = $tramo->id;
        $this->editingArticuloId     = $tramo->articulo_id;
        $this->editingArticuloNombre = $tramo->articulo->codigo . ' — ' . $tramo->articulo->descripcion;
        $this->editingArticuloIva    = (float) $tramo->articulo->alicuota_iva;
        $this->tramoDesde            = (string) $tramo->cantidad_desde;
        $this->tramoHasta            = $tramo->cantidad_hasta !== null ? (string) $tramo->cantidad_hasta : '';
        $precioNeto                  = (float) $tramo->precio;
        $this->tramoPrecio           = (string) ($this->verNetos
            ? $precioNeto
            : Pricing::final($precioNeto, $this->editingArticuloIva));
        $this->tramoActivo           = (bool) $tramo->activo;

        $this->resetErrorBag();
        $this->showTramoModal = true;
    }

    public function cerrarModal(): void
    {
        $this->showTramoModal    = false;
        $this->editingTramoId    = null;
        $this->editingArticuloId = null;
    }

    public function guardarTramo(): void
    {
        $this->validate([
            'tramoDesde'  => 'required|numeric|min:0',
            'tramoHasta'  => 'nullable|numeric|gt:tramoDesde',
            'tramoPrecio' => 'required|numeric|min:0',
            'tramoActivo' => 'boolean',
        ], [
            'tramoHasta.gt' => 'La cantidad hasta debe ser mayor a la cantidad desde.',
        ]);

        if (! $this->editingArticuloId) {
            session()->flash('error', 'Articulo no seleccionado.');
            return;
        }

        $precioIngresado = (float) $this->tramoPrecio;
        $precioNeto = $this->verNetos
            ? $precioIngresado
            : Pricing::neto($precioIngresado, $this->editingArticuloIva);

        $data = [
            'articulo_id'     => $this->editingArticuloId,
            'lista_precio_id' => $this->listaId,
            'cantidad_desde'  => (float) $this->tramoDesde,
            'cantidad_hasta'  => $this->tramoHasta !== '' ? (float) $this->tramoHasta : null,
            'precio'          => $precioNeto,
            'activo'          => $this->tramoActivo,
            'orden'           => 0,
        ];

        if ($this->editingTramoId) {
            ArticuloPrecio::findOrFail($this->editingTramoId)->update($data);
            session()->flash('success', 'Tramo actualizado.');
        } else {
            ArticuloPrecio::create($data);
            session()->flash('success', 'Tramo creado.');
        }

        $this->cerrarModal();
    }

    public function confirmDeleteTramo(int $id): void
    {
        $this->confirmingDeleteTramo = $id;
    }

    public function cancelDeleteTramo(): void
    {
        $this->confirmingDeleteTramo = null;
    }

    public function deleteTramo(): void
    {
        if (! $this->confirmingDeleteTramo) return;

        $tramo = ArticuloPrecio::findOrFail($this->confirmingDeleteTramo);
        if ($tramo->lista_precio_id !== $this->listaId) {
            session()->flash('error', 'Tramo no pertenece a esta lista.');
            $this->confirmingDeleteTramo = null;
            return;
        }
        $tramo->delete();
        $this->confirmingDeleteTramo = null;
        session()->flash('success', 'Tramo eliminado.');
    }

    // ─── Feature 1: Ajuste % bulk ─────────────────────────────────────────────

    public function aplicarAjuste(): void
    {
        $this->validate([
            'ajustePct'  => 'required|numeric|min:0.01|max:999',
            'ajusteTipo' => 'in:subir,bajar',
        ], [
            'ajustePct.required' => 'Ingresá el porcentaje.',
            'ajustePct.min'      => 'El porcentaje debe ser mayor a 0.',
        ]);

        $pct    = (float) $this->ajustePct;
        $factor = $this->ajusteTipo === 'subir' ? (1 + $pct / 100) : (1 - $pct / 100);

        $query = ArticuloPrecio::where('lista_precio_id', $this->listaId);

        if ($this->ajusteRubroFiltro !== '') {
            $query->whereHas('articulo', fn ($q) => $q->where('rubro_id', $this->ajusteRubroFiltro));
        }

        $tramos = $query->get();
        foreach ($tramos as $tramo) {
            $tramo->update(['precio' => round((float) $tramo->precio * $factor, 4)]);
        }

        $this->showAjustePanel   = false;
        $this->ajustePct         = '';
        $this->ajusteRubroFiltro = '';

        session()->flash('success', "Ajuste de {$pct}% aplicado a {$tramos->count()} tramos.");

        if ($this->modoGrilla) {
            $this->cargarGrilla();
        }
    }

    public function cerrarAjustePanel(): void
    {
        $this->showAjustePanel   = false;
        $this->ajustePct         = '';
        $this->ajusteRubroFiltro = '';
    }

    // ─── Feature 2: Importar Excel ────────────────────────────────────────────

    public function importarExcel(): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls|max:5120',
            'importModo' => 'in:final,neto',
        ], [
            'importFile.required' => 'Seleccioná un archivo Excel.',
            'importFile.mimes'    => 'El archivo debe ser .xlsx o .xls.',
            'importFile.max'      => 'El archivo no debe superar 5 MB.',
        ]);

        $import = new ListaPreciosImport($this->listaId, $this->importModo);
        Excel::import($import, $this->importFile->path());

        $this->importResultado = "Actualizados: {$import->actualizados} · Omitidos: {$import->omitidos}";
        $this->importErrores   = $import->errores;
        $this->importFile      = null;

        if ($this->modoGrilla) {
            $this->cargarGrilla();
        }
    }

    public function cerrarImportPanel(): void
    {
        $this->showImportPanel = false;
        $this->importFile      = null;
        $this->importResultado = null;
        $this->importErrores   = [];
    }

    // ─── Feature 3: Grilla editable ───────────────────────────────────────────

    public function activarModoGrilla(): void
    {
        $this->modoGrilla = true;
        $this->cargarGrilla();
    }

    public function desactivarModoGrilla(): void
    {
        $this->modoGrilla    = false;
        $this->grillaPrecios = [];
        $this->grillaIvas    = [];
    }

    private function cargarGrilla(): void
    {
        $this->grillaPrecios = [];
        $this->grillaIvas    = [];

        $tramos = ArticuloPrecio::with('articulo')
            ->where('lista_precio_id', $this->listaId)
            ->get();

        foreach ($tramos as $tramo) {
            $iva = (float) ($tramo->articulo->alicuota_iva ?? 21);
            $this->grillaIvas[$tramo->articulo_id] = $iva;
            $neto = (float) $tramo->precio;
            $this->grillaPrecios[$tramo->id] = (string) ($this->verNetos
                ? $neto
                : Pricing::final($neto, $iva));
        }
    }

    public function guardarGrilla(): void
    {
        $actualizados = 0;
        foreach ($this->grillaPrecios as $tramoId => $precioRaw) {
            $tramo = ArticuloPrecio::with('articulo')
                ->where('id', $tramoId)
                ->where('lista_precio_id', $this->listaId)
                ->first();

            if (! $tramo) continue;

            $precio = (float) str_replace(',', '.', (string) $precioRaw);
            if ($precio <= 0) continue;

            $iva  = (float) ($tramo->articulo->alicuota_iva ?? 21);
            $neto = $this->verNetos ? $precio : Pricing::neto($precio, $iva);
            $tramo->update(['precio' => round($neto, 4)]);
            $actualizados++;
        }

        session()->flash('success', "Grilla guardada: {$actualizados} tramos actualizados.");
        $this->cargarGrilla();
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $query = Articulo::query()
            ->where('estado', 'A')
            ->with(['rubro', 'precios' => fn ($q) => $q
                ->where('lista_precio_id', $this->listaId)
                ->orderBy('cantidad_desde')])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('codigo', 'like', "%{$this->search}%")
                  ->orWhere('descripcion', 'like', "%{$this->search}%");
            }))
            ->when($this->filterRubro, fn ($q) => $q->where('rubro_id', $this->filterRubro))
            ->orderBy('descripcion');

        $articulos = $this->modoGrilla
            ? $query->get()
            : $query->paginate(25);

        return view('livewire.listas.precios', [
            'articulos' => $articulos,
            'rubros'    => Rubro::orderBy('descripcion')->get(),
            'lista'     => $this->lista,
        ]);
    }
}
