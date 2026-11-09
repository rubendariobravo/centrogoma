<?php

namespace App\Http\Controllers;

use App\Exports\ListaPreciosExport;
use App\Models\Articulo;
use App\Models\ListaPrecio;
use App\Models\Rubro;
use App\Models\VariableEntorno;
use App\Support\Pricing;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListasController extends Controller
{
    public function exportarPrecios(int $lista): BinaryFileResponse
    {
        $listaPrecio = ListaPrecio::findOrFail($lista);

        $verNetos    = (bool)   request('verNetos', false);
        $filterRubro = (string) request('rubro', '');
        $search      = (string) request('search', '');

        $filename = 'precios-' . str($listaPrecio->codigo)->slug() . '.xlsx';

        return Excel::download(
            new ListaPreciosExport($lista, $verNetos, $filterRubro, $search),
            $filename,
        );
    }

    /**
     * Lista de precios para imprimir (o guardar como PDF desde el navegador), agrupada por rubro, con los
     * mismos filtros que la pantalla. HTML y no DomPDF: con los ~7.000 artículos DomPDF tarda minutos.
     */
    public function imprimirPrecios(int $lista)
    {
        $listaPrecio = ListaPrecio::findOrFail($lista);
        $verNetos    = (bool) request('verNetos', false);
        $rubroId     = (string) request('rubro', '');
        $search      = (string) request('search', '');
        $ids         = array_filter(array_map('intval', explode(',', (string) request('ids', ''))));

        $articulos = Articulo::query()
            ->where('estado', 'A')
            ->with(['precios' => fn ($q) => $q->where('lista_precio_id', $lista)->where('activo', true)->orderBy('cantidad_desde')])
            ->when($ids, fn ($q) => $q->whereIn('id', $ids))
            ->when($rubroId, fn ($q) => $q->where('rubro_id', $rubroId))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('codigo', 'like', "%{$search}%")
                ->orWhere('descripcion', 'like', "%{$search}%")))
            ->orderBy('descripcion')
            ->get(['id', 'codigo', 'descripcion', 'rubro_id', 'unidad_medida', 'alicuota_iva', 'precio_unitario']);

        $rubros = Rubro::whereIn('id', $articulos->pluck('rubro_id')->filter()->unique())->pluck('descripcion', 'id');

        $grupos = $articulos
            ->map(function ($a) use ($verNetos) {
                $iva    = (float) $a->alicuota_iva;
                $precio = fn ($neto) => $verNetos ? round((float) $neto, 2) : Pricing::final((float) $neto, $iva);
                // Sin tramo en esta lista: el motor cobra el precio base del artículo.
                $tramos = $a->precios->isNotEmpty()
                    ? $a->precios->map(fn ($t) => ['desde' => (float) $t->cantidad_desde, 'precio' => $precio($t->precio)])
                    : collect([['desde' => 1.0, 'precio' => $precio($a->precio_unitario)]]);

                return ['codigo' => $a->codigo, 'descripcion' => $a->descripcion, 'unidad' => $a->unidad_medida,
                        'rubro_id' => $a->rubro_id, 'tramos' => $tramos->values()];
            })
            ->groupBy(fn ($a) => $rubros[$a['rubro_id']] ?? 'Sin rubro')
            ->sortKeys();

        return view('listas.imprimir', [
            'lista'    => $listaPrecio,
            'grupos'   => $grupos,
            'cantidad' => $articulos->count(),
            'verNetos' => $verNetos,
            'filtro'   => collect([$rubroId ? 'Rubro: ' . ($rubros[$rubroId] ?? '') : null,
                                   $search ? "Búsqueda: «{$search}»" : null,
                                   $ids ? count($ids) . ' artículos seleccionados' : null])->filter()->implode(' · '),
            'empresa'  => VariableEntorno::get('empresa_nombre', config('app.name')),
        ]);
    }
}
