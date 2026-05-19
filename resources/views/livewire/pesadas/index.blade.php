<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Pesadas</h1>
            <p class="text-sm text-gray-500 mt-0.5">Control y ajuste de stock por pesada real</p>
        </div>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva pesada
        </button>
    </div>

    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <div class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[180px]">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar artículo..."
                       class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-500">Desde</label>
                <input wire:model.live="filterDesde" type="date" class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 outline-none">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-500">Hasta</label>
                <input wire:model.live="filterHasta" type="date" class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 outline-none">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Nº</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Artículo</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Stock sistema</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Stock real</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Diferencia</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Merma %</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Obs.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($pesadas as $pesada)
                    <tr class="hover:bg-[#E9ECF0] transition-colors">
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">#{{ $pesada->nro_pesada }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $pesada->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $pesada->articulo?->descripcion ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($pesada->stock_sistema, 3, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">{{ number_format($pesada->stock_real, 3, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-medium
                            {{ (float)$pesada->diferencia < 0 ? 'text-red-600' : ((float)$pesada->diferencia > 0 ? 'text-emerald-600' : 'text-gray-400') }}">
                            {{ number_format($pesada->diferencia, 3, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right text-amber-600">
                            {{ (float)$pesada->merma_porcentaje > 0 ? number_format($pesada->merma_porcentaje, 2).'%' : '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs truncate max-w-xs">{{ $pesada->observacion ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-16 text-center text-gray-400 text-sm">No hay pesadas en el período</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pesadas->hasPages())
        <div class="px-4 py-3 border-t border-[#DDE1E7]">{{ $pesadas->links() }}</div>
        @endif
    </div>

    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 w-full max-w-md">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[#DDE1E7]">
                <h2 class="text-base font-semibold text-gray-900">Nueva pesada</h2>
                <button wire:click="resetForm" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-5 space-y-4">
                <div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-xs text-amber-800">
                    Al guardar, el stock del artículo se ajustará automáticamente al stock real ingresado.
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Artículo *</label>
                    <select wire:model.live="articulo_id"
                            class="w-full text-sm border {{ $errors->has('articulo_id') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none bg-white">
                        <option value="">— Seleccionar —</option>
                        @foreach($articulos as $art)
                            <option value="{{ $art->id }}">{{ $art->descripcion }} (stock: {{ number_format($art->stock, 3, ',', '.') }})</option>
                        @endforeach
                    </select>
                    @error('articulo_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Fecha *</label>
                        <input wire:model="fecha" type="date"
                               class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Stock sistema</label>
                        <input type="number" value="{{ $stock_sistema }}" readonly
                               class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 bg-[#E9ECF0] text-gray-600">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Stock real pesado *</label>
                        <input wire:model.live="stock_real" type="number" step="0.001" min="0"
                               class="w-full text-sm border {{ $errors->has('stock_real') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none">
                        @error('stock_real') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Diferencia</label>
                        <input type="number" value="{{ $diferencia }}" readonly
                               class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 font-medium
                               {{ $diferencia < 0 ? 'text-red-600 bg-red-50' : ($diferencia > 0 ? 'text-emerald-600 bg-emerald-50' : 'bg-[#E9ECF0] text-gray-600') }}">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Observación</label>
                    <textarea wire:model="observacion" rows="2"
                              class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none resize-none"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 px-5 py-4 border-t border-[#DDE1E7]">
                <button wire:click="resetForm" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                <button wire:click="save" class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700">
                    Registrar y ajustar stock
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
