<?php

namespace App\Livewire\Estadisticas;

use App\Models\DetCompra;
use App\Models\Proveedor;
use App\Models\Rubro;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Proveedores top')]
class Proveedores extends Component
{
    public string $desde = '';
    public string $hasta = '';

    #[Url]
    public int $topN = 20;           // 10 | 20 | 50 | 0 (todos)

    #[Url]
    public ?int $proveedorId = null; // filtro por proveedor puntual

    #[Url]
    public ?int $rubroId = null;     // filtro por rubro (restringe el monto a ese rubro)

    public function mount(): void
    {
        $this->desde = now()->startOfMonth()->toDateString();
        $this->hasta = now()->toDateString();
    }

    /**
     * Query base sobre det_compras ⋈ compras (+ articulos si hay filtro de rubro).
     * Solo compras reales: no anuladas y procesadas (igual que el panel de estadísticas).
     */
    private function detBase()
    {
        $q = DetCompra::query()
            ->join('compras', 'compras.id', '=', 'det_compras.compra_id')
            ->where('compras.estado', '!=', 'A')
            ->where('compras.procesada', true)
            ->whereBetween('compras.fecha', [$this->desde, $this->hasta]);

        if ($this->proveedorId) {
            $q->where('compras.proveedor_id', $this->proveedorId);
        }

        if ($this->rubroId) {
            $q->join('articulos', 'articulos.id', '=', 'det_compras.articulo_id')
                ->where('articulos.rubro_id', $this->rubroId);
        }

        return $q;
    }

    public function render()
    {
        // Monto c/IVA por línea: subtotal neto + IVA guardado en el renglón.
        $montoExpr = 'SUM(det_compras.subtotal + det_compras.subtotal_iva)';

        /* --------------------------- TOTALES DEL PERÍODO --------------------------- */
        $totales = $this->detBase()
            ->selectRaw("COUNT(DISTINCT compras.proveedor_id) as proveedores, COUNT(DISTINCT compras.id) as comprobantes, {$montoExpr} as monto")
            ->first();

        $totalMonto      = (float) ($totales->monto ?? 0);
        $proveedoresCount = (int) ($totales->proveedores ?? 0);
        $comprobantes    = (int) ($totales->comprobantes ?? 0);
        $ticketProm      = $comprobantes > 0 ? $totalMonto / $comprobantes : 0;

        /* ---------------------------- RANKING PROVEEDORES -------------------------- */
        $rankingQuery = $this->detBase()
            ->leftJoin('proveedores', 'proveedores.id', '=', 'compras.proveedor_id')
            ->selectRaw("compras.proveedor_id, COALESCE(proveedores.razon_social, 'Sin proveedor') as proveedor, COUNT(DISTINCT compras.id) as comprobantes, {$montoExpr} as monto")
            ->groupBy('compras.proveedor_id', 'proveedores.razon_social')
            ->orderByDesc('monto');

        if ($this->topN > 0) {
            $rankingQuery->limit($this->topN);
        }
        $ranking = $rankingQuery->get();

        /* --------------------- ARTÍCULO MÁS COMPRADO POR PROVEEDOR ----------------- */
        // Por monto: como viene ordenado desc, la primera fila de cada proveedor es su top.
        $articulos = $this->detBase()
            ->join('articulos as art', 'art.id', '=', 'det_compras.articulo_id')
            ->selectRaw("compras.proveedor_id, art.descripcion, SUM(det_compras.cantidad) as cantidad, {$montoExpr} as monto")
            ->groupBy('compras.proveedor_id', 'art.descripcion')
            ->orderByDesc('monto')
            ->get();

        $topArticulo = [];
        foreach ($articulos as $a) {
            $k = $a->proveedor_id ?? 0;
            if (! isset($topArticulo[$k])) {
                $topArticulo[$k] = [
                    'descripcion' => $a->descripcion,
                    'cantidad'    => (float) $a->cantidad,
                    'monto'       => (float) $a->monto,
                ];
            }
        }

        /* -------------------------------- GRÁFICOS -------------------------------- */
        $barLabels = $ranking->map(fn ($r) => Str::limit($r->proveedor, 24))->values()->toArray();
        $barMontos = $ranking->map(fn ($r) => round((float) $r->monto, 2))->values()->toArray();

        // Donut de concentración: top 5 + "Resto".
        $top5      = $ranking->take(5);
        $sumTop5   = (float) $top5->sum('monto');
        $donutLbls = $top5->map(fn ($r) => Str::limit($r->proveedor, 20))->values()->toArray();
        $donutData = $top5->map(fn ($r) => round((float) $r->monto, 2))->values()->toArray();
        $resto     = max($totalMonto - $sumTop5, 0);
        if ($resto > 0.01) {
            $donutLbls[] = 'Resto de proveedores';
            $donutData[] = round($resto, 2);
        }

        $chartData = [
            'bar'   => ['labels' => $barLabels, 'montos' => $barMontos],
            'donut' => ['labels' => $donutLbls, 'montos' => $donutData],
        ];

        /* ------------------------------ SELECTS DE FILTRO -------------------------- */
        $proveedores = Proveedor::where('estado', 'A')->orderBy('razon_social')->get(['id', 'razon_social']);
        $rubros      = Rubro::where('estado', 'A')->orderBy('descripcion')->get(['id', 'descripcion']);

        return view('livewire.estadisticas.proveedores', compact(
            'ranking', 'topArticulo', 'totalMonto', 'proveedoresCount', 'comprobantes', 'ticketProm',
            'chartData', 'proveedores', 'rubros',
        ));
    }
}
