<div
    x-data
    x-on:keydown.window="
        if (event.key === 'F2') {
            event.preventDefault();
            document.getElementById('btn-nueva-venta').click();
        }
    "
>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Ventas</h1>
            <p class="text-sm text-gray-500 mt-0.5">Registro de ventas y comprobantes</p>
        </div>
        <a id="btn-nueva-venta" href="{{ route('ventas.create') }}" wire:navigate
           class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors"
           title="Nueva venta (F2)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva venta <span class="opacity-60 text-xs font-normal">F2</span>
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg px-4 py-3">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <div class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[180px]">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" type="text"
                           placeholder="Cliente o Nº de venta..."
                           class="w-full pl-9 pr-4 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-500">Desde</label>
                <input wire:model.live="filterDesde" type="date"
                       class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-500">Hasta</label>
                <input wire:model.live="filterHasta" type="date"
                       class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
            </div>
            <select wire:model.live="filterEstado"
                    class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                <option value="">Todos los estados</option>
                <option value="P">Pendiente</option>
                <option value="C">Cobrada</option>
                <option value="A">Anulada</option>
            </select>
        </div>
    </div>

    {{-- Resumen período --}}
    <div class="bg-white rounded-xl border border-[#DDE1E7] px-4 py-3 mb-4 flex items-center justify-between">
        <span class="text-sm text-gray-500">Total del período</span>
        <span class="text-lg font-bold text-gray-900">${{ number_format($totalPeriodo, 2, ',', '.') }}</span>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Nº</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Cliente</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Tipo</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Saldo</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($ventas as $venta)
                    <tr class="hover:bg-[#E9ECF0] transition-colors group">
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">
                            {{ $venta->nro_venta ?? '—' }}
                            @if($venta->letra_comprobante)
                                <span class="ml-1 text-gray-400">{{ $venta->letra_comprobante }}</span>
                            @endif
                            @if($venta->saldo_inicial)
                                <span class="ml-1 inline-flex whitespace-nowrap px-1.5 py-0.5 rounded text-[10px] font-sans font-medium bg-slate-100 text-slate-600">Saldo inicial</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $venta->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            @if($venta->cliente)
                                <div class="font-medium text-gray-900">{{ $venta->cliente->razon_social }}</div>
                            @elseif($venta->cliente_vtadiaria)
                                <div class="text-gray-600">{{ $venta->cliente_vtadiaria }}</div>
                                @if($venta->dni_vtadiaria)
                                    <div class="text-xs text-gray-400">DNI {{ $venta->dni_vtadiaria }}</div>
                                @endif
                            @else
                                <span class="text-gray-400">Consumidor final</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($venta->tipo_comprobante === 'nota_credito')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">NC</span>
                            @elseif($venta->tipo_comprobante === 'nota_debito')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">ND</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">FAC</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">
                            ${{ number_format($venta->total, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @php $saldo = (float) $venta->saldo; @endphp
                            <span class="{{ $saldo > 0.01 ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                                ${{ number_format($saldo, 2, ',', '.') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($venta->estado === 'C')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Cobrada</span>
                            @elseif($venta->estado === 'A')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">Anulada</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                {{-- Ver --}}
                                <a href="{{ route('ventas.show', $venta->id) }}" wire:navigate
                                   class="p-1.5 text-gray-400 hover:text-slate-900 hover:bg-gray-100 rounded-md transition-colors" title="Ver">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                {{-- PDF A4 --}}
                                <a href="{{ route('ventas.pdf', $venta->id) }}" target="_blank"
                                   class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors"
                                   title="{{ $venta->cae ? 'Factura PDF (A4)' : 'Recibo PDF (A4)' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </a>
                                {{-- Ticket 72mm --}}
                                <a href="{{ route('ventas.ticket', $venta->id) }}" target="_blank"
                                   class="p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-md transition-colors"
                                   title="{{ $venta->cae ? 'Factura Ticket (72mm)' : 'Recibo Ticket (72mm)' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                </a>
                                {{-- Imprimir directo --}}
                                <a href="{{ route('ventas.imprimir', $venta->id) }}" target="_blank"
                                   class="p-1.5 text-gray-400 hover:text-slate-700 hover:bg-slate-100 rounded-md transition-colors"
                                   title="Imprimir (formato configurado)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/>
                                    </svg>
                                </a>
                                @if($venta->estado !== 'A' && auth()->user()->hasRole(['super-admin', 'admin']))
                                <button type="button" wire:click="confirmarAnular({{ $venta->id }})"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Anular">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-16 text-center text-gray-400">
                            <p class="text-sm">No se encontraron ventas en el período</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ventas->hasPages())
        <div class="px-4 py-3 border-t border-[#DDE1E7]">
            {{ $ventas->links() }}
        </div>
        @endif
    </div>

    {{-- Modal anular venta --}}
    @if($anularId)
    <x-modal-anular titulo="Anular venta" descripcion="Se devuelve el stock, se revierte la caja y la cuenta corriente del cliente, y se anulan los cheques recibidos en la venta.">
        @if($anularConCae)
        <div class="mt-4 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg px-3 py-2">
            La venta tiene CAE: requiere nota de crédito electrónica, que todavía no está implementada.
        </div>
        @endif
        @if(count($anularCobros))
        <div class="mt-4 bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-lg px-3 py-2">
            <p class="font-medium">La venta tiene cobros aplicados (${{ $anularCobrado }}). Anule primero estos recibos:</p>
            <ul class="mt-1 list-disc list-inside">
                @foreach($anularCobros as $c)
                    <li>Recibo #{{ $c['nro'] }} del {{ $c['fecha'] }} — ${{ $c['monto'] }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </x-modal-anular>
    @endif
</div>
