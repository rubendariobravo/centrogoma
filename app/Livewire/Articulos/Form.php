<?php

namespace App\Livewire\Articulos;

use App\Models\Articulo;
use App\Models\ListaPrecio;
use App\Models\Marca;
use App\Models\Rubro;
use App\Support\Pricing;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Artículo')]
class Form extends Component
{
    public ?int $articuloId = null;

    // Identificación
    public string $codigo       = 'SINCODIGO';
    public string $descripcion  = '';
    public string $presentacion = '';
    public string $imagen_url   = '';

    // Clasificación
    public ?int $marca_id = null;
    public ?int $rubro_id = null;

    // Unidad / presentación
    public string $unidad_medida = 'unidad';
    public string $presentacion_caja = '';
    public ?float $cantidad_caja = null;

    // Precios
    public array $tiers = [];

    // Stock
    public float $stock          = 0;
    public float $stock_minimo   = 0;
    public bool  $modifica_stock = true;
    public bool  $art_mayorista  = false;

    // IVA y precios legacy
    public float  $alicuota_iva    = 21;
    public float  $precio_costo    = 0;
    public float  $margen          = 0;
    public float  $bonificacion    = 0;
    public float  $precio_unitario = 0;

    /**
     * Modo de edicion de tramos: false = ver finales (con IVA), true = ver netos.
     * Default = final. Los valores en $tiers[*]['precio'] siempre se mantienen
     * en la unidad que ve el usuario; la conversion a neto se hace en save().
     */
    public bool $verNetosTramos = false;

    /**
     * Alicuota IVA con la que se cargaron originalmente los tramos. Si el
     * usuario cambia $alicuota_iva, recalculamos los precios mostrados para
     * preservar el neto subyacente. Publica para sobrevivir requests Livewire,
     * pero no la binda ningun input (es estado interno).
     */
    public float $alicuotaIvaCargada = 21;

    // Estado
    public string $estado = 'A';

    /**
     * Lista por defecto del sistema. Los tramos editados desde este formulario
     * pertenecen siempre a esta lista. Para gestionar tramos de otras listas
     * (Mayorista, etc.) se usa la pantalla `/listas-precios/{lista}/precios`.
     */
    #[Computed]
    public function listaDefault(): ?ListaPrecio
    {
        return ListaPrecio::where('es_default', true)->first()
            ?? ListaPrecio::where('activo', true)->orderBy('orden')->first();
    }

    protected function rules(): array
    {
        return [
            'codigo'           => 'required|string|max:20',
            'descripcion'      => 'required|string|max:250',
            'rubro_id'         => 'nullable|exists:rubros,id',
            'marca_id'         => 'nullable|exists:marcas,id',
            'unidad_medida'    => 'required|string|max:20',
            'alicuota_iva'     => 'required|numeric|in:0,10.5,21,27',
            'stock'            => 'required|numeric',
            'stock_minimo'     => 'required|numeric|min:0',
            'precio_costo'     => 'required|numeric|min:0',
            'estado'           => 'required|in:A,I',
            'tiers'            => 'array',
            'tiers.*.cantidad_desde' => 'required|numeric|min:0',
            'tiers.*.cantidad_hasta' => 'nullable|numeric|gt:tiers.*.cantidad_desde',
            'tiers.*.precio'         => 'required|numeric|min:0',
        ];
    }

    protected function messages(): array
    {
        return [
            'descripcion.required'       => 'La descripción es obligatoria.',
            'tiers.*.precio.required'    => 'El precio es obligatorio en cada tramo.',
            'tiers.*.precio.min'         => 'El precio debe ser mayor a 0.',
            'tiers.*.cantidad_desde.required' => 'La cantidad desde es obligatoria.',
        ];
    }

    public function mount(?int $articulo = null): void
    {
        $listaDefault = $this->listaDefault;

        if ($articulo) {
            $art = Articulo::findOrFail($articulo);
            $this->articuloId      = $art->id;
            $this->codigo          = $art->codigo;
            $this->descripcion     = $art->descripcion ?? '';
            $this->presentacion    = $art->presentacion ?? '';
            $this->imagen_url      = $art->imagen_url ?? '';
            $this->marca_id        = $art->marca_id;
            $this->rubro_id        = $art->rubro_id;
            $this->unidad_medida   = $art->unidad_medida;
            $this->presentacion_caja = $art->presentacion_caja ?? '';
            $this->cantidad_caja   = $art->cantidad_caja;
            $this->stock           = (float) $art->stock;
            $this->stock_minimo    = (float) $art->stock_minimo;
            $this->modifica_stock  = (bool) $art->modifica_stock;
            $this->art_mayorista   = (bool) $art->art_mayorista;
            $this->alicuota_iva    = (float) $art->alicuota_iva;
            $this->precio_costo    = (float) $art->precio_costo;
            $this->margen          = (float) $art->margen;
            $this->bonificacion    = (float) $art->bonificacion;
            $this->precio_unitario = (float) $art->precio_unitario;
            $this->estado          = $art->estado;

            // Solo tramos de la lista por defecto (Minorista). Los tramos de
            // otras listas se gestionan en /listas-precios/{lista}/precios.
            $tramos = $listaDefault
                ? $art->precios()
                    ->where('lista_precio_id', $listaDefault->id)
                    ->orderBy('cantidad_desde')
                    ->get()
                : collect();

            // DB guarda neto; cargamos en pantalla en final por default
            // (toggle $verNetosTramos lo invierte). Guardamos la alicuota con
            // la que se cargaron los precios para poder recalcular si cambia.
            $this->alicuotaIvaCargada = $this->alicuota_iva;

            $this->tiers = $tramos->map(function ($p) {
                $neto = (float) $p->precio;
                return [
                    'cantidad_desde' => (float) $p->cantidad_desde,
                    'cantidad_hasta' => $p->cantidad_hasta !== null ? (float) $p->cantidad_hasta : null,
                    'precio'         => $this->verNetosTramos
                        ? $neto
                        : Pricing::final($neto, $this->alicuota_iva),
                    'activo'         => (bool) $p->activo,
                    'orden'          => (int) $p->orden,
                ];
            })->toArray();
        }

        if (empty($this->tiers)) {
            $this->addTier();
        }
    }

    public function toggleVerNetosTramos(): void
    {
        $iva = (float) $this->alicuota_iva;
        if ($iva <= 0) {
            $this->verNetosTramos = ! $this->verNetosTramos;
            return;
        }

        foreach ($this->tiers as $i => $tier) {
            $valor = (float) $tier['precio'];
            // Si estamos en modo final, convertimos a neto y viceversa.
            $this->tiers[$i]['precio'] = $this->verNetosTramos
                ? Pricing::final($valor, $iva)
                : Pricing::neto($valor, $iva);
        }
        $this->verNetosTramos = ! $this->verNetosTramos;
    }

    /**
     * Cuando el usuario cambia la alicuota IVA del articulo, recalculamos
     * los precios mostrados en $tiers para preservar el neto subyacente
     * (el "precio real" del articulo). Solo aplica si estamos viendo finales.
     */
    public function updatedAlicuotaIva(): void
    {
        $ivaNueva   = (float) $this->alicuota_iva;
        $ivaPrevia  = (float) $this->alicuotaIvaCargada;

        if (! $this->verNetosTramos && $ivaPrevia !== $ivaNueva) {
            foreach ($this->tiers as $i => $tier) {
                $finalActual = (float) $tier['precio'];
                $neto        = Pricing::neto($finalActual, $ivaPrevia);
                $this->tiers[$i]['precio'] = Pricing::final($neto, $ivaNueva);
            }
        }
        $this->alicuotaIvaCargada = $ivaNueva;
    }

    public function addTier(): void
    {
        $lastDesde = count($this->tiers) > 0
            ? (($this->tiers[count($this->tiers) - 1]['cantidad_hasta'] ?? 0) + 0.001)
            : 1;

        $this->tiers[] = [
            'cantidad_desde' => round($lastDesde, 3),
            'cantidad_hasta' => null,
            'precio'         => 0,
            'activo'         => true,
            'orden'          => count($this->tiers),
        ];
    }

    public function removeTier(int $index): void
    {
        if (count($this->tiers) > 1) {
            array_splice($this->tiers, $index, 1);
            // Re-index
            $this->tiers = array_values($this->tiers);
        }
    }

    public function updatedTiersOrden(): void
    {
        // re-sort tiers by cantidad_desde when user changes values
        usort($this->tiers, fn ($a, $b) => $a['cantidad_desde'] <=> $b['cantidad_desde']);
    }

    /**
     * Convierte el precio de un tier al neto que debe guardarse en DB.
     * Si estamos viendo netos, ya es neto. Si estamos viendo finales,
     * divide por (1 + iva/100).
     */
    private function precioNetoDeTier(array $tier): float
    {
        $valor = (float) $tier['precio'];
        if ($this->verNetosTramos) {
            return round($valor, 2);
        }
        return Pricing::neto($valor, (float) $this->alicuota_iva);
    }

    public function save(): void
    {
        $this->validate();

        $precioUnitarioNeto = isset($this->tiers[0])
            ? $this->precioNetoDeTier($this->tiers[0])
            : (float) $this->precio_unitario;

        $data = [
            'codigo'            => $this->codigo,
            'descripcion'       => $this->descripcion,
            'presentacion'      => $this->presentacion ?: null,
            'imagen_url'        => $this->imagen_url ?: null,
            'marca_id'          => $this->marca_id,
            'rubro_id'          => $this->rubro_id,
            'unidad_medida'     => $this->unidad_medida,
            'presentacion_caja' => $this->presentacion_caja ?: null,
            'cantidad_caja'     => $this->cantidad_caja,
            'stock'             => $this->stock,
            'stock_minimo'      => $this->stock_minimo,
            'modifica_stock'    => $this->modifica_stock,
            'art_mayorista'     => $this->art_mayorista,
            'alicuota_iva'      => $this->alicuota_iva,
            'precio_costo'      => $this->precio_costo,
            'margen'            => $this->margen,
            'bonificacion'      => $this->bonificacion,
            'precio_unitario'   => $precioUnitarioNeto,
            'estado'            => $this->estado,
            'fecha_modif'       => now()->toDateString(),
        ];

        if ($this->articuloId) {
            $articulo = Articulo::findOrFail($this->articuloId);
            $articulo->update($data);
        } else {
            $articulo = Articulo::create($data);
        }

        // Guardar tramos de precio SOLO de la lista por defecto.
        // Los tramos de otras listas (Mayorista, etc.) NO se tocan acá: se
        // gestionan en /listas-precios/{lista}/precios.
        $listaDefault = $this->listaDefault;
        if ($listaDefault) {
            $articulo->precios()
                ->where('lista_precio_id', $listaDefault->id)
                ->delete();

            foreach ($this->tiers as $index => $tier) {
                $articulo->precios()->create([
                    'lista_precio_id' => $listaDefault->id,
                    'cantidad_desde'  => $tier['cantidad_desde'],
                    'cantidad_hasta'  => $tier['cantidad_hasta'] ?: null,
                    'precio'          => $this->precioNetoDeTier($tier),
                    'activo'          => $tier['activo'] ?? true,
                    'orden'           => $index,
                ]);
            }
        }

        session()->flash('success', $this->articuloId ? 'Artículo actualizado.' : 'Artículo creado.');
        $this->redirect(route('articulos'), navigate: true);
    }

    public function render()
    {
        return view('livewire.articulos.form', [
            'marcas' => Marca::where('estado', 'A')->orderBy('descripcion')->get(),
            'rubros' => Rubro::where('estado', 'A')->orderBy('descripcion')->get(),
        ]);
    }
}
