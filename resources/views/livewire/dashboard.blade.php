<div>
    {{-- Encabezado --}}
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ now()->isoFormat('dddd D [de] MMMM, YYYY') }}</p>
    </div>

    {{-- Alerta caja cerrada --}}
    @if(!$cajaAbierta)
    <div class="mb-5 flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <p class="text-sm text-amber-800 font-medium">No hay caja abierta.
            <a href="{{ route('cajas') }}" wire:navigate class="underline hover:no-underline">Abrir caja</a>
            para habilitar ventas.
        </p>
    </div>
    @endif

    {{-- Tarjetas de estadísticas --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">

        {{-- Ventas hoy --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Ventas hoy</p>
                    <p class="mt-1.5 text-2xl font-bold text-gray-900">{{ $ventasHoy }}</p>
                    <p class="mt-0.5 text-sm text-gray-500">{{ number_format($totalHoy, 2, ',', '.') }} ARS</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-[#DDE1E7] flex items-center gap-1 text-xs text-gray-500">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Esta semana: <span class="font-medium text-gray-700">{{ $ventasSemana }} ventas</span>
            </div>
        </div>

        {{-- Total semana --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total semana</p>
                    <p class="mt-1.5 text-2xl font-bold text-gray-900">$ {{ number_format($totalSemana, 0, ',', '.') }}</p>
                    <p class="mt-0.5 text-sm text-gray-500">{{ $ventasSemana }} comprobantes</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-[#DDE1E7] flex items-center gap-1 text-xs text-gray-500">
                @if($cajaAbierta)
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Caja abierta desde {{ $cajaAbierta->fecha_apertura?->format('d/m') }}
                @else
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                    Sin caja activa
                @endif
            </div>
        </div>

        {{-- Clientes activos --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Clientes</p>
                    <p class="mt-1.5 text-2xl font-bold text-gray-900">{{ $clientesActivos }}</p>
                    <p class="mt-0.5 text-sm text-gray-500">activos en el sistema</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-violet-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-[#DDE1E7] flex items-center gap-1 text-xs text-gray-500">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Artículos activos: <span class="font-medium text-gray-700">{{ $articulosActivos }}</span>
            </div>
        </div>

        {{-- Stock crítico --}}
        <div class="bg-white rounded-xl shadow-sm p-5 {{ $stockCritico > 0 ? 'border border-red-200 bg-red-50' : '' }}">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium {{ $stockCritico > 0 ? 'text-red-500' : 'text-gray-500' }} uppercase tracking-wide">Stock crítico</p>
                    <p class="mt-1.5 text-2xl font-bold {{ $stockCritico > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ $stockCritico }}</p>
                    <p class="mt-0.5 text-sm {{ $stockCritico > 0 ? 'text-red-500' : 'text-gray-500' }}">artículos bajo mínimo</p>
                </div>
                <div class="w-10 h-10 rounded-lg {{ $stockCritico > 0 ? 'bg-red-100' : 'bg-gray-100' }} flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 {{ $stockCritico > 0 ? 'text-red-600' : 'text-gray-500' }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t {{ $stockCritico > 0 ? 'border-red-200' : 'border-[#DDE1E7]' }}">
                <a href="{{ route('articulos') }}" wire:navigate
                   class="text-xs {{ $stockCritico > 0 ? 'text-red-600 font-medium' : 'text-gray-500' }} hover:underline">
                    Ver artículos →
                </a>
            </div>
        </div>

    </div>

    {{-- Fila inferior: últimas ventas + alertas stock --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

        {{-- Últimas ventas --}}
        <div class="xl:col-span-2 bg-white rounded-xl border border-[#DDE1E7]">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[#DDE1E7]">
                <h2 class="text-sm font-semibold text-gray-900">Últimas ventas</h2>
                <a href="{{ route('ventas') }}" wire:navigate class="text-xs text-blue-600 hover:underline">Ver todas →</a>
            </div>
            @if($ultimasVentas->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center text-gray-400">
                    <svg class="w-10 h-10 mb-3 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm">No hay ventas aún</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500 border-b border-[#DDE1E7]">
                                <th class="px-5 py-2.5 text-left font-medium">#</th>
                                <th class="px-3 py-2.5 text-left font-medium">Cliente</th>
                                <th class="px-3 py-2.5 text-left font-medium">Fecha</th>
                                <th class="px-3 py-2.5 text-right font-medium">Total</th>
                                <th class="px-5 py-2.5 text-center font-medium">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E9ECF0]">
                            @foreach($ultimasVentas as $venta)
                            <tr class="hover:bg-[#E9ECF0] transition-colors">
                                <td class="px-5 py-3 text-gray-500 font-mono text-xs">{{ str_pad($venta->nro_venta, 6, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-3 py-3 font-medium text-gray-900 truncate max-w-[180px]">{{ $venta->cliente?->razon_social ?? '—' }}</td>
                                <td class="px-3 py-3 text-gray-500 whitespace-nowrap">{{ $venta->fecha?->format('d/m/Y') }}</td>
                                <td class="px-3 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">$ {{ number_format($venta->total, 2, ',', '.') }}</td>
                                <td class="px-5 py-3 text-center">
                                    @if($venta->estaFacturada())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Facturada</span>
                                    @elseif($venta->estado === 'C')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Cobrada</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Pendiente</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Alertas stock --}}
        <div class="bg-white rounded-xl border border-[#DDE1E7]">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[#DDE1E7]">
                <h2 class="text-sm font-semibold text-gray-900">Stock crítico</h2>
                @if($stockCritico > 6)
                    <span class="text-xs text-red-600 font-medium">+{{ $stockCritico - 6 }} más</span>
                @endif
            </div>
            @if($alertasStock->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center text-gray-400">
                    <svg class="w-10 h-10 mb-3 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm">Stock OK</p>
                </div>
            @else
                <ul class="divide-y divide-[#E9ECF0]">
                    @foreach($alertasStock as $art)
                    <li class="flex items-center justify-between px-5 py-3 hover:bg-[#E9ECF0]">
                        <div class="min-w-0 mr-3">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $art->descripcion }}</p>
                            <p class="text-xs text-gray-400 font-mono">{{ $art->codigo }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm font-bold text-red-600">{{ number_format($art->stock, 2, ',', '.') }}</p>
                            <p class="text-xs text-gray-400">mín: {{ number_format($art->stock_minimo, 2, ',', '.') }}</p>
                        </div>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>

    </div>
</div>
