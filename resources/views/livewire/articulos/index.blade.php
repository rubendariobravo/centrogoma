<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Artículos</h1>
            <p class="text-sm text-gray-500 mt-0.5">Gestión de artículos y precios por rango</p>
        </div>
        <a href="{{ route('articulos.create') }}" wire:navigate
           class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo artículo
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
            {{-- Búsqueda --}}
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search"
                           type="text" placeholder="Buscar por código o descripción..."
                           class="w-full pl-9 pr-4 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
            </div>

            {{-- Rubro --}}
            <select wire:model.live="filterRubro"
                    class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                <option value="">Todos los rubros</option>
                @foreach($rubros as $rubro)
                    <option value="{{ $rubro->id }}">{{ $rubro->descripcion }}</option>
                @endforeach
            </select>

            {{-- Tipo venta --}}
            <select wire:model.live="filterTipo"
                    class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                <option value="">Tipo: todos</option>
                <option value="CAJA">Por caja/unidad</option>
                <option value="PESO">Por peso (kg)</option>
            </select>

            {{-- Estado --}}
            <select wire:model.live="filterEstado"
                    class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                <option value="A">Activos</option>
                <option value="I">Inactivos</option>
                <option value="">Todos</option>
            </select>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-4 py-3 text-left">
                            <button wire:click="sort('codigo')" class="flex items-center gap-1 text-xs font-semibold text-gray-500 uppercase tracking-wide hover:text-gray-900">
                                Código
                                @if($sortBy === 'codigo')
                                    <svg class="w-3 h-3 {{ $sortDir === 'desc' ? 'rotate-180' : '' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M5 10l5-5 5 5H5z"/></svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left">
                            <button wire:click="sort('descripcion')" class="flex items-center gap-1 text-xs font-semibold text-gray-500 uppercase tracking-wide hover:text-gray-900">
                                Descripción
                                @if($sortBy === 'descripcion')
                                    <svg class="w-3 h-3 {{ $sortDir === 'desc' ? 'rotate-180' : '' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M5 10l5-5 5 5H5z"/></svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Rubro</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Tipo</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Stock</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">IVA</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($articulos as $art)
                    <tr class="hover:bg-[#E9ECF0] transition-colors group">
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $art->codigo }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $art->descripcion }}</div>
                            @if($art->marca)
                                <div class="text-xs text-gray-400">{{ $art->marca->descripcion }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs">{{ $art->rubro?->descripcion ?? '—' }}</td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500">{{ $art->unidad_medida }}</td>
                        <td class="px-4 py-3 text-right">
                            @php $stockBajo = $art->stock_minimo > 0 && $art->stock <= $art->stock_minimo; @endphp
                            <span class="font-medium {{ $stockBajo ? 'text-red-600' : 'text-gray-900' }}">
                                {{ number_format($art->stock, 2, ',', '.') }}
                            </span>
                            <div class="text-xs text-gray-400">{{ $art->unidad_medida }}</div>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($art->alicuota_iva, 0) }}%</td>
                        <td class="px-4 py-3 text-center">
                            @if($art->estado === 'A')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Activo</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('articulos.edit', $art->id) }}" wire:navigate
                                   class="p-1.5 text-gray-400 hover:text-slate-900 hover:bg-gray-100 rounded-md transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <button wire:click="confirmDelete({{ $art->id }})"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-16 text-center text-gray-400">
                            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <p class="text-sm">No se encontraron artículos</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($articulos->hasPages())
        <div class="px-4 py-3 border-t border-[#DDE1E7]">
            {{ $articulos->links() }}
        </div>
        @endif
    </div>

    {{-- Modal confirmación de eliminación --}}
    @if($confirmingDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">¿Eliminar artículo?</h3>
                    <p class="text-sm text-gray-500">Esta acción no se puede deshacer.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <button wire:click="cancelDelete" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button wire:click="delete" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                    Eliminar
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
