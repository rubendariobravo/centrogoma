<?php

namespace App\Services\Pricing;

use App\Models\AjustePrecio;
use App\Models\Articulo;
use App\Models\ArticuloPrecio;
use App\Models\ListaPrecio;
use App\Models\Marca;
use App\Models\Rubro;
use App\Support\Pricing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ajustes masivos de precios de una lista, con redondeo, vista previa e historial reversible.
 *
 * Dos modos:
 *  - porcentaje: sube/baja el precio FINAL de cada tramo un % y lo redondea.
 *  - costo (como el Delphi): sube/baja el costo un % y el precio pasa a costo × (1+IVA) × (1+margen),
 *    redondeado. Solo artículos con costo; los demás tramos del artículo siguen en la misma proporción.
 *
 * Los precios se guardan NETOS (4 decimales); el redondeo se hace sobre el final, que es el que ve
 * el cliente. Cada ajuste queda en ajustes_precios / ajuste_precio_items con el precio anterior.
 *
 * Las escrituras van por query builder y en lote (sin eventos de Eloquent): son miles de filas y el
 * historial propio reemplaza a la auditoría por fila.
 */
class AjustePrecios
{
    /** Pasos de redondeo del precio final ofrecidos en pantalla (0 = centavos). */
    public const REDONDEOS = [0 => 'Sin redondeo', 1 => 'A $1', 10 => 'A $10', 50 => 'A $50', 100 => 'A $100'];

    /** Muestra de filas en la vista previa. */
    private const EJEMPLOS = 8;

    /**
     * Redondea un precio final al paso pedido, sin que el paso supere el 10% del precio: un artículo
     * de $9,60 no pasa a $0 ni a $100 con "redondeo a $100" (baja a $1 o a centavos).
     */
    public static function redondear(float $final, float $paso): float
    {
        foreach ([100, 50, 10, 1] as $p) {
            if ($p <= $paso && $final >= $p * 10) {
                return round($final / $p) * $p;
            }
        }

        return round($final, 2);
    }

    /** Precio final sugerido por costo y margen (fórmula del Delphi). Null si no hay costo. */
    public static function precioSugerido(float $costo, float $iva, float $margen): ?float
    {
        return $costo > 0 ? round($costo * (1 + $iva / 100) * (1 + $margen / 100), 2) : null;
    }

    /** Margen real (%) de un precio final respecto del costo. Null si no hay costo. */
    public static function margenReal(float $final, float $costo, float $iva): ?float
    {
        return $costo > 0 ? round(($final / ($costo * (1 + $iva / 100)) - 1) * 100, 1) : null;
    }

    /**
     * Calcula el ajuste sin grabar nada.
     *
     * @param  array{modo:string,tipo:string,porcentaje:float,redondeo:float,filtros:array}  $p
     * @return array{filas:Collection,omitidos:int}
     */
    public function calcular(int $listaId, array $p): array
    {
        $pct    = (float) $p['porcentaje'];
        $factor = $p['tipo'] === 'bajar' ? 1 - $pct / 100 : 1 + $pct / 100;
        $paso   = (float) ($p['redondeo'] ?? 0);

        $tramos = $this->tramos($listaId, $p['filtros'] ?? [])->get()->groupBy('articulo_id');

        $filas    = collect();
        $omitidos = 0;

        foreach ($tramos as $deArticulo) {
            $art = $deArticulo->first()->articulo;
            $iva = (float) $art->alicuota_iva;
            $base = $deArticulo->sortBy([['cantidad_desde', 'asc'], ['id', 'asc']])->first();

            $costoNuevo = null;
            if ($p['modo'] === 'costo') {
                $sugerido = self::precioSugerido((float) $art->precio_costo * $factor, $iva, (float) $art->margen);
                if ($sugerido === null) {
                    $omitidos++;
                    continue;
                }
                $costoNuevo = round((float) $art->precio_costo * $factor, 4);
                $finalBase  = Pricing::final((float) $base->precio, $iva);
                // El tramo base va al sugerido; los demás tramos mantienen su proporción.
                $factorArt  = $finalBase > 0 ? $sugerido / $finalBase : 1;
            }

            foreach ($deArticulo as $tramo) {
                $antes = Pricing::final((float) $tramo->precio, $iva);
                $despues = $p['modo'] === 'costo'
                    ? self::redondear($tramo->id === $base->id ? $sugerido : $antes * $factorArt, $paso)
                    : self::redondear($antes * $factor, $paso);
                $neto = Pricing::neto($despues, $iva);

                if (abs($neto - (float) $tramo->precio) < 0.00005 && $costoNuevo === null) {
                    continue;   // no cambia
                }

                $filas->push([
                    'tramo_id'       => $tramo->id,
                    'articulo_id'    => $art->id,
                    'codigo'         => $art->codigo,
                    'descripcion'    => $art->descripcion,
                    'final_antes'    => $antes,
                    'final_despues'  => $despues,
                    'neto_antes'     => (float) $tramo->precio,
                    'neto_despues'   => $neto,
                    'costo_antes'    => $costoNuevo !== null ? (float) $art->precio_costo : null,
                    'costo_despues'  => $tramo->id === $base->id ? $costoNuevo : null,
                ]);
            }
        }

        return ['filas' => $filas, 'omitidos' => $omitidos];
    }

    /** Cantidad de tramos que cambian, omitidos y una muestra de antes/después. */
    public function previsualizar(int $listaId, array $p): array
    {
        $r = $this->calcular($listaId, $p);

        return [
            'cantidad'  => $r['filas']->count(),
            'articulos' => $r['filas']->pluck('articulo_id')->unique()->count(),
            'omitidos'  => $r['omitidos'],
            'ejemplos'  => $r['filas']->take(self::EJEMPLOS)->values()->all(),
        ];
    }

    /** Aplica el ajuste y lo registra en el historial. */
    public function aplicar(int $listaId, array $p): AjustePrecio
    {
        $filas = $this->calcular($listaId, $p)['filas'];

        return DB::transaction(function () use ($listaId, $p, $filas) {
            $ajuste = AjustePrecio::create([
                'lista_precio_id' => $listaId,
                'user_id'         => auth()->id(),
                'modo'            => $p['modo'],
                'tipo'            => $p['tipo'],
                'porcentaje'      => $p['porcentaje'],
                'redondeo'        => $p['redondeo'] ?? 0,
                'filtros'         => $p['filtros'] ?? [],
                'descripcion'     => $this->describir($p),
                'cantidad'        => $filas->count(),
            ]);

            foreach ($filas->chunk(500) as $lote) {
                DB::table('ajuste_precio_items')->insert($lote->map(fn ($f) => [
                    'ajuste_precio_id'   => $ajuste->id,
                    'articulo_precio_id' => $f['tramo_id'],
                    'articulo_id'        => $f['articulo_id'],
                    'precio_anterior'    => $f['neto_antes'],
                    'precio_nuevo'       => $f['neto_despues'],
                    'costo_anterior'     => $f['costo_despues'] !== null ? $f['costo_antes'] : null,
                    'costo_nuevo'        => $f['costo_despues'],
                ])->values()->all());
            }

            self::actualizarEnLote('articulo_precios', 'precio', $filas->pluck('neto_despues', 'tramo_id')->all());
            $costos = $filas->whereNotNull('costo_despues')->pluck('costo_despues', 'articulo_id')->all();
            self::actualizarEnLote('articulos', 'precio_costo', $costos);

            if (ListaPrecio::whereKey($listaId)->value('es_default')) {
                self::sincronizarPrecioBase($filas->pluck('articulo_id')->unique()->all());
            }

            return $ajuste;
        });
    }

    /**
     * Deshace un ajuste: cada tramo vuelve al precio anterior, salvo los que se cambiaron después
     * por otra vía (se dejan como están y se informan).
     *
     * @return array{revertidos:int,omitidos:int}
     */
    public function revertir(AjustePrecio $ajuste): array
    {
        if ($ajuste->revertido_at) {
            return ['revertidos' => 0, 'omitidos' => 0];
        }

        return DB::transaction(function () use ($ajuste) {
            $items   = $ajuste->items()->get();
            $actuales = ArticuloPrecio::whereIn('id', $items->pluck('articulo_precio_id'))->pluck('precio', 'id');
            $costos   = Articulo::whereIn('id', $items->whereNotNull('costo_nuevo')->pluck('articulo_id'))->pluck('precio_costo', 'id');

            $precios = $nuevosCostos = [];
            $omitidos = 0;
            foreach ($items as $it) {
                $actual = $actuales[$it->articulo_precio_id] ?? null;
                if ($actual === null || abs((float) $actual - (float) $it->precio_nuevo) > 0.00005) {
                    $omitidos++;
                    continue;
                }
                $precios[$it->articulo_precio_id] = (float) $it->precio_anterior;
                if ($it->costo_nuevo !== null && abs((float) ($costos[$it->articulo_id] ?? 0) - (float) $it->costo_nuevo) < 0.00005) {
                    $nuevosCostos[$it->articulo_id] = (float) $it->costo_anterior;
                }
            }

            self::actualizarEnLote('articulo_precios', 'precio', $precios);
            self::actualizarEnLote('articulos', 'precio_costo', $nuevosCostos);
            if ($ajuste->lista?->es_default) {
                self::sincronizarPrecioBase($items->pluck('articulo_id')->unique()->all());
            }

            $ajuste->update(['revertido_at' => now(), 'revertido_por' => auth()->id()]);

            return ['revertidos' => count($precios), 'omitidos' => $omitidos];
        });
    }

    /**
     * articulos.precio_unitario = precio del tramo base (menor "desde") de la lista default. El motor
     * lo usa cuando una lista no tiene tramo para el artículo: si queda viejo, se cobra el precio viejo.
     *
     * @param  array<int>|null  $articuloIds  null = todos
     */
    public static function sincronizarPrecioBase(?array $articuloIds = null): int
    {
        $default = ListaPrecio::where('es_default', true)->value('id');
        if (! $default || $articuloIds === []) {
            return 0;
        }

        $base = DB::table('articulo_precios')
            ->where('lista_precio_id', $default)
            ->when($articuloIds !== null, fn ($q) => $q->whereIn('articulo_id', $articuloIds))
            ->orderByDesc('activo')->orderBy('cantidad_desde')->orderBy('id')
            ->get(['articulo_id', 'precio'])
            ->unique('articulo_id')
            ->pluck('precio', 'articulo_id');

        $actuales = DB::table('articulos')->whereIn('id', $base->keys())->pluck('precio_unitario', 'id');
        $cambios  = $base->filter(fn ($p, $id) => abs((float) $p - (float) ($actuales[$id] ?? 0)) > 0.00005)->all();

        self::actualizarEnLote('articulos', 'precio_unitario', $cambios);

        return count($cambios);
    }

    /** UPDATE ... SET col = CASE id ... en lotes de 500. */
    public static function actualizarEnLote(string $tabla, string $columna, array $valoresPorId): void
    {
        foreach (array_chunk($valoresPorId, 500, true) as $lote) {
            $casos = $bindings = [];
            foreach ($lote as $id => $valor) {
                $casos[]    = 'WHEN ? THEN ?';
                $bindings[] = $id;
                $bindings[] = $valor;
            }
            $ids = array_keys($lote);
            DB::update(
                "UPDATE {$tabla} SET {$columna} = CASE id " . implode(' ', $casos) . ' END WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')',
                array_merge($bindings, $ids)
            );
        }
    }

    /**
     * Artículos cuyo precio (tramo base) quedó con un margen real menor al configurado.
     *
     * @param  array<int>  $articuloIds
     * @return Collection<int, array{descripcion:string,margen:float,margen_real:float}>
     */
    public static function conMargenBajo(array $articuloIds): Collection
    {
        return Articulo::whereIn('id', $articuloIds)->where('precio_costo', '>', 0)->where('margen', '>', 0)->get()
            ->map(fn ($a) => [
                'descripcion' => $a->descripcion,
                'margen'      => (float) $a->margen,
                'margen_real' => self::margenReal(Pricing::final((float) $a->precio_unitario, (float) $a->alicuota_iva), (float) $a->precio_costo, (float) $a->alicuota_iva),
            ])
            ->filter(fn ($a) => $a['margen_real'] !== null && $a['margen_real'] < $a['margen'] - 0.5)
            ->values();
    }

    private function tramos(int $listaId, array $f)
    {
        return ArticuloPrecio::query()
            ->with('articulo:id,codigo,descripcion,alicuota_iva,precio_costo,margen')
            ->where('lista_precio_id', $listaId)
            ->whereHas('articulo', function ($q) use ($f) {
                $q->when($f['rubro_id'] ?? null, fn ($w, $v) => $w->where('rubro_id', $v))
                  ->when($f['marca_id'] ?? null, fn ($w, $v) => $w->where('marca_id', $v))
                  ->when($f['articulo_ids'] ?? null, fn ($w, $v) => $w->whereIn('id', $v))
                  ->when($f['search'] ?? null, fn ($w, $v) => $w->where(fn ($s) => $s
                      ->where('codigo', 'like', "%{$v}%")->orWhere('descripcion', 'like', "%{$v}%")));
            });
    }

    private function describir(array $p): string
    {
        $f = $p['filtros'] ?? [];
        $partes = [
            ($p['tipo'] === 'bajar' ? '−' : '+') . rtrim(rtrim(number_format((float) $p['porcentaje'], 3, ',', ''), '0'), ',') . '%'
                . ($p['modo'] === 'costo' ? ' sobre el costo (precio = costo × IVA × margen)' : ''),
            ($p['redondeo'] ?? 0) > 0 ? 'redondeo a $' . (int) $p['redondeo'] : 'sin redondeo',
        ];
        if (! empty($f['rubro_id']))     $partes[] = 'rubro ' . Rubro::whereKey($f['rubro_id'])->value('descripcion');
        if (! empty($f['marca_id']))     $partes[] = 'marca ' . Marca::whereKey($f['marca_id'])->value('descripcion');
        if (! empty($f['search']))       $partes[] = "búsqueda \"{$f['search']}\"";
        if (! empty($f['articulo_ids'])) $partes[] = count($f['articulo_ids']) . ' artículos elegidos';

        return implode(' · ', $partes);
    }
}
