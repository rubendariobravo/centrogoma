<div
    x-data
    x-on:keydown.window="
        if (event.key === 'F2') {
            event.preventDefault();
            document.getElementById('btn-nuevo-presupuesto').click();
        }
    "
>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Presupuestos</h1>
            <p class="text-sm text-gray-500 mt-0.5">Cotizaciones y presupuestos a clientes</p>
        </div>
        <a id="btn-nuevo-presupuesto" href="{{ route('presupuestos.create') }}" wire:navigate
           class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors"
           title="Nuevo presupuesto (F2)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo <span class="opacity-60 text-xs font-normal">F2</span>
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
    @if(session('error'))
    <div class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
        {{ session('error') }}
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
                           placeholder="Cliente o Nº de presupuesto..."
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
                <option value="A">Activo</option>
                <option value="V">Vencido</option>
                <option value="F">Facturado</option>
                <option value="N">Anulado</option>
            </select>
        </div>
    </div>

    {{-- Resumen período --}}
    <div class="bg-white rounded-xl border border-[#DDE1E7] px-4 py-3 mb-4 flex items-center justify-between">
        <span class="text-sm text-gray-500">Total del período (activos + vencidos)</span>
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Vence</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Cliente</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($presupuestos as $p)
                    <tr class="hover:bg-[#E9ECF0] transition-colors group">
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">
                            #{{ str_pad($p->nro_presupuesto, 6, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $p->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ $p->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $p->cliente?->razon_social ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">
                            ${{ number_format($p->total, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($p->estado === 'A')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Activo</span>
                            @elseif($p->estado === 'V')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Vencido</span>
                            @elseif($p->estado === 'F')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Facturado</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">Anulado</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                {{-- Editar --}}
                                @if(in_array($p->estado, ['A']))
                                <a href="{{ route('presupuestos.edit', $p->id) }}" wire:navigate
                                   class="p-1.5 text-gray-400 hover:text-slate-900 hover:bg-gray-100 rounded-md transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @endif
                                {{-- PDF --}}
                                <a href="{{ route('presupuestos.pdf', $p->id) }}" target="_blank"
                                   class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Ver PDF">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </a>
                                {{-- Anular --}}
                                @if(in_array($p->estado, ['A', 'V']))
                                    @if($confirmingAnular === $p->id)
                                        <button wire:click="anular({{ $p->id }})"
                                                class="px-2 py-1 text-xs font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition-colors">
                                            Confirmar
                                        </button>
                                        <button wire:click="$set('confirmingAnular', null)"
                                                class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors">
                                            No
                                        </button>
                                    @else
                                        <button wire:click="$set('confirmingAnular', {{ $p->id }})"
                                                class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-md transition-colors" title="Anular">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-16 text-center text-gray-400">
                            <p class="text-sm">No se encontraron presupuestos en el período</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($presupuestos->hasPages())
        <div class="px-4 py-3 border-t border-[#DDE1E7]">
            {{ $presupuestos->links() }}
        </div>
        @endif
    </div>
</div>
