<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Descartes</h1>
            <p class="text-sm text-gray-500 mt-0.5">Registro de mermas y descartes de producto</p>
        </div>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo descarte
        </button>
    </div>

    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <div class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[180px]">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar artículo o motivo..."
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Artículo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Empleado</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Cantidad</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Motivo</th>
                        <th class="px-4 py-3 w-16"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($descartes as $descarte)
                    <tr class="hover:bg-[#E9ECF0] transition-colors group">
                        <td class="px-4 py-3 text-gray-700">{{ $descarte->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $descarte->articulo?->descripcion ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $descarte->empleado?->nombre ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-medium text-red-600">{{ number_format($descarte->cantidad, 3, ',', '.') }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $descarte->motivo ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button wire:click="openEdit({{ $descarte->id }})"
                                        class="p-1.5 text-gray-400 hover:text-slate-900 hover:bg-gray-100 rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button wire:click="confirmDelete({{ $descarte->id }})"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-16 text-center text-gray-400 text-sm">No hay descartes en el período</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($descartes->hasPages())
        <div class="px-4 py-3 border-t border-[#DDE1E7]">{{ $descartes->links() }}</div>
        @endif
    </div>

    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 w-full max-w-md">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[#DDE1E7]">
                <h2 class="text-base font-semibold text-gray-900">{{ $descarteId ? 'Editar descarte' : 'Nuevo descarte' }}</h2>
                <button wire:click="resetForm" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Artículo *</label>
                    <select wire:model="articulo_id"
                            class="w-full text-sm border {{ $errors->has('articulo_id') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none bg-white">
                        <option value="">— Seleccionar —</option>
                        @foreach($articulos as $art)
                            <option value="{{ $art->id }}">{{ $art->descripcion }}</option>
                        @endforeach
                    </select>
                    @error('articulo_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Empleado</label>
                        <select wire:model="empleado_id"
                                class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none bg-white">
                            <option value="">— Ninguno —</option>
                            @foreach($empleados as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Fecha *</label>
                        <input wire:model="fecha" type="date"
                               class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Cantidad *</label>
                        <input wire:model="cantidad" type="number" step="0.001" min="0"
                               class="w-full text-sm border {{ $errors->has('cantidad') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none">
                        @error('cantidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Motivo</label>
                        <input wire:model="motivo" type="text" placeholder="Vencimiento, daño..."
                               class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Observación</label>
                        <textarea wire:model="observacion" rows="2"
                                  class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none resize-none"></textarea>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 px-5 py-4 border-t border-[#DDE1E7]">
                <button wire:click="resetForm" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                <button wire:click="save" class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700">
                    {{ $descarteId ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    @if($confirmingDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 w-full max-w-sm p-6">
            <div class="flex items-start gap-3 mb-4">
                <div class="flex-shrink-0 w-9 h-9 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">¿Eliminar descarte?</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Esta acción no se puede deshacer.</p>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button wire:click="$set('confirmingDelete', null)" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                <button wire:click="delete" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">Eliminar</button>
            </div>
        </div>
    </div>
    @endif
</div>
