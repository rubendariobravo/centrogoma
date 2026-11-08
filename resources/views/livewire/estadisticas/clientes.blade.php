<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Clientes top</h1>
            <p class="text-sm text-gray-500 mt-0.5">Ranking de clientes por monto de compra · artículo más comprado</p>
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
                <label class="block text-xs font-medium text-gray-600 mb-1">Mostrar</label>
                <select wire:model.live="topN"
                        class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                    <option value="10">Top 10</option>
                    <option value="20">Top 20</option>
                    <option value="50">Top 50</option>
                    <option value="0">Todos</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Cliente</label>
                <div class="w-64">
                    <x-buscador-cliente vacio="Todos los clientes" />
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Rubro</label>
                <select wire:model.live="rubroId"
                        class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                    <option value="">Todos</option>
                    @foreach($rubros as $r)
                        <option value="{{ $r->id }}">{{ $r->descripcion }}</option>
                    @endforeach
                </select>
            </div>
            <div wire:loading.delay class="text-xs text-slate-400 pb-2">Actualizando…</div>
        </div>
        @if($rubroId)
            <p class="text-xs text-amber-600 mt-2">El monto mostrado corresponde <strong>solo</strong> al rubro seleccionado.</p>
        @endif
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-4">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <p class="text-xs text-gray-500">Facturación del período <span class="text-gray-400">(c/IVA)</span></p>
            <p class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($totalMonto, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <p class="text-xs text-gray-500">Clientes con compras</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($clientesCount, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <p class="text-xs text-gray-500">Comprobantes</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($comprobantes, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <p class="text-xs text-gray-500">Ticket promedio</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($ticketProm, 2, ',', '.') }}</p>
        </div>
    </div>

    {{-- Gráficos --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-xl shadow-sm p-5 lg:col-span-2">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Top clientes por monto <span class="text-xs font-normal text-gray-400">(c/IVA)</span></h3>
            <div id="clitop-chart-bar" wire:ignore></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Concentración de cartera</h3>
            <div id="clitop-chart-donut" wire:ignore></div>
        </div>
    </div>

    {{-- Ranking --}}
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Ranking de clientes</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] text-xs text-gray-500 uppercase">
                        <th class="py-2 text-left w-10">#</th>
                        <th class="py-2 text-left">Cliente</th>
                        <th class="py-2 text-left">Artículo más comprado</th>
                        <th class="py-2 text-right">Comprob.</th>
                        <th class="py-2 text-right">Monto total</th>
                        <th class="py-2 text-right">% del total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ranking as $i => $row)
                        @php
                            $art = $topArticulo[$row->cliente_id ?? 0] ?? null;
                            $pct = $totalMonto > 0 ? ($row->monto / $totalMonto) * 100 : 0;
                        @endphp
                        <tr class="border-b border-gray-50 hover:bg-gray-50">
                            <td class="py-2 text-gray-400">{{ $i + 1 }}</td>
                            <td class="py-2 font-medium text-gray-900">{{ $row->cliente }}</td>
                            <td class="py-2 text-gray-600">
                                @if($art)
                                    {{ $art['descripcion'] }}
                                    <span class="text-xs text-gray-400">
                                        ({{ number_format($art['cantidad'], 2, ',', '.') }} · ${{ number_format($art['monto'], 2, ',', '.') }})
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="py-2 text-right text-gray-600">{{ number_format($row->comprobantes, 0, ',', '.') }}</td>
                            <td class="py-2 text-right font-semibold text-gray-900">${{ number_format($row->monto, 2, ',', '.') }}</td>
                            <td class="py-2 text-right text-gray-500">{{ number_format($pct, 1, ',', '.') }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-gray-400">No hay ventas en el período seleccionado.</td></tr>
                    @endforelse
                </tbody>
                @if($ranking->isNotEmpty())
                    <tfoot>
                        <tr class="border-t-2 border-[#DDE1E7] font-semibold text-gray-900">
                            <td colspan="3" class="py-2">Total ranking</td>
                            <td class="py-2 text-right">{{ number_format($ranking->sum('comprobantes'), 0, ',', '.') }}</td>
                            <td class="py-2 text-right">${{ number_format($ranking->sum('monto'), 2, ',', '.') }}</td>
                            <td class="py-2 text-right">{{ $totalMonto > 0 ? number_format($ranking->sum('monto') / $totalMonto * 100, 1, ',', '.') : '0,0' }}%</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Datos para los gráficos --}}
    <script type="application/json" id="clitop-data">{!! json_encode($chartData) !!}</script>

    {{-- ApexCharts (mismo patrón resiliente que el panel de estadísticas) --}}
    <script>
        window.__cliTopCharts = window.__cliTopCharts || {};

        function cliTopEnsureLib(cb) {
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

        const CLITOP_PALETTE = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#64748b'];

        function cliTopMoney(v) {
            return '$' + (parseFloat(v) || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        function cliTopDestroy(key) {
            if (window.__cliTopCharts[key]) { try { window.__cliTopCharts[key].destroy(); } catch (e) {} delete window.__cliTopCharts[key]; }
        }
        function cliTopCreate(key, elId, options) {
            const el = document.getElementById(elId);
            if (!el || typeof ApexCharts === 'undefined') return;
            cliTopDestroy(key);
            el.innerHTML = '';
            const chart = new ApexCharts(el, options);
            window.__cliTopCharts[key] = chart;
            chart.render();
        }

        function cliTopRenderAll() {
            const node = document.getElementById('clitop-data');
            if (!node) return;
            let d;
            try { d = JSON.parse(node.textContent); } catch (e) { return; }

            // Barras horizontales — top clientes por monto.
            cliTopCreate('bar', 'clitop-chart-bar', {
                chart: { type: 'bar', height: Math.max(320, d.bar.labels.length * 28), toolbar: { show: false }, fontFamily: 'inherit' },
                series: [{ name: 'Monto', data: d.bar.montos }],
                xaxis: { categories: d.bar.labels, labels: { formatter: v => cliTopMoney(v) } },
                colors: [CLITOP_PALETTE[0]],
                plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '65%' } },
                dataLabels: { enabled: false },
                tooltip: { y: { formatter: cliTopMoney } },
                grid: { borderColor: '#eef0f3' },
            });

            // Donut — concentración de cartera (top 5 + resto).
            if (d.donut.labels.length) {
                cliTopCreate('donut', 'clitop-chart-donut', {
                    chart: { type: 'donut', height: 320, fontFamily: 'inherit' },
                    series: d.donut.montos, labels: d.donut.labels, colors: CLITOP_PALETTE,
                    legend: { position: 'bottom', fontSize: '11px' },
                    dataLabels: { enabled: true, formatter: v => v.toFixed(1) + '%' },
                    tooltip: { y: { formatter: cliTopMoney } },
                });
            } else {
                cliTopDestroy('donut');
            }
        }

        function cliTopBoot() {
            if (!document.getElementById('clitop-data')) return;
            cliTopEnsureLib(cliTopRenderAll);
        }

        if (!window.__cliTopBound) {
            window.__cliTopBound = true;
            document.addEventListener('livewire:navigated', () => cliTopBoot());

            const cliTopBindHook = () => {
                if (!window.Livewire || window.__cliTopHookBound) return;
                window.__cliTopHookBound = true;
                window.Livewire.hook('morphed', () => cliTopEnsureLib(cliTopRenderAll));
            };
            document.addEventListener('livewire:init', cliTopBindHook);
            cliTopBindHook();
        }

        cliTopBoot();
    </script>
</div>
