<?php

namespace App\Livewire\Estadisticas;

use App\Models\AnticipoEmpleado;
use App\Models\Compra;
use App\Models\DetVenta;
use App\Models\FormaPago;
use App\Models\Gasto;
use App\Models\MovimientoCaja;
use App\Models\PagoVenta;
use App\Models\ReciboDetalle;
use App\Models\Venta;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Estadísticas')]
class Index extends Component
{
    #[Url(as: 'tab')]
    public string $tab = 'ventas';   // ventas | compras | gastos | sueldos (anticipos) | resumen

    /**
     * Lectura del donut de formas de pago:
     *  - 'real'    → cómo entró realmente la plata (incluye la cobranza posterior de la CC)
     *  - 'emision' → cómo se pactó el pago al emitir el comprobante (la lectura histórica)
     */
    #[Url(as: 'fp')]
    public string $modoFormasPago = 'real';

    public string $desde = '';
    public string $hasta = '';
    public string $agrupacion = 'dia';  // dia | mes | anio

    /**
     * Agrupación de presentación de las formas de pago.
     * Tarjeta de crédito y débito van juntas: separadas, crédito queda en una astilla
     * ilegible del donut, y el par naranja/amarillo que exigiría es el único que no
     * pasa el control de daltonismo. Los cheques van aparte: son un valor en cartera,
     * no plata acreditada.
     */
    private const BUCKETS = [
        'efectivo'      => ['label' => 'Efectivo',      'color' => '#2a78d6'],
        'tarjetas'      => ['label' => 'Tarjetas',      'color' => '#199e70'],
        'transferencia' => ['label' => 'Transferencia', 'color' => '#eb6834'],
        'cheque'        => ['label' => 'Cheques',       'color' => '#4a3aa7'],
    ];

    /** Códigos de forma_pago que caen en cada bucket. El resto conserva su descripción propia. */
    private const MAPA_BUCKETS = [
        'efectivo'      => 'efectivo',
        'transferencia' => 'transferencia',
        'tarjeta'       => 'tarjetas',
        'debito'        => 'tarjetas',
        'cheque'        => 'cheque',
    ];

    /**
     * Color del residuo — el mismo gris en los dos modos. "Pendiente de cobro" y "Vendido a
     * crédito" son la misma clase de cosa: lo que queda cuando se restan los medios de cobro,
     * no un medio más. Nunca aparecen juntos (son modos alternativos), así que compartir el
     * tono neutro los distingue de las series reales sin competir con ellas. Es oscuro para
     * que la etiqueta de % en blanco se lea encima.
     */
    private const COLOR_RESIDUO = '#6f6c66';
    private const COLOR_OTROS   = '#eda100';

    public function mount(): void
    {
        $this->desde = now()->startOfMonth()->toDateString();
        $this->hasta = now()->toDateString();
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function setModoFormasPago(string $modo): void
    {
        $this->modoFormasPago = $modo === 'emision' ? 'emision' : 'real';
    }

    /** Formato MySQL DATE_FORMAT según la agrupación elegida (valor controlado, sin inyección). */
    private function formato(): string
    {
        return match ($this->agrupacion) {
            'mes'  => '%Y-%m',
            'anio' => '%Y',
            default => '%Y-%m-%d',
        };
    }

    /**
     * Query base de ventas reales del período: excluye anuladas, notas de crédito y los
     * comprobantes de saldo inicial (deuda importada del Delphi, no es venta del período).
     */
    private function ventasBase()
    {
        return Venta::query()
            ->comerciales()
            ->where('estado', '!=', 'A')
            ->where('tipo_comprobante', '!=', 'nota_credito')
            ->whereBetween('fecha', [$this->desde, $this->hasta]);
    }

    /** Cobros registrados al emitir el comprobante (contado), agrupados por forma de pago. */
    private function cobrosEnLaVenta(): Collection
    {
        return PagoVenta::query()
            ->join('ventas', 'ventas.id', '=', 'pago_ventas.venta_id')
            ->where('ventas.saldo_inicial', false)
            ->where('ventas.estado', '!=', 'A')
            ->where('ventas.tipo_comprobante', '!=', 'nota_credito')
            ->whereBetween('ventas.fecha', [$this->desde, $this->hasta])
            ->selectRaw('pago_ventas.forma_pago, SUM(pago_ventas.monto) as monto')
            ->groupBy('pago_ventas.forma_pago')
            ->get();
    }

    /**
     * Cobranza posterior: recibos aplicados a las ventas del período, por forma de pago.
     *
     * El corte es **al día de hoy**, no al cierre del período: un recibo de cualquier fecha
     * cuenta mientras cancele una venta del rango (decisión del cliente — la pregunta es
     * "de lo que vendí en estos días, ¿cuánto entró?"). Para la foto al cierre habría que
     * agregar `->whereDate('recibos.fecha', '<=', $this->hasta)`.
     *
     * Los recibos anulados (`estado = 'A'`) conservan sus `recibo_detalles`, así que hay que
     * excluirlos explícitamente o el cobrado queda inflado.
     */
    private function cobrosPorRecibo(): Collection
    {
        return ReciboDetalle::query()
            ->join('recibos', 'recibos.id', '=', 'recibo_detalles.recibo_id')
            ->join('ventas', 'ventas.id', '=', 'recibo_detalles.venta_id')
            ->whereNull('recibos.deleted_at')
            ->where('recibos.estado', '!=', 'A')
            ->where('ventas.saldo_inicial', false)
            ->where('ventas.estado', '!=', 'A')
            ->where('ventas.tipo_comprobante', '!=', 'nota_credito')
            ->whereBetween('ventas.fecha', [$this->desde, $this->hasta])
            ->selectRaw('recibos.forma_pago, SUM(recibo_detalles.monto_aplicado) as monto')
            ->groupBy('recibos.forma_pago')
            ->get();
    }

    /**
     * Vuelca una o más colecciones {forma_pago, monto} en los buckets de presentación,
     * respetando el orden de `BUCKETS` (que es el orden del anillo del donut). Las formas
     * no mapeadas conservan su descripción y se ubican al final.
     *
     * @param  array<string,string>  $labelFormas  codigo => descripcion de `formas_pago`
     * @return array<int,array{label:string,monto:float,color:string,patron:bool}>
     */
    private function agruparPorMedio(array $colecciones, array $labelFormas): array
    {
        $acum = [];
        foreach ($colecciones as $filas) {
            foreach ($filas as $fila) {
                $codigo = (string) $fila->forma_pago;
                $bucket = self::MAPA_BUCKETS[$codigo] ?? 'otro:' . $codigo;
                $acum[$bucket] = ($acum[$bucket] ?? 0) + (float) $fila->monto;
            }
        }

        $salida = [];
        foreach (self::BUCKETS as $key => $meta) {
            if (($acum[$key] ?? 0) > 0.01) {
                $salida[] = ['label' => $meta['label'], 'monto' => round($acum[$key], 2),
                             'color' => $meta['color'], 'patron' => false];
            }
            unset($acum[$key]);
        }

        // Formas sin bucket propio (p. ej. "Transferencia Mayorista"): etiqueta de la tabla.
        foreach ($acum as $key => $monto) {
            if ($monto <= 0.01) {
                continue;
            }
            $codigo = substr($key, 5);   // recorta el prefijo "otro:"
            $salida[] = [
                'label'  => $labelFormas[$codigo] ?? ucfirst(str_replace('_', ' ', $codigo)),
                'monto'  => round($monto, 2),
                'color'  => self::COLOR_OTROS,
                'patron' => false,
            ];
        }

        return $salida;
    }

    public function render()
    {
        $fmt = $this->formato();

        /* ----------------------------- VENTAS ----------------------------- */
        $totalVentas = (float) $this->ventasBase()->sum('total');
        $cantVentas  = (int) $this->ventasBase()->count();
        $ticketProm  = $cantVentas > 0 ? $totalVentas / $cantVentas : 0;

        $serieVentas = $this->ventasBase()
            ->selectRaw("DATE_FORMAT(fecha, '{$fmt}') as periodo, SUM(total) as total, COUNT(*) as cant")
            ->groupBy('periodo')->orderBy('periodo')->get();

        $ventasPorProducto = DetVenta::query()
            ->join('ventas', 'ventas.id', '=', 'det_ventas.venta_id')
            ->where('ventas.saldo_inicial', false)
            ->where('ventas.estado', '!=', 'A')
            ->where('ventas.tipo_comprobante', '!=', 'nota_credito')
            ->whereBetween('ventas.fecha', [$this->desde, $this->hasta])
            ->selectRaw('det_ventas.descripcion, SUM(det_ventas.cantidad) as cantidad, SUM(det_ventas.subtotal) as neto, SUM(det_ventas.subtotal + det_ventas.subtotal_iva) as total_civa')
            ->groupBy('det_ventas.descripcion')
            ->orderByDesc('neto')
            ->get();

        /* ------------------- FORMAS DE PAGO / COBRANZA -------------------
         * Cuenta corriente no es una forma de pago, es un estado: la venta se pacta a
         * crédito y después se cobra en efectivo o transferencia. Por eso hay dos lecturas:
         *
         *   'real'    → cada peso en el medio por el que efectivamente entró; el residuo
         *               (lo que sigue impago) es "Pendiente de cobro".
         *   'emision' → la foto del comprobante; el residuo es "Vendido a crédito".
         *
         * En ambos modos el residuo cierra contra `totalVentas`, así el donut suma 100 %.
         */
        $labelFormas   = FormaPago::pluck('descripcion', 'codigo')->toArray();
        $cobrosVenta   = $this->cobrosEnLaVenta();
        $cobrosRecibo  = $this->cobrosPorRecibo();

        $totalEnLaVenta = round((float) $cobrosVenta->sum('monto'), 2);
        $totalPorRecibo = round((float) $cobrosRecibo->sum('monto'), 2);
        $totalCobrado   = $totalEnLaVenta + $totalPorRecibo;

        // Residuo = lo que sigue adeudado. Coincide con SUM(ventas.saldo) del período.
        $pendiente      = round(max($totalVentas - $totalCobrado, 0), 2);
        // Residuo de la lectura histórica: lo pactado a crédito al emitir.
        $vendidoCredito = round(max($totalVentas - $totalEnLaVenta, 0), 2);

        if ($this->modoFormasPago === 'emision') {
            $formasPago = $this->agruparPorMedio([$cobrosVenta], $labelFormas);
            if ($vendidoCredito > 0.01) {
                $formasPago[] = ['label' => 'Vendido a crédito', 'monto' => $vendidoCredito,
                                 'color' => self::COLOR_RESIDUO, 'patron' => true];
            }
        } else {
            $formasPago = $this->agruparPorMedio([$cobrosVenta, $cobrosRecibo], $labelFormas);
            if ($pendiente > 0.01) {
                $formasPago[] = ['label' => 'Pendiente de cobro', 'monto' => $pendiente,
                                 'color' => self::COLOR_RESIDUO, 'patron' => true];
            }
        }

        // Barra de estado de cobranza — independiente del modo elegido.
        $cobranza = [
            ['label' => 'Cobrado en la venta', 'monto' => $totalEnLaVenta, 'color' => '#2a78d6', 'patron' => false],
            ['label' => 'Cobrado después',     'monto' => $totalPorRecibo, 'color' => '#eb6834', 'patron' => false],
            ['label' => 'Pendiente',           'monto' => $pendiente,      'color' => self::COLOR_RESIDUO, 'patron' => true],
        ];

        $cobranzaResumen = [
            'total'            => $totalVentas,
            'en_la_venta'      => $totalEnLaVenta,
            'por_recibo'       => $totalPorRecibo,
            'cobrado'          => $totalCobrado,
            'pendiente'        => $pendiente,
            'vendido_credito'  => $vendidoCredito,
            'pct_cobrado'      => $totalVentas > 0 ? round($totalCobrado / $totalVentas * 100, 1) : 0,
            'pct_pendiente'    => $totalVentas > 0 ? round($pendiente / $totalVentas * 100, 1) : 0,
            'pct_credito'      => $totalVentas > 0 ? round($vendidoCredito / $totalVentas * 100, 1) : 0,
            // Cuánto de lo fiado ya se recuperó.
            'pct_recupero_cc'  => $vendidoCredito > 0 ? round($totalPorRecibo / $vendidoCredito * 100, 1) : 0,
        ];

        /* ----------------------------- COMPRAS ---------------------------- */
        $comprasBase = fn () => Compra::query()
            ->comerciales()
            ->where('compras.estado', '!=', 'A')->where('compras.procesada', true)
            ->whereBetween('compras.fecha', [$this->desde, $this->hasta]);

        $totalCompras = (float) $comprasBase()->sum('total');
        $serieCompras = $comprasBase()
            ->selectRaw("DATE_FORMAT(fecha, '{$fmt}') as periodo, SUM(total) as total")
            ->groupBy('periodo')->orderBy('periodo')->get();
        $comprasPorProveedor = $comprasBase()
            ->join('proveedores', 'proveedores.id', '=', 'compras.proveedor_id')
            ->selectRaw('proveedores.razon_social, SUM(compras.total) as total')
            ->groupBy('proveedores.razon_social')->orderByDesc('total')->limit(15)->get();

        /* ----------------------------- GASTOS ----------------------------- */
        $gastosBase = fn () => Gasto::query()
            ->where('gastos.estado', '!=', 'N')
            ->whereBetween('gastos.fecha', [$this->desde, $this->hasta]);

        $totalGastos = (float) $gastosBase()->sum('monto');
        $gastosPorCategoria = $gastosBase()
            ->leftJoin('categorias_gasto', 'categorias_gasto.id', '=', 'gastos.categoria_gasto_id')
            ->selectRaw("COALESCE(categorias_gasto.nombre, 'Sin categoría') as categoria, SUM(gastos.monto) as total")
            ->groupBy('categoria')->orderByDesc('total')->get();

        /* ----------------------------- SUELDOS ---------------------------- */
        // CentroGoma no liquida sueldos en el sistema: lo único que sale de caja por personal
        // son los anticipos (concepto `anticipo`). Rechazar o eliminar un anticipo borra su
        // movimiento del libro, así que no aparece.
        $sueldosBase = fn () => MovimientoCaja::query()
            ->where('movimiento_cajas.tipo', 'egreso')
            ->where('movimiento_cajas.concepto', 'anticipo')
            ->whereBetween('movimiento_cajas.fecha', [$this->desde, $this->hasta]);

        $totalSueldos = (float) $sueldosBase()->sum('monto');

        $sueldosPorEmpleado = $sueldosBase()
            ->join('anticipo_empleados', function ($j) {
                $j->on('anticipo_empleados.id', '=', 'movimiento_cajas.referencia_id')
                  ->where('movimiento_cajas.referencia_type', '=', AnticipoEmpleado::class);
            })
            ->join('empleados', 'empleados.id', '=', 'anticipo_empleados.empleado_id')
            ->selectRaw("CONCAT(empleados.nombre, ' ', COALESCE(empleados.apellido, '')) as empleado, SUM(movimiento_cajas.monto) as total")
            ->groupBy('empleado')->orderByDesc('total')->get();

        /* ----------------------------- RESUMEN ---------------------------- */
        // Sin stock valorizado: el negocio no lleva stock.
        $egresos = $totalCompras + $totalGastos + $totalSueldos;
        $pct = fn (float $parte, float $base) => $base > 0 ? round($parte / $base * 100, 1) : 0;

        $resumen = [
            'ventas'             => $totalVentas,
            'compras'            => $totalCompras,
            'gastos'             => $totalGastos,
            'sueldos'            => $totalSueldos,
            'egresos'            => $egresos,
            'resultado'          => $totalVentas - $egresos,
            'pct_compras'        => $pct($totalCompras, $egresos),
            'pct_gastos'         => $pct($totalGastos, $egresos),
            'pct_sueldos'        => $pct($totalSueldos, $egresos),
        ];

        return view('livewire.estadisticas.index', compact(
            'totalVentas', 'cantVentas', 'ticketProm', 'serieVentas', 'ventasPorProducto',
            'formasPago', 'cobranza', 'cobranzaResumen',
            'totalCompras', 'serieCompras', 'comprasPorProveedor',
            'totalGastos', 'gastosPorCategoria',
            'totalSueldos', 'sueldosPorEmpleado', 'resumen',
        ));
    }
}
