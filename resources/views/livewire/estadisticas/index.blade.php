<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Estadísticas</h1>
            <p class="text-sm text-gray-500 mt-0.5">Resumen del comercio · ventas, compras, gastos y resultado</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
                <input wire:model.live="desde" type="date"
                       class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                <input wire:model.live="hasta" type="date"
                       class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Agrupar por</label>
                <select wire:model.live="agrupacion"
                        class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                    <option value="dia">Día</option>
                    <option value="mes">Mes</option>
                    <option value="anio">Año</option>
                </select>
            </div>
            <div wire:loading.delay class="text-xs text-slate-400 pb-2">Actualizando…</div>
        </div>
    </div>

    {{-- Tabs --}}
    @php
        $tabs = ['ventas' => 'Ventas', 'compras' => 'Compras', 'gastos' => 'Gastos', 'sueldos' => 'Anticipos', 'resumen' => 'Ingresos vs Egresos'];
    @endphp
    <div class="flex flex-wrap gap-1 border-b border-[#DDE1E7] mb-4">
        @foreach($tabs as $key => $label)
        <button type="button" wire:click="setTab('{{ $key }}')"
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors
                       {{ $tab === $key ? 'border-slate-900 text-slate-900' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- ============================ VENTAS ============================ --}}
    @if($tab === 'ventas')
    <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-5">
                <p class="text-xs text-gray-500">Total ventas</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($totalVentas, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <p class="text-xs text-gray-500">Comprobantes</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($cantVentas, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <p class="text-xs text-gray-500">Ticket promedio</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($ticketProm, 2, ',', '.') }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Ventas por período <span class="text-xs font-normal text-gray-400">(c/IVA)</span></h3>
            <div id="estad-chart-ventas" wire:ignore></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Ventas por producto</h3>
                <div class="overflow-y-auto max-h-96">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-white">
                            <tr class="border-b border-[#DDE1E7] text-xs text-gray-500 uppercase">
                                <th class="py-2 text-left">Producto</th>
                                <th class="py-2 text-right">Cantidad</th>
                                <th class="py-2 text-right">Neto</th>
                                <th class="py-2 text-right">Total c/IVA</th>
                                <th class="py-2 text-right">Prom. c/IVA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E9ECF0]">
                            @forelse($ventasPorProducto as $p)
                            <tr>
                                <td class="py-1.5 text-gray-800">{{ $p->descripcion ?: '—' }}</td>
                                <td class="py-1.5 text-right text-gray-700">{{ number_format($p->cantidad, 2, ',', '.') }}</td>
                                <td class="py-1.5 text-right text-gray-700">${{ number_format($p->neto, 2, ',', '.') }}</td>
                                <td class="py-1.5 text-right font-medium text-gray-900">${{ number_format($p->total_civa, 2, ',', '.') }}</td>
                                <td class="py-1.5 text-right font-medium text-indigo-700">${{ number_format($p->cantidad > 0 ? $p->total_civa / $p->cantidad : 0, 2, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="py-8 text-center text-gray-400">Sin ventas en el período</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Formas de pago</h3>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $modoFormasPago === 'emision'
                                ? 'Cómo se pactó el pago al emitir el comprobante'
                                : 'Cómo entró realmente la plata de estas ventas · al día de hoy' }}
                        </p>
                    </div>
                    <div class="inline-flex rounded-lg border border-[#DDE1E7] bg-gray-50 p-0.5 gap-0.5 shrink-0">
                        @foreach(['real' => 'Cobranza real', 'emision' => 'Al emitir'] as $modo => $label)
                        <button type="button" wire:click="setModoFormasPago('{{ $modo }}')"
                                class="px-3 py-1.5 text-xs rounded-md transition-colors
                                       {{ $modoFormasPago === $modo
                                            ? 'bg-white text-slate-900 font-semibold shadow-sm'
                                            : 'text-gray-500 font-medium hover:text-gray-800' }}">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </div>
                <div id="estad-chart-formaspago" wire:ignore></div>
                @if($modoFormasPago === 'real' && $cobranzaResumen['pendiente'] > 0.01)
                <p class="text-xs text-gray-400 mt-2 border-t border-[#E9ECF0] pt-2">
                    «Pendiente de cobro» no es un medio de pago: es el saldo que todavía se adeuda
                    de las ventas del período.
                </p>
                @endif
            </div>
        </div>

        {{-- Estado de cobranza --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Estado de cobranza</h3>
                    <p class="text-xs text-gray-400 mt-0.5">De lo facturado en el período, cuánto entró y cuándo · al día de hoy</p>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
                <div class="rounded-lg border border-[#E9ECF0] bg-gray-50/60 p-3">
                    <p class="text-xs text-gray-500">Cobrado</p>
                    <p class="text-lg font-bold text-emerald-600 mt-0.5">${{ number_format($cobranzaResumen['cobrado'], 2, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">{{ number_format($cobranzaResumen['pct_cobrado'], 1, ',', '.') }}% del período</p>
                </div>
                <div class="rounded-lg border border-[#E9ECF0] bg-gray-50/60 p-3">
                    <p class="text-xs text-gray-500">Pendiente</p>
                    <p class="text-lg font-bold text-gray-900 mt-0.5">${{ number_format($cobranzaResumen['pendiente'], 2, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">{{ number_format($cobranzaResumen['pct_pendiente'], 1, ',', '.') }}% del período</p>
                </div>
                <div class="rounded-lg border border-[#E9ECF0] bg-gray-50/60 p-3">
                    <p class="text-xs text-gray-500">Vendido a crédito</p>
                    <p class="text-lg font-bold text-gray-900 mt-0.5">${{ number_format($cobranzaResumen['vendido_credito'], 2, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">{{ number_format($cobranzaResumen['pct_credito'], 1, ',', '.') }}% del período</p>
                </div>
                <div class="rounded-lg border border-[#E9ECF0] bg-gray-50/60 p-3">
                    <p class="text-xs text-gray-500">Recupero de la CC</p>
                    <p class="text-lg font-bold text-gray-900 mt-0.5">{{ number_format($cobranzaResumen['pct_recupero_cc'], 1, ',', '.') }}%</p>
                    <p class="text-xs text-gray-400">ya cobrado de cuentas corrientes</p>
                </div>
            </div>

            <div id="estad-chart-cobranza" wire:ignore></div>
        </div>
    </div>
    @endif

    {{-- ============================ COMPRAS ============================ --}}
    @if($tab === 'compras')
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <p class="text-xs text-gray-500">Total compras (procesadas)</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($totalCompras, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Compras por período</h3>
            <div id="estad-chart-compras" wire:ignore></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Top proveedores</h3>
            <div id="estad-chart-proveedores" wire:ignore></div>
        </div>
    </div>
    @endif

    {{-- ============================ GASTOS ============================ --}}
    @if($tab === 'gastos')
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <p class="text-xs text-gray-500">Total gastos</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($totalGastos, 2, ',', '.') }}</p>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Gastos por categoría</h3>
                <div id="estad-chart-gastos-bar" wire:ignore></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Área de gastos</h3>
                <div id="estad-chart-gastos-donut" wire:ignore></div>
            </div>
        </div>
    </div>
    @endif

    {{-- ============================ SUELDOS ============================ --}}
    @if($tab === 'sueldos')
    <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-5">
                <p class="text-xs text-gray-500">Anticipos pagados</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($totalSueldos, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">Lo que salió de caja por adelantos de sueldo.</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Por empleado</h3>
            <div class="overflow-y-auto max-h-96">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-white">
                        <tr class="border-b border-[#DDE1E7] text-xs text-gray-500 uppercase">
                            <th class="py-2 text-left">Empleado</th>
                            <th class="py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E9ECF0]">
                        @forelse($sueldosPorEmpleado as $s)
                        <tr>
                            <td class="py-1.5 text-gray-800">{{ trim($s->empleado) ?: '—' }}</td>
                            <td class="py-1.5 text-right font-medium text-gray-900">${{ number_format($s->total, 2, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="py-8 text-center text-gray-400">Sin anticipos en el período</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ===================== INGRESOS VS EGRESOS ===================== --}}
    @if($tab === 'resumen')
    <div class="space-y-4">
        {{-- Desglose de egresos --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-5 lg:col-span-2">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Composición del comercio</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-emerald-700 font-medium">Ventas (ingresos)</span>
                        <span class="font-bold text-emerald-700">${{ number_format($resumen['ventas'], 2, ',', '.') }}</span>
                    </div>
                    <div class="border-t border-[#E9ECF0] pt-3 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Compras <span class="text-xs text-gray-400">({{ $resumen['pct_compras'] }}% de egresos)</span></span>
                            <span class="font-medium text-gray-900">${{ number_format($resumen['compras'], 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Gastos <span class="text-xs text-gray-400">({{ $resumen['pct_gastos'] }}% de egresos)</span></span>
                            <span class="font-medium text-gray-900">${{ number_format($resumen['gastos'], 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Anticipos de sueldo <span class="text-xs text-gray-400">({{ $resumen['pct_sueldos'] }}% de egresos)</span></span>
                            <span class="font-medium text-gray-900">${{ number_format($resumen['sueldos'], 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t border-[#E9ECF0] pt-2">
                            <span class="text-red-700 font-medium">Total egresos</span>
                            <span class="font-bold text-red-700">${{ number_format($resumen['egresos'], 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Distribución de egresos</h3>
                <div id="estad-chart-egresos-donut" wire:ignore></div>
            </div>
        </div>

        {{-- Resultados --}}
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 {{ $resumen['resultado'] >= 0 ? 'border-emerald-500' : 'border-red-500' }}">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Resultado</p>
            <p class="text-xs text-gray-400 mb-2">Ventas − (Compras + Gastos + Anticipos)</p>
            <p class="text-2xl font-bold {{ $resumen['resultado'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                ${{ number_format($resumen['resultado'], 2, ',', '.') }}
            </p>
        </div>

        {{-- Ingresos vs Egresos --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Ingresos vs Egresos</h3>
            <div id="estad-chart-ingresos-egresos" wire:ignore></div>
        </div>
    </div>
    @endif

    {{-- ===================== DATOS PARA CHARTS ===================== --}}
    <script type="application/json" id="estad-data">
        {!! json_encode([
            'tab' => $tab,
            'ventas' => [
                'labels' => $serieVentas->pluck('periodo'),
                'total'  => $serieVentas->pluck('total')->map(fn($v) => (float) $v),
            ],
            'formasPago' => [
                'modo'   => $modoFormasPago,
                'labels' => collect($formasPago)->pluck('label'),
                'montos' => collect($formasPago)->pluck('monto')->map(fn($v) => (float) $v),
                'colors' => collect($formasPago)->pluck('color'),
                'patron' => collect($formasPago)->pluck('patron')->map(fn($v) => (bool) $v),
            ],
            'cobranza' => [
                'labels' => collect($cobranza)->pluck('label'),
                'montos' => collect($cobranza)->pluck('monto')->map(fn($v) => (float) $v),
                'colors' => collect($cobranza)->pluck('color'),
                'patron' => collect($cobranza)->pluck('patron')->map(fn($v) => (bool) $v),
                'total'  => (float) $cobranzaResumen['total'],
            ],
            'compras' => [
                'labels' => $serieCompras->pluck('periodo'),
                'total'  => $serieCompras->pluck('total')->map(fn($v) => (float) $v),
            ],
            'proveedores' => [
                'labels' => $comprasPorProveedor->pluck('razon_social'),
                'total'  => $comprasPorProveedor->pluck('total')->map(fn($v) => (float) $v),
            ],
            'gastos' => [
                'labels' => $gastosPorCategoria->pluck('categoria'),
                'total'  => $gastosPorCategoria->pluck('total')->map(fn($v) => (float) $v),
            ],
            'ingresosEgresos' => [
                'ingresos' => (float) $resumen['ventas'],
                'compras'  => (float) $resumen['compras'],
                'gastos'   => (float) $resumen['gastos'],
                'sueldos'  => (float) $resumen['sueldos'],
            ],
        ]) !!}
    </script>

    <script>
        window.__estadCharts = window.__estadCharts || {};

        // Carga ApexCharts por JS si todavía no está (robusto ante wire:navigate, donde
        // un <script src> en el body puede no re-ejecutarse).
        function estadEnsureLib(cb) {
            if (typeof ApexCharts !== 'undefined') return cb();
            if (window.__estadLibLoading) {
                const t = setInterval(() => {
                    if (typeof ApexCharts !== 'undefined') { clearInterval(t); cb(); }
                }, 100);
                return;
            }
            window.__estadLibLoading = true;
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/apexcharts@3.49.1/dist/apexcharts.min.js';
            s.onload = () => cb();
            document.head.appendChild(s);
        }
        const ESTAD_PALETTE = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#64748b'];

        function estadMoney(v) {
            return '$' + (parseFloat(v) || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        function estadDestroy(key) {
            if (window.__estadCharts[key]) { try { window.__estadCharts[key].destroy(); } catch (e) {} delete window.__estadCharts[key]; }
        }
        function estadCreate(key, elId, options) {
            const el = document.getElementById(elId);
            if (!el || typeof ApexCharts === 'undefined') return;
            estadDestroy(key);
            el.innerHTML = '';
            const chart = new ApexCharts(el, options);
            window.__estadCharts[key] = chart;
            chart.render();
        }

        function estadRenderAll() {
            const node = document.getElementById('estad-data');
            if (!node) return;
            let d;
            try { d = JSON.parse(node.textContent); } catch (e) { return; }

            // Limpiar charts que ya no están en el DOM
            Object.keys(window.__estadCharts).forEach(k => {
                const map = {
                    ventas: 'estad-chart-ventas', formaspago: 'estad-chart-formaspago',
                    cobranza: 'estad-chart-cobranza',
                    compras: 'estad-chart-compras', proveedores: 'estad-chart-proveedores',
                    gastosbar: 'estad-chart-gastos-bar', gastosdonut: 'estad-chart-gastos-donut',
                    egresosdonut: 'estad-chart-egresos-donut', ingresosegresos: 'estad-chart-ingresos-egresos',
                };
                if (map[k] && !document.getElementById(map[k])) estadDestroy(k);
            });

            const baseBar = (cats, name, data, horizontal = false) => ({
                chart: { type: 'bar', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
                series: [{ name, data }],
                xaxis: { categories: cats, labels: { style: { fontSize: '11px' } } },
                colors: [ESTAD_PALETTE[0]],
                plotOptions: { bar: { horizontal, borderRadius: 4, columnWidth: '55%' } },
                dataLabels: { enabled: false },
                yaxis: { labels: { formatter: v => horizontal ? v : estadMoney(v) } },
                tooltip: { y: { formatter: estadMoney } },
                grid: { borderColor: '#eef0f3' },
            });
            const baseDonut = (labels, series) => ({
                chart: { type: 'donut', height: 320, fontFamily: 'inherit' },
                series, labels, colors: ESTAD_PALETTE,
                legend: { position: 'bottom' },
                dataLabels: { enabled: true, formatter: v => v.toFixed(1) + '%' },
                tooltip: { y: { formatter: estadMoney } },
            });

            // Las porciones que NO son un medio de cobro (Pendiente / Vendido a crédito) se
            // distinguen por el tono apagado y por el aclarado del tooltip. Se probó marcarlas
            // además con relleno rayado (fill.pattern 'slantedLines') y hubo que sacarlo: la
            // trama deja pasar el fondo y volvía ilegible la etiqueta de %, en blanco y en
            // tinta oscura por igual. El número es lo primero que se lee — no se negocia.
            const estadNota = (d, i) => (d.patron || [])[i] ? ' · no es un medio de cobro' : '';

            const donutMedios = (d) => ({
                chart: { type: 'donut', height: 320, fontFamily: 'inherit' },
                series: d.montos, labels: d.labels, colors: d.colors,
                legend: { position: 'bottom', fontSize: '12px' },
                stroke: { width: 2, colors: ['#fff'] },
                dataLabels: {
                    enabled: true,
                    formatter: v => v.toFixed(1) + '%',
                    style: { fontSize: '12px', fontWeight: 700, colors: ['#fff'] },
                },
                tooltip: {
                    y: { formatter: (v, o) => estadMoney(v) + estadNota(d, o ? o.seriesIndex : -1) },
                },
            });

            // Barra apilada horizontal de una sola categoría: la composición del total.
            const barraCobranza = (d) => {
                const total = d.total || d.montos.reduce((a, b) => a + b, 0);
                const pct = v => total > 0 ? (v / total * 100) : 0;
                return {
                    chart: { type: 'bar', height: 170, stacked: true, toolbar: { show: false }, fontFamily: 'inherit' },
                    series: d.labels.map((l, i) => ({ name: l, data: [d.montos[i]] })),
                    colors: d.colors,
                    xaxis: { categories: ['Ventas del período'], labels: { formatter: estadMoney, style: { fontSize: '11px' } } },
                    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '46%' } },
                    stroke: { width: 2, colors: ['#fff'] },
                    dataLabels: {
                        enabled: true,
                        formatter: v => pct(v) >= 4 ? pct(v).toFixed(1) + '%' : '',
                        style: { fontSize: '12px', fontWeight: 700, colors: ['#fff'] },
                    },
                    legend: { position: 'bottom', fontSize: '12px' },
                    tooltip: {
                        y: {
                            formatter: (v, o) => estadMoney(v) + ' · ' + pct(v).toFixed(1) + '%'
                                + estadNota(d, o ? o.seriesIndex : -1),
                        },
                    },
                    grid: { borderColor: '#eef0f3' },
                };
            };

            if (d.tab === 'ventas') {
                estadCreate('ventas', 'estad-chart-ventas', baseBar(d.ventas.labels, 'Ventas', d.ventas.total));
                if (d.formasPago.labels.length) {
                    estadCreate('formaspago', 'estad-chart-formaspago', donutMedios(d.formasPago));
                }
                if (d.cobranza.total > 0) {
                    estadCreate('cobranza', 'estad-chart-cobranza', barraCobranza(d.cobranza));
                }
            } else if (d.tab === 'compras') {
                estadCreate('compras', 'estad-chart-compras', baseBar(d.compras.labels, 'Compras', d.compras.total));
                estadCreate('proveedores', 'estad-chart-proveedores', baseBar(d.proveedores.labels, 'Compras', d.proveedores.total, true));
            } else if (d.tab === 'gastos') {
                estadCreate('gastosbar', 'estad-chart-gastos-bar', baseBar(d.gastos.labels, 'Gastos', d.gastos.total, true));
                if (d.gastos.labels.length) {
                    estadCreate('gastosdonut', 'estad-chart-gastos-donut', baseDonut(d.gastos.labels, d.gastos.total));
                }
            } else if (d.tab === 'resumen') {
                const ie = d.ingresosEgresos;
                const egresosLabels = ['Compras', 'Gastos', 'Anticipos'];
                const egresosData = [ie.compras, ie.gastos, ie.sueldos];
                if (egresosData.some(v => v > 0)) {
                    estadCreate('egresosdonut', 'estad-chart-egresos-donut', baseDonut(egresosLabels, egresosData));
                }
                estadCreate('ingresosegresos', 'estad-chart-ingresos-egresos', {
                    chart: { type: 'bar', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
                    series: [{ name: 'Monto', data: [ie.ingresos, ie.compras + ie.gastos + ie.sueldos] }],
                    xaxis: { categories: ['Ingresos', 'Egresos'] },
                    colors: ['#10b981', '#ef4444'],
                    plotOptions: { bar: { borderRadius: 6, columnWidth: '45%', distributed: true } },
                    legend: { show: false },
                    dataLabels: { enabled: true, formatter: estadMoney },
                    yaxis: { labels: { formatter: estadMoney } },
                    tooltip: { y: { formatter: estadMoney } },
                    grid: { borderColor: '#eef0f3' },
                });
            }
        }

        // Arranque resiliente: en navegación SPA el CDN de ApexCharts puede recargarse
        // async y no estar definido todavía cuando corre este script. Esperamos a que
        // exista (y a que el contenedor de datos esté en el DOM) antes de renderizar.
        function estadBoot() {
            if (!document.getElementById('estad-data')) return;
            estadEnsureLib(estadRenderAll);
        }

        // Registrar los listeners globales una sola vez (evita duplicados en cada navegación).
        if (!window.__estadBound) {
            window.__estadBound = true;
            document.addEventListener('livewire:navigated', () => estadBoot());

            // Re-render tras cada actualización de Livewire (cambio de fecha / agrupación / tab).
            // OJO: en Livewire 3 NO existe el evento DOM 'livewire:updated'. Hay que usar el hook
            // 'morphed', que se dispara una vez por componente cuando el morph del DOM ya terminó
            // (el JSON de #estad-data ya está actualizado). Registrarlo apenas Livewire exista.
            const estadBindHook = () => {
                if (!window.Livewire || window.__estadHookBound) return;
                window.__estadHookBound = true;
                window.Livewire.hook('morphed', () => estadEnsureLib(estadRenderAll));
            };
            document.addEventListener('livewire:init', estadBindHook);
            estadBindHook();  // por si Livewire ya estaba inicializado (p. ej. tras wire:navigate)
        }

        // Ejecutar ahora: cubre tanto la carga completa como el swap de wire:navigate.
        estadBoot();
    </script>
</div>
