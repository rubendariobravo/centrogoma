<?php

namespace App\Livewire\Produccion;

use App\Models\Articulo;
use App\Models\DetProduccion;
use App\Models\Empleado;
use App\Models\MovimientoArticulo;
use App\Models\Produccion;
use App\Models\SubArticulo;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Detalle de Producción')]
class Detalle extends Component
{
    public Produccion $produccion;

    // Cabecera editable
    public string $fecha           = '';
    public string $tipo_produccion = 'troceo';
    public ?int   $empleado_id     = null;
    public string $observacion     = '';

    // Fila de insumo (único por ahora, extensible)
    public ?int   $insumo_articulo_id  = null;
    public float  $insumo_cantidad     = 0;
    public float  $insumo_precio_costo = 0;

    // Productos obtenidos: array de filas
    // Cada elemento: ['articulo_id' => int|null, 'cantidad' => float, 'precio_costo' => float]
    public array $productos = [];

    public bool $confirmandoCierre  = false;
    public bool $confirmandoAnulado = false;

    protected function rules(): array
    {
        return [
            'fecha'                   => 'required|date',
            'insumo_articulo_id'      => 'required|exists:articulos,id',
            'insumo_cantidad'         => 'required|numeric|min:0.001',
            'productos'               => 'required|array|min:1',
            'productos.*.articulo_id' => 'required|exists:articulos,id',
            'productos.*.cantidad'    => 'required|numeric|min:0.001',
        ];
    }

    protected $messages = [
        'insumo_articulo_id.required'      => 'Seleccioná el artículo de entrada.',
        'insumo_cantidad.min'              => 'La cantidad debe ser mayor a 0.',
        'productos.required'               => 'Agregá al menos un producto obtenido.',
        'productos.min'                    => 'Agregá al menos un producto obtenido.',
        'productos.*.articulo_id.required' => 'Seleccioná el artículo del producto.',
        'productos.*.cantidad.min'         => 'La cantidad debe ser mayor a 0.',
    ];

    public function mount(Produccion $produccion): void
    {
        $this->produccion = $produccion->load(['insumos.articulo', 'productos.articulo', 'empleado']);

        $this->fecha           = $produccion->fecha->toDateString();
        $this->tipo_produccion = $produccion->tipo_produccion;
        $this->empleado_id     = $produccion->empleado_id;
        $this->observacion     = $produccion->observacion ?? '';

        // Cargar insumo existente
        $insumo = $produccion->insumos->first();
        if ($insumo) {
            $this->insumo_articulo_id  = $insumo->articulo_id;
            $this->insumo_cantidad     = $insumo->cantidad;
            $this->insumo_precio_costo = $insumo->precio_costo;
        }

        // Cargar productos existentes
        $this->productos = $produccion->productos->map(fn ($d) => [
            'articulo_id'  => $d->articulo_id,
            'cantidad'     => $d->cantidad,
            'precio_costo' => $d->precio_costo,
        ])->toArray();

        if (empty($this->productos)) {
            $this->productos = [['articulo_id' => null, 'cantidad' => 0, 'precio_costo' => 0]];
        }
    }

    // Al cambiar artículo de insumo, pre-carga su precio_costo y sugiere sub-artículos
    public function updatedInsumoArticuloId(): void
    {
        if (! $this->insumo_articulo_id) return;

        $art = Articulo::find($this->insumo_articulo_id);
        if ($art) {
            $this->insumo_precio_costo = $art->precio_costo ?? 0;
        }

        // Pre-cargar sub-artículos definidos para este padre
        $subs = SubArticulo::where('articulo_padre_id', $this->insumo_articulo_id)
            ->where('activo', true)
            ->with('articuloHijo')
            ->get();

        if ($subs->isNotEmpty()) {
            $this->productos = $subs->map(fn ($s) => [
                'articulo_id'  => $s->articulo_hijo_id,
                'cantidad'     => 0,
                'precio_costo' => $s->articuloHijo->precio_costo ?? 0,
            ])->toArray();
        }
    }

    // Al cambiar artículo de un producto, carga su precio_costo actual
    public function updatedProductos($value, $key): void
    {
        // $key es algo como "0.articulo_id"
        if (str_ends_with($key, '.articulo_id') && $value) {
            $idx = (int) explode('.', $key)[0];
            $art = Articulo::find($value);
            if ($art) {
                $this->productos[$idx]['precio_costo'] = $art->precio_costo ?? 0;
            }
        }
    }

    public function agregarProducto(): void
    {
        $this->productos[] = ['articulo_id' => null, 'cantidad' => 0, 'precio_costo' => 0];
    }

    public function quitarProducto(int $index): void
    {
        array_splice($this->productos, $index, 1);
        if (empty($this->productos)) {
            $this->productos = [['articulo_id' => null, 'cantidad' => 0, 'precio_costo' => 0]];
        }
    }

    // ── Totales calculados ──────────────────────────────────────────────────

    public function getTotalEntradaProperty(): float
    {
        return (float) $this->insumo_cantidad;
    }

    public function getCostoEntradaProperty(): float
    {
        return round($this->insumo_cantidad * $this->insumo_precio_costo, 2);
    }

    public function getTotalSalidaProperty(): float
    {
        return array_sum(array_column($this->productos, 'cantidad'));
    }

    public function getMermaProperty(): float
    {
        return max(0, $this->total_entrada - $this->total_salida);
    }

    public function getMermaPorcentajeProperty(): float
    {
        return $this->total_entrada > 0
            ? round(($this->merma / $this->total_entrada) * 100, 2)
            : 0;
    }

    public function getCostoPromedioProperty(): float
    {
        return $this->total_salida > 0
            ? round($this->costo_entrada / $this->total_salida, 4)
            : 0;
    }

    // ── Guardar borrador ───────────────────────────────────────────────────

    public function guardar(): void
    {
        if ($this->produccion->estado !== 'abierta') return;

        $this->validate([
            'fecha'              => 'required|date',
            'insumo_articulo_id' => 'required|exists:articulos,id',
            'insumo_cantidad'    => 'required|numeric|min:0.001',
        ]);

        DB::transaction(function () {
            $this->produccion->update([
                'fecha'           => $this->fecha,
                'tipo_produccion' => $this->tipo_produccion,
                'empleado_id'     => $this->empleado_id,
                'observacion'     => $this->observacion ?: null,
            ]);

            // Reemplaza el insumo
            $this->produccion->insumos()->delete();
            $subtotalE = round($this->insumo_cantidad * $this->insumo_precio_costo, 2);
            DetProduccion::create([
                'produccion_id' => $this->produccion->id,
                'articulo_id'   => $this->insumo_articulo_id,
                'tipo'          => 'E',
                'cantidad'      => $this->insumo_cantidad,
                'precio_costo'  => $this->insumo_precio_costo,
                'subtotal'      => $subtotalE,
                'orden'         => 0,
            ]);

            // Reemplaza productos
            $this->produccion->productos()->delete();
            foreach ($this->productos as $i => $p) {
                if (empty($p['articulo_id']) || (float)$p['cantidad'] <= 0) continue;
                DetProduccion::create([
                    'produccion_id' => $this->produccion->id,
                    'articulo_id'   => $p['articulo_id'],
                    'tipo'          => 'I',
                    'cantidad'      => $p['cantidad'],
                    'precio_costo'  => $p['precio_costo'] ?? 0,
                    'subtotal'      => round($p['cantidad'] * ($p['precio_costo'] ?? 0), 2),
                    'orden'         => $i,
                ]);
            }
        });

        // Refresca relaciones
        $this->produccion->load(['insumos.articulo', 'productos.articulo']);
        session()->flash('success', 'Borrador guardado.');
    }

    // ── Cerrar producción (impacta stock) ──────────────────────────────────

    public function cerrar(): void
    {
        $this->validate();

        DB::transaction(function () {
            // Persistir detalle actualizado antes de cerrar
            $this->sincronizarDetalle();

            $produccion = $this->produccion->fresh(['insumos.articulo', 'productos.articulo']);

            $costoTotal = $produccion->insumos->sum('subtotal');
            $totalSalida = $produccion->productos->sum('cantidad');
            $costoPromedio = $totalSalida > 0 ? round($costoTotal / $totalSalida, 4) : 0;

            // Decrementar stock de insumos (tipo E)
            foreach ($produccion->insumos as $det) {
                $art = $det->articulo;
                $stockAntes = $art->stock;
                $art->decrement('stock', $det->cantidad);
                MovimientoArticulo::create([
                    'articulo_id'     => $art->id,
                    'user_id'         => auth()->id(),
                    'fecha'           => $produccion->fecha,
                    'tipo'            => 'salida',
                    'cantidad'        => $det->cantidad,
                    'stock_anterior'  => $stockAntes,
                    'stock_posterior' => $art->fresh()->stock,
                    'referencia_type' => Produccion::class,
                    'referencia_id'   => $produccion->id,
                    'es_minorista'    => false,
                    'observacion'     => "Insumo producción #{$produccion->nro_produccion}",
                ]);
            }

            // Incrementar stock de productos (tipo I) y actualizar precio_costo
            foreach ($produccion->productos as $det) {
                $art = $det->articulo;
                $stockAntes = $art->stock;
                $art->increment('stock', $det->cantidad);
                $art->update(['precio_costo' => $costoPromedio]);
                MovimientoArticulo::create([
                    'articulo_id'     => $art->id,
                    'user_id'         => auth()->id(),
                    'fecha'           => $produccion->fecha,
                    'tipo'            => 'produccion',
                    'cantidad'        => $det->cantidad,
                    'stock_anterior'  => $stockAntes,
                    'stock_posterior' => $art->fresh()->stock,
                    'referencia_type' => Produccion::class,
                    'referencia_id'   => $produccion->id,
                    'es_minorista'    => false,
                    'observacion'     => "Producto producción #{$produccion->nro_produccion}",
                ]);
            }

            // Calcular ganancia aproximada (diferencia de valuación)
            $valorSalida = $produccion->productos->sum(fn ($d) => $d->cantidad * $d->precio_costo);
            $ganancia = round($valorSalida - $costoTotal, 2);

            $produccion->update([
                'estado'         => 'cerrada',
                'costo_total'    => $costoTotal,
                'costo_promedio' => $costoPromedio,
                'ganancia'       => $ganancia,
            ]);
        });

        $this->produccion->refresh()->load(['insumos.articulo', 'productos.articulo']);
        $this->confirmandoCierre = false;
        session()->flash('success', 'Producción cerrada. Stock actualizado correctamente.');
    }

    public function anular(): void
    {
        if ($this->produccion->estado === 'cerrada') return;

        $this->produccion->update(['estado' => 'anulada']);
        $this->confirmandoAnulado = false;
        session()->flash('success', 'Producción anulada.');
    }

    private function sincronizarDetalle(): void
    {
        $this->produccion->update([
            'fecha'           => $this->fecha,
            'tipo_produccion' => $this->tipo_produccion,
            'empleado_id'     => $this->empleado_id,
            'observacion'     => $this->observacion ?: null,
        ]);

        $this->produccion->insumos()->delete();
        DetProduccion::create([
            'produccion_id' => $this->produccion->id,
            'articulo_id'   => $this->insumo_articulo_id,
            'tipo'          => 'E',
            'cantidad'      => $this->insumo_cantidad,
            'precio_costo'  => $this->insumo_precio_costo,
            'subtotal'      => round($this->insumo_cantidad * $this->insumo_precio_costo, 2),
            'orden'         => 0,
        ]);

        $this->produccion->productos()->delete();
        foreach ($this->productos as $i => $p) {
            if (empty($p['articulo_id']) || (float)$p['cantidad'] <= 0) continue;
            DetProduccion::create([
                'produccion_id' => $this->produccion->id,
                'articulo_id'   => $p['articulo_id'],
                'tipo'          => 'I',
                'cantidad'      => $p['cantidad'],
                'precio_costo'  => $p['precio_costo'] ?? 0,
                'subtotal'      => round($p['cantidad'] * ($p['precio_costo'] ?? 0), 2),
                'orden'         => $i,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.produccion.detalle', [
            'articulosInsumo'   => Articulo::where('estado', 'A')
                ->where('acepta_subrubro', true)
                ->orderBy('descripcion')
                ->get(),
            'articulosProducto' => Articulo::where('estado', 'A')
                ->orderBy('descripcion')
                ->get(),
            'empleados'         => Empleado::where('estado', 'A')->orderBy('nombre')->get(),
        ]);
    }
}
