<?php

namespace App\Livewire\Estadisticas;

use App\Livewire\Concerns\BuscaCliente;
use App\Models\DetVenta;
use App\Models\Rubro;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Clientes top')]
class Clientes extends Component
{
    use BuscaCliente;

    public string $desde = '';
    public string $hasta = '';

    #[Url]
    public int $topN = 20;          // 10 | 20 | 50 | 0 (todos)

    #[Url]
    public ?int $clienteId = null;  // filtro por cliente puntual

    #[Url]
    public ?int $rubroId = null;    // filtro por rubro (restringe el monto a ese rubro)

    protected function clienteCampo(): string
    {
        return 'clienteId';
    }

    public function mount(): void
    {
        $this->desde = now()->startOfMonth()->toDateString();
        $this->hasta = now()->toDateString();
    }

    /**
     * Query base sobre det_ventas ⋈ ventas (+ articulos si hay filtro de rubro).
     * Excluye anuladas y notas de crédito, igual que el resto de estadísticas.
     */
    private function detBase()
    {
        $q = DetVenta::query()
            ->join('ventas', 'ventas.id', '=', 'det_ventas.venta_id')
            ->where('ventas.estado', '!=', 'A')
            ->where('ventas.tipo_comprobante', '!=', 'nota_credito')
            ->whereBetween('ventas.fecha', [$this->desde, $this->hasta]);

        if ($this->clienteId) {
            $q->where('ventas.cliente_id', $this->clienteId);
        }

        if ($this->rubroId) {
            $q->join('articulos', 'articulos.id', '=', 'det_ventas.articulo_id')
                ->where('articulos.rubro_id', $this->rubroId);
        }

        return $q;
    }

    public function render()
    {
        // Monto c/IVA por línea: subtotal neto + IVA guardado en el renglón.
        $montoExpr = 'SUM(det_ventas.subtotal + det_ventas.subtotal_iva)';

        /* --------------------------- TOTALES DEL PERÍODO --------------------------- */
        $totales = $this->detBase()
            ->selectRaw("COUNT(DISTINCT ventas.cliente_id) as clientes, COUNT(DISTINCT ventas.id) as comprobantes, {$montoExpr} as monto")
            ->first();

        $totalMonto    = (float) ($totales->monto ?? 0);
        $clientesCount = (int) ($totales->clientes ?? 0);
        $comprobantes  = (int) ($totales->comprobantes ?? 0);
        $ticketProm    = $comprobantes > 0 ? $totalMonto / $comprobantes : 0;

        /* ------------------------------ RANKING CLIENTES --------------------------- */
        $rankingQuery = $this->detBase()
            ->leftJoin('clientes', 'clientes.id', '=', 'ventas.cliente_id')
            ->selectRaw("ventas.cliente_id, COALESCE(clientes.razon_social, 'Consumidor final / sin cliente') as cliente, COUNT(DISTINCT ventas.id) as comprobantes, {$montoExpr} as monto")
            ->groupBy('ventas.cliente_id', 'clientes.razon_social')
            ->orderByDesc('monto');

        if ($this->topN > 0) {
            $rankingQuery->limit($this->topN);
        }
        $ranking = $rankingQuery->get();

        /* --------------------- ARTÍCULO MÁS COMPRADO POR CLIENTE ------------------- */
        // Por monto: como viene ordenado desc, la primera fila de cada cliente es su top.
        $articulos = $this->detBase()
            ->selectRaw("ventas.cliente_id, det_ventas.descripcion, SUM(det_ventas.cantidad) as cantidad, {$montoExpr} as monto")
            ->groupBy('ventas.cliente_id', 'det_ventas.descripcion')
            ->orderByDesc('monto')
            ->get();

        $topArticulo = [];
        foreach ($articulos as $a) {
            $k = $a->cliente_id ?? 0;
            if (! isset($topArticulo[$k])) {
                $topArticulo[$k] = [
                    'descripcion' => $a->descripcion,
                    'cantidad'    => (float) $a->cantidad,
                    'monto'       => (float) $a->monto,
                ];
            }
        }

        /* -------------------------------- GRÁFICOS -------------------------------- */
        $barLabels = $ranking->map(fn ($r) => \Illuminate\Support\Str::limit($r->cliente, 24))->values()->toArray();
        $barMontos = $ranking->map(fn ($r) => round((float) $r->monto, 2))->values()->toArray();

        // Donut de concentración: top 5 + "Resto".
        $top5      = $ranking->take(5);
        $sumTop5   = (float) $top5->sum('monto');
        $donutLbls = $top5->map(fn ($r) => \Illuminate\Support\Str::limit($r->cliente, 20))->values()->toArray();
        $donutData = $top5->map(fn ($r) => round((float) $r->monto, 2))->values()->toArray();
        $resto     = max($totalMonto - $sumTop5, 0);
        if ($resto > 0.01) {
            $donutLbls[] = 'Resto de clientes';
            $donutData[] = round($resto, 2);
        }

        $chartData = [
            'bar'   => ['labels' => $barLabels, 'montos' => $barMontos],
            'donut' => ['labels' => $donutLbls, 'montos' => $donutData],
        ];

        /* ------------------------------ SELECTS DE FILTRO -------------------------- */
        $rubros   = Rubro::where('estado', 'A')->orderBy('descripcion')->get(['id', 'descripcion']);

        return view('livewire.estadisticas.clientes', compact(
            'ranking', 'topArticulo', 'totalMonto', 'clientesCount', 'comprobantes', 'ticketProm',
            'chartData', 'rubros',
        ));
    }
}
