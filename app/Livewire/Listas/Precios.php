<?php

namespace App\Livewire\Listas;

use App\Imports\ListaPreciosImport;
use App\Models\AjustePrecio;
use App\Models\Articulo;
use App\Models\ArticuloPrecio;
use App\Models\ListaPrecio;
use App\Models\Marca;
use App\Models\Rubro;
use App\Services\Pricing\AjustePrecios;
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

    private const POR_PAGINA = 25;

    public int    $listaId;
    public string $search      = '';
    public string $filterRubro = '';

    /** Artículos marcados en el listado para ajustarlos juntos. */
    public array $seleccion = [];

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

    // — Ajuste masivo (AjustePrecios) —
    public bool   $showAjustePanel    = false;
    public string $ajusteModo         = 'porcentaje';   // porcentaje | costo
    public string $ajustePct          = '';
    public string $ajusteTipo         = 'subir';
    public string $ajusteRedondeo     = '100';
    public string $ajusteRubroFiltro  = '';
    public string $ajusteMarcaFiltro  = '';
    public bool   $ajusteSoloBusqueda = false;
    public bool   $ajusteSoloSeleccion = false;
    public ?array $ajustePreview      = null;

    // — Importar Excel —
    public bool    $showImportPanel = false;
    public         $importFile      = null;
    public string  $importModo      = 'final';
    public ?string $importResultado = null;
    public array   $importErrores   = [];

    // — Grilla editable (paginada; guarda solo lo que cambió) —
    public bool  $modoGrilla     = false;
    public array $grillaPrecios  = [];   // tramo_id => precio mostrado (editable)
    public array $grillaOriginal = [];   // tramo_id => precio mostrado al cargar
    public array $grillaIvas     = [];   // tramo_id => alícuota

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
        if ($this->modoGrilla && $this->grillaPendientes() > 0) {
            session()->flash('error', 'Guardá o descartá los cambios de la grilla antes de cambiar la vista.');
            return;
        }
        $this->verNetos = ! $this->verNetos;
        $this->grillaPrecios = $this->grillaOriginal = $this->grillaIvas = [];
    }

    public function limpiarSeleccion(): void
    {
        $this->seleccion = [];
        $this->ajusteSoloSeleccion = false;
    }

    private function esListaDefault(): bool
    {
        return (bool) $this->lista->es_default;
    }

    private function sincronizarBase(array $articuloIds): void
    {
        if ($this->esListaDefault()) {
            AjustePrecios::sincronizarPrecioBase($articuloIds);
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
            ? round($precioIngresado, 4)
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

        $this->sincronizarBase([$this->editingArticuloId]);
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
        $this->sincronizarBase([$tramo->articulo_id]);
        $this->confirmingDeleteTramo = null;
        session()->flash('success', 'Tramo eliminado.');
    }

    // ─── Ajuste masivo ────────────────────────────────────────────────────────

    /** Cualquier cambio en los parámetros invalida la vista previa: hay que volver a calcularla. */
    public function updated(string $prop): void
    {
        if (str_starts_with($prop, 'ajuste') && $prop !== 'ajustePreview') {
            $this->ajustePreview = null;
        }
    }

    private function parametrosAjuste(): array
    {
        $max = $this->ajusteTipo === 'bajar' ? 99 : 999;
        $this->validate([
            'ajustePct'      => "required|numeric|min:0.01|max:{$max}",
            'ajusteTipo'     => 'in:subir,bajar',
            'ajusteModo'     => 'in:porcentaje,costo',
            'ajusteRedondeo' => 'in:' . implode(',', array_keys(AjustePrecios::REDONDEOS)),
        ], [
            'ajustePct.required' => 'Ingresá el porcentaje.',
            'ajustePct.min'      => 'El porcentaje debe ser mayor a 0.',
            'ajustePct.max'      => $this->ajusteTipo === 'bajar' ? 'No se puede bajar 100% o más.' : 'El porcentaje es demasiado alto.',
        ]);

        if ($this->ajusteModo === 'costo' && ! $this->esListaDefault()) {
            $this->addError('ajusteModo', 'El ajuste por costo y margen es solo para la lista por defecto.');
            return [];
        }
        if ($this->ajusteSoloSeleccion && empty($this->seleccion)) {
            $this->addError('ajusteSoloSeleccion', 'No hay artículos seleccionados en el listado.');
            return [];
        }

        return [
            'modo'       => $this->ajusteModo,
            'tipo'       => $this->ajusteTipo,
            'porcentaje' => (float) $this->ajustePct,
            'redondeo'   => (float) $this->ajusteRedondeo,
            'filtros'    => array_filter([
                'rubro_id'     => $this->ajusteRubroFiltro !== '' ? (int) $this->ajusteRubroFiltro : null,
                'marca_id'     => $this->ajusteMarcaFiltro !== '' ? (int) $this->ajusteMarcaFiltro : null,
                'search'       => $this->ajusteSoloBusqueda && trim($this->search) !== '' ? trim($this->search) : null,
                'articulo_ids' => $this->ajusteSoloSeleccion ? array_map('intval', $this->seleccion) : null,
            ]),
        ];
    }

    public function previsualizarAjuste(AjustePrecios $ajustes): void
    {
        if (! $p = $this->parametrosAjuste()) return;
        $this->ajustePreview = $ajustes->previsualizar($this->listaId, $p);
    }

    public function aplicarAjuste(AjustePrecios $ajustes): void
    {
        if (! $this->ajustePreview) {
            $this->addError('ajustePct', 'Primero calculá la vista previa.');
            return;
        }
        if (! $p = $this->parametrosAjuste()) return;

        $ajuste = $ajustes->aplicar($this->listaId, $p);

        $this->cerrarAjustePanel();
        $this->grillaPrecios = $this->grillaOriginal = $this->grillaIvas = [];
        session()->flash('success', "Ajuste aplicado a {$ajuste->cantidad} precios ({$ajuste->descripcion}). Se puede deshacer desde el historial.");
    }

    public function revertirAjuste(int $id, AjustePrecios $ajustes): void
    {
        $ajuste = AjustePrecio::where('lista_precio_id', $this->listaId)->findOrFail($id);
        $r = $ajustes->revertir($ajuste);

        $this->grillaPrecios = $this->grillaOriginal = $this->grillaIvas = [];
        session()->flash('success', "Ajuste deshecho: {$r['revertidos']} precios volvieron al valor anterior"
            . ($r['omitidos'] ? "; {$r['omitidos']} se dejaron como estaban porque se modificaron después." : '.'));
    }

    public function cerrarAjustePanel(): void
    {
        $this->showAjustePanel     = false;
        $this->ajustePct           = '';
        $this->ajusteRubroFiltro   = '';
        $this->ajusteMarcaFiltro   = '';
        $this->ajusteSoloBusqueda  = false;
        $this->ajusteSoloSeleccion = false;
        $this->ajustePreview       = null;
    }

    // ─── Importar Excel ───────────────────────────────────────────────────────

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
        $this->sincronizarBase($import->articulosActualizados);

        $this->importResultado = "Actualizados: {$import->actualizados} · Omitidos: {$import->omitidos}";
        $this->importErrores   = $import->errores;
        $this->importFile      = null;
        $this->grillaPrecios = $this->grillaOriginal = $this->grillaIvas = [];
    }

    public function cerrarImportPanel(): void
    {
        $this->showImportPanel = false;
        $this->importFile      = null;
        $this->importResultado = null;
        $this->importErrores   = [];
    }

    // ─── Grilla editable ──────────────────────────────────────────────────────

    public function activarModoGrilla(): void
    {
        $this->modoGrilla = true;
    }

    public function desactivarModoGrilla(): void
    {
        $this->modoGrilla = false;
        $this->grillaPrecios = $this->grillaOriginal = $this->grillaIvas = [];
    }

    /** Suma a la grilla los tramos de la página visible (lo ya editado en otras páginas se conserva). */
    private function cargarGrillaDe($articulos): void
    {
        foreach ($articulos as $articulo) {
            $iva = (float) $articulo->alicuota_iva;
            foreach ($articulo->precios as $tramo) {
                if (array_key_exists($tramo->id, $this->grillaOriginal)) {
                    continue;
                }
                $valor = (string) ($this->verNetos ? round((float) $tramo->precio, 4) : Pricing::final((float) $tramo->precio, $iva));
                $this->grillaPrecios[$tramo->id]  = $valor;
                $this->grillaOriginal[$tramo->id] = $valor;
                $this->grillaIvas[$tramo->id]     = $iva;
            }
        }
    }

    /** Tramos editados en la grilla y todavía sin guardar. */
    private function cambiosGrilla(): array
    {
        $cambios = [];
        foreach ($this->grillaPrecios as $tramoId => $valor) {
            $precio = (float) str_replace(',', '.', (string) $valor);
            if ($precio > 0 && abs($precio - (float) ($this->grillaOriginal[$tramoId] ?? 0)) > 0.00005) {
                $cambios[$tramoId] = $precio;
            }
        }

        return $cambios;
    }

    public function grillaPendientes(): int
    {
        return count($this->cambiosGrilla());
    }

    public function guardarGrilla(): void
    {
        $cambios = $this->cambiosGrilla();
        $tramos  = ArticuloPrecio::whereIn('id', array_keys($cambios))->where('lista_precio_id', $this->listaId)->get();

        $netos = [];
        foreach ($tramos as $tramo) {
            $precio = $cambios[$tramo->id];
            $netos[$tramo->id] = $this->verNetos ? round($precio, 4) : Pricing::neto($precio, (float) $this->grillaIvas[$tramo->id]);
        }
        AjustePrecios::actualizarEnLote('articulo_precios', 'precio', $netos);
        $this->sincronizarBase($tramos->pluck('articulo_id')->unique()->all());

        // Lo guardado pasa a ser el nuevo "original".
        foreach ($netos as $id => $neto) {
            $this->grillaOriginal[$id] = $this->grillaPrecios[$id];
        }

        session()->flash('success', 'Grilla guardada: ' . count($netos) . ' precios actualizados.');
    }

    public function descartarGrilla(): void
    {
        $this->grillaPrecios = $this->grillaOriginal;
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $articulos = Articulo::query()
            ->where('estado', 'A')
            ->with(['rubro', 'precios' => fn ($q) => $q
                ->where('lista_precio_id', $this->listaId)
                ->orderBy('cantidad_desde')])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('codigo', 'like', "%{$this->search}%")
                  ->orWhere('descripcion', 'like', "%{$this->search}%");
            }))
            ->when($this->filterRubro, fn ($q) => $q->where('rubro_id', $this->filterRubro))
            ->orderBy('descripcion')
            ->paginate(self::POR_PAGINA);

        if ($this->modoGrilla) {
            $this->cargarGrillaDe($articulos);
        }

        return view('livewire.listas.precios', [
            'articulos'   => $articulos,
            'rubros'      => Rubro::orderBy('descripcion')->get(),
            'marcas'      => Marca::orderBy('descripcion')->get(),
            'lista'       => $this->lista,
            'historial'   => AjustePrecio::with('user:id,name')->where('lista_precio_id', $this->listaId)->latest('id')->limit(10)->get(),
            'redondeos'   => AjustePrecios::REDONDEOS,
            'pendientes'  => $this->modoGrilla ? $this->grillaPendientes() : 0,
        ]);
    }
}
