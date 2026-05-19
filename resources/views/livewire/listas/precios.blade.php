<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('listas-precios') }}" wire:navigate class="hover:text-gray-700">Listas de precios</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-900">{{ $lista->nombre }}</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900">
                Tramos · {{ $lista->nombre }}
                @if($lista->es_default)
                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 align-middle">Default</span>
                @endif
                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium align-middle
                       {{ $verNetos ? 'bg-slate-200 text-slate-700' : 'bg-emerald-100 text-emerald-700' }}">
                    {{ $verNetos ? 'Viendo netos (sin IVA)' : 'Viendo finales (con IVA)' }}
                </span>
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">Definí los precios y tramos por cantidad para cada artículo en esta lista.</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="toggleVerNetos" type="button"
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-[#DDE1E7] rounded-lg hover:bg-[#E9ECF0] transition-colors inline-flex items-center gap-2"
                    title="Alternar entre precios finales (con IVA) y netos (sin IVA)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                {{ $verNetos ? 'Ver finales' : 'Ver netos' }}
            </button>
            <a href="{{ route('listas-precios.edit', $lista->id) }}" wire:navigate
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-[#DDE1E7] rounded-lg hover:bg-[#E9ECF0] transition-colors">Editar lista</a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg px-4 py-3">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        {{ session('error') }}
    </div>
    @endif

    {{-- Barra de acciones de precios --}}
    <div class="bg-white rounded-xl shadow-sm px-4 py-3 mb-4 flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide mr-1">Herramientas:</span>

        {{-- Ajuste % --}}
        <button wire:click="$set('showAjustePanel', {{ $showAjustePanel ? 'false' : 'true' }})" type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors
                       {{ $showAjustePanel ? 'bg-amber-50 border-amber-300 text-amber-700' : 'bg-white border-[#DDE1E7] text-gray-700 hover:bg-[#E9ECF0]' }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            Ajuste %
        </button>

        {{-- Exportar Excel --}}
        <a href="{{ route('listas-precios.exportar', array_filter([
                'lista'     => $lista->id,
                'verNetos'  => $verNetos ? 1 : 0,
                'rubro'     => $filterRubro ?: null,
                'search'    => $search ?: null,
            ])) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border bg-white border-[#DDE1E7] text-gray-700 hover:bg-[#E9ECF0] transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Exportar Excel
        </a>

        {{-- Importar Excel --}}
        <button wire:click="$set('showImportPanel', {{ $showImportPanel ? 'false' : 'true' }})" type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors
                       {{ $showImportPanel ? 'bg-blue-50 border-blue-300 text-blue-700' : 'bg-white border-[#DDE1E7] text-gray-700 hover:bg-[#E9ECF0]' }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/></svg>
            Importar Excel
        </button>

        {{-- Editar en grilla --}}
        @if($modoGrilla)
        <button wire:click="guardarGrilla" type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border bg-emerald-600 border-emerald-600 text-white hover:bg-emerald-700 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            Guardar grilla
        </button>
        <button wire:click="desactivarModoGrilla" type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border bg-white border-[#DDE1E7] text-gray-700 hover:bg-[#E9ECF0] transition-colors">
            Salir de grilla
        </button>
        @else
        <button wire:click="activarModoGrilla" type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border bg-white border-[#DDE1E7] text-gray-700 hover:bg-[#E9ECF0] transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M10 3v18M14 3v18M3 6a3 3 0 013-3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6z"/></svg>
            Editar en grilla
        </button>
        @endif
    </div>

    {{-- Panel: Ajuste % --}}
    @if($showAjustePanel)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="text-sm font-semibold text-amber-900">Ajuste de precios por porcentaje</h3>
                <p class="text-xs text-amber-700 mt-0.5">Sube o baja todos los precios de la lista en un porcentaje. Operación irreversible.</p>
            </div>
            <button wire:click="cerrarAjustePanel" class="text-amber-400 hover:text-amber-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-amber-800 mb-1">Porcentaje</label>
                <div class="relative">
                    <input wire:model="ajustePct" type="number" min="0.01" step="0.01" placeholder="ej. 10"
                           class="w-32 pr-7 pl-3 py-2 text-sm border border-amber-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent outline-none bg-white {{ $errors->has('ajustePct') ? 'border-red-400' : '' }}">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-amber-500 text-sm font-medium">%</span>
                </div>
                @error('ajustePct') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-amber-800 mb-1">Tipo</label>
                <div class="flex rounded-lg overflow-hidden border border-amber-300">
                    <button wire:click="$set('ajusteTipo', 'subir')" type="button"
                            class="px-3 py-2 text-xs font-medium transition-colors {{ $ajusteTipo === 'subir' ? 'bg-amber-600 text-white' : 'bg-white text-amber-700 hover:bg-amber-50' }}">
                        Subir
                    </button>
                    <button wire:click="$set('ajusteTipo', 'bajar')" type="button"
                            class="px-3 py-2 text-xs font-medium transition-colors {{ $ajusteTipo === 'bajar' ? 'bg-amber-600 text-white' : 'bg-white text-amber-700 hover:bg-amber-50' }}">
                        Bajar
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-amber-800 mb-1">Rubro (opcional)</label>
                <select wire:model="ajusteRubroFiltro"
                        class="text-sm border border-amber-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-transparent outline-none bg-white">
                    <option value="">Todos los rubros</option>
                    @foreach($rubros as $r)
                        <option value="{{ $r->id }}">{{ $r->descripcion }}</option>
                    @endforeach
                </select>
            </div>
            <button wire:click="aplicarAjuste" wire:confirm="¿Aplicar ajuste de {{ $ajustePct }}% a todos los precios{{ $ajusteRubroFiltro ? ' del rubro seleccionado' : '' }}? Esta acción no se puede deshacer."
                    class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition-colors">
                Aplicar ajuste
            </button>
        </div>
    </div>
    @endif

    {{-- Panel: Importar Excel --}}
    @if($showImportPanel)
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="text-sm font-semibold text-blue-900">Importar precios desde Excel</h3>
                <p class="text-xs text-blue-700 mt-0.5">Subí el archivo Excel exportado con los precios modificados. Solo se actualiza la columna resaltada en amarillo (columna G).</p>
            </div>
            <button wire:click="cerrarImportPanel" class="text-blue-400 hover:text-blue-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Resultado de importación --}}
        @if($importResultado)
        <div class="mb-3 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg px-3 py-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ $importResultado }}
        </div>
        @if(count($importErrores) > 0)
        <div class="mb-3 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
            <p class="text-xs font-semibold text-red-700 mb-1">Filas con errores:</p>
            <ul class="text-xs text-red-600 space-y-0.5 list-disc list-inside">
                @foreach($importErrores as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        @endif

        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-blue-800 mb-1">Precios en el Excel</label>
                <div class="flex rounded-lg overflow-hidden border border-blue-300">
                    <button wire:click="$set('importModo', 'final')" type="button"
                            class="px-3 py-2 text-xs font-medium transition-colors {{ $importModo === 'final' ? 'bg-blue-600 text-white' : 'bg-white text-blue-700 hover:bg-blue-50' }}">
                        Finales c/IVA
                    </button>
                    <button wire:click="$set('importModo', 'neto')" type="button"
                            class="px-3 py-2 text-xs font-medium transition-colors {{ $importModo === 'neto' ? 'bg-blue-600 text-white' : 'bg-white text-blue-700 hover:bg-blue-50' }}">
                        Netos s/IVA
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-blue-800 mb-1">Archivo .xlsx</label>
                <input wire:model="importFile" type="file" accept=".xlsx,.xls"
                       class="text-xs text-gray-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
                @error('importFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button wire:click="importarExcel"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                <span wire:loading.remove wire:target="importarExcel">Importar</span>
                <span wire:loading wire:target="importarExcel">Procesando...</span>
            </button>
        </div>
    </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <div class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar artículo por código o descripción..."
                           class="w-full pl-9 pr-4 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
            </div>
            <select wire:model.live="filterRubro"
                    class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                <option value="">Todos los rubros</option>
                @foreach($rubros as $r)
                    <option value="{{ $r->id }}">{{ $r->descripcion }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        @if($modoGrilla)
        <div class="px-4 py-2 bg-slate-50 border-b border-[#DDE1E7] flex items-center gap-2 text-xs text-slate-600">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Modo grilla: editá los precios directamente y presioná <strong class="mx-1">Guardar grilla</strong> para confirmar.
            Los precios son {{ $verNetos ? 'netos (sin IVA)' : 'finales (con IVA)' }}.
        </div>
        @endif

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-32">Código</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Artículo</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Tramos</th>
                    @if(!$modoGrilla)
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide w-28"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-[#E9ECF0]">
                @forelse($articulos as $articulo)
                <tr class="hover:bg-[#E9ECF0]/50 transition-colors">
                    <td class="px-4 py-3 align-top font-mono text-xs text-gray-700">{{ $articulo->codigo }}</td>
                    <td class="px-4 py-3 align-top">
                        <p class="font-medium text-gray-900">{{ $articulo->descripcion }}</p>
                        <p class="text-xs text-gray-400">{{ $articulo->rubro?->descripcion ?? '—' }} · costo ${{ number_format($articulo->precio_costo, 2, ',', '.') }}</p>
                    </td>
                    <td class="px-4 py-3 align-top">
                        @if($articulo->precios->isEmpty())
                            <span class="text-xs text-gray-400 italic">Sin tramos en esta lista</span>
                        @elseif($modoGrilla)
                            {{-- Grilla: inputs editables por tramo --}}
                            <div class="flex flex-wrap gap-2">
                                @foreach($articulo->precios as $tramo)
                                    @php
                                        $iva = (float) $articulo->alicuota_iva;
                                    @endphp
                                    <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md border border-[#DDE1E7] bg-amber-50 text-xs">
                                        <span class="font-mono text-gray-500 text-xs">
                                            {{ rtrim(rtrim(number_format($tramo->cantidad_desde, 3, ',', ''), '0'), ',') }}
                                            @if($tramo->cantidad_hasta !== null)
                                                –{{ rtrim(rtrim(number_format($tramo->cantidad_hasta, 3, ',', ''), '0'), ',') }}
                                            @else
                                                +
                                            @endif
                                        </span>
                                        <span class="text-gray-400">$</span>
                                        <input wire:model="grillaPrecios.{{ $tramo->id }}"
                                               type="number" min="0" step="0.01"
                                               class="w-24 px-1 py-0.5 text-xs border border-amber-300 rounded focus:ring-1 focus:ring-amber-500 focus:border-transparent outline-none bg-white font-semibold text-gray-900">
                                    </div>
                                @endforeach
                            </div>
                        @else
                            {{-- Vista normal: chips clicables --}}
                            <div class="flex flex-wrap gap-2">
                                @foreach($articulo->precios as $tramo)
                                    @php
                                        $iva = (float) $articulo->alicuota_iva;
                                        $precioMostrado = $verNetos
                                            ? (float) $tramo->precio
                                            : \App\Support\Pricing::final((float) $tramo->precio, $iva);
                                    @endphp
                                    <button wire:click="abrirModalEditar({{ $tramo->id }})"
                                            class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-md border text-xs transition-colors
                                                   @if($tramo->activo) bg-white border-[#DDE1E7] hover:border-slate-900 @else bg-gray-50 border-gray-200 text-gray-400 @endif">
                                        <span class="font-mono">
                                            {{ rtrim(rtrim(number_format($tramo->cantidad_desde, 3, ',', ''), '0'), ',') }}
                                            @if($tramo->cantidad_hasta !== null)
                                                –{{ rtrim(rtrim(number_format($tramo->cantidad_hasta, 3, ',', ''), '0'), ',') }}
                                            @else
                                                +
                                            @endif
                                        </span>
                                        <span class="font-semibold text-gray-900">${{ number_format($precioMostrado, 2, ',', '.') }}</span>
                                        <button wire:click.stop="confirmDeleteTramo({{ $tramo->id }})"
                                                class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-red-600 transition-all" title="Eliminar tramo">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    @if(!$modoGrilla)
                    <td class="px-4 py-3 align-top text-right">
                        <button wire:click="abrirModalNuevo({{ $articulo->id }})"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-slate-700 bg-slate-100 rounded-md hover:bg-slate-200 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Tramo
                        </button>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $modoGrilla ? 3 : 4 }}" class="px-4 py-16 text-center text-gray-400">
                        <p class="text-sm">No hay artículos para mostrar.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if(!$modoGrilla && method_exists($articulos, 'hasPages') && $articulos->hasPages())
        <div class="px-4 py-3 border-t border-[#DDE1E7]">
            {{ $articulos->links() }}
        </div>
        @endif
    </div>

    {{-- Modal crear/editar tramo --}}
    @if($showTramoModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 p-6 max-w-md w-full">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="font-semibold text-gray-900">{{ $editingTramoId ? 'Editar tramo' : 'Nuevo tramo' }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $editingArticuloNombre }}</p>
                </div>
                <button wire:click="cerrarModal" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Cantidad desde <span class="text-red-500">*</span></label>
                        <input wire:model="tramoDesde" type="number" min="0" step="0.001"
                               class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('tramoDesde') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                        @error('tramoDesde') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Cantidad hasta</label>
                        <input wire:model="tramoHasta" type="number" min="0" step="0.001" placeholder="∞ (vacío)"
                               class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('tramoHasta') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                        @error('tramoHasta') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-400 mt-1">Vacío = sin tope superior</p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        {{ $verNetos ? 'Precio neto (sin IVA)' : 'Precio final (con IVA)' }}
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                        <input wire:model.live.debounce.400ms="tramoPrecio" type="number" min="0" step="0.01"
                               class="w-full pl-7 pr-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('tramoPrecio') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                    </div>
                    @error('tramoPrecio') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @php
                        $valIngresado = (float) $tramoPrecio;
                        $ivaArt       = (float) $editingArticuloIva;
                        $otroLado = $verNetos
                            ? \App\Support\Pricing::final($valIngresado, $ivaArt)
                            : \App\Support\Pricing::neto($valIngresado, $ivaArt);
                    @endphp
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $verNetos ? 'Final con IVA' : 'Neto sin IVA' }}
                        ({{ rtrim(rtrim(number_format($ivaArt, 2, ',', ''), '0'), ',') }}%):
                        <span class="font-medium text-gray-700">${{ number_format($otroLado, 2, ',', '.') }}</span>
                    </p>
                </div>
                <label class="flex items-start gap-3 cursor-pointer">
                    <input wire:model="tramoActivo" type="checkbox" class="mt-0.5 rounded border-[#DDE1E7] text-slate-900 focus:ring-slate-900">
                    <span class="text-sm font-medium text-gray-900">Tramo activo</span>
                </label>
            </div>

            <div class="flex gap-3 mt-6">
                <button wire:click="cerrarModal"
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Cancelar</button>
                <button wire:click="guardarTramo"
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-700 transition-colors">Guardar</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal confirmación eliminación de tramo --}}
    @if($confirmingDeleteTramo)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 p-6 max-w-sm w-full">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">¿Eliminar tramo?</h3>
                    <p class="text-sm text-gray-500">Esta acción no se puede deshacer.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <button wire:click="cancelDeleteTramo" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Cancelar</button>
                <button wire:click="deleteTramo" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">Eliminar</button>
            </div>
        </div>
    </div>
    @endif
</div>
