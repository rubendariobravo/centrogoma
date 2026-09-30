<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Gastos</h1>
            <p class="text-sm text-gray-500 mt-0.5">Egresos de caja por categoría</p>
        </div>
        <div class="flex items-center gap-2">
        <button wire:click="abrirCategorias"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-[#DDE1E7] rounded-lg hover:bg-gray-50 transition-colors">
            Categorías
        </button>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo gasto
        </button>
        </div>
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
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="lg:col-span-1">
            <label class="block text-xs font-medium text-gray-500 mb-1">Buscar</label>
            <input wire:model.live.debounce.400ms="search" type="text" placeholder="Descripción o comprobante"
                   class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Categoría</label>
            <select wire:model.live="filterCategoria" class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                <option value="">Todas</option>
                @foreach($categorias as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Caja</label>
            <select wire:model.live="filterCaja" class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                <option value="">Todas</option>
                @foreach($cajas as $c)
                    <option value="{{ $c->id }}">{{ $c->nombreEtiqueta() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Desde</label>
            <input wire:model.live="filterDesde" type="date" class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Hasta</label>
            <input wire:model.live="filterHasta" type="date" class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Categoría</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Descripción</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Caja</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Forma pago</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Monto</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($gastos as $gasto)
                    <tr class="hover:bg-[#E9ECF0] transition-colors">
                        <td class="px-4 py-3 text-gray-700">{{ $gasto->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                {{ $gasto->categoria?->nombre ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $gasto->descripcion ?: '—' }}
                            @if($gasto->proveedor)
                                <div class="text-xs text-gray-400">{{ $gasto->proveedor->razon_social }}</div>
                            @endif
                            @if($gasto->comprobante)
                                <div class="text-xs text-gray-400">Comp.: {{ $gasto->comprobante }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $gasto->caja?->nombreEtiqueta() ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700 capitalize">{{ $gasto->forma_pago }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-red-600">${{ number_format($gasto->monto, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button wire:click="openEdit({{ $gasto->id }})"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-slate-700 hover:bg-gray-100 transition-colors" title="Editar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button wire:click="confirmDelete({{ $gasto->id }})"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Eliminar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                            <p class="text-sm">No hay gastos registrados en el período</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($gastos->isNotEmpty())
                <tfoot>
                    <tr class="border-t border-[#DDE1E7] bg-[#E9ECF0]">
                        <td colspan="5" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Total del período</td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-red-700">${{ number_format($totalFiltrado, 2, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        @if($gastos->hasPages())
        <div class="px-4 py-3 border-t border-[#DDE1E7]">
            {{ $gastos->links() }}
        </div>
        @endif
    </div>

    {{-- Modal crear / editar --}}
    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 p-6 w-full max-w-lg">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-semibold text-gray-900">{{ $gastoId ? 'Editar gasto' : 'Nuevo gasto' }}</h3>
                <button wire:click="resetForm" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            @if($cajas->isEmpty())
            <div class="mb-4 flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg px-4 py-3">
                No hay ninguna caja abierta. Abra una caja en <span class="font-medium">Caja y Finanzas → Cajas</span> antes de registrar un gasto.
            </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                    <input wire:model="fecha" type="date"
                           class="w-full px-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('fecha') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                    @error('fecha') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium">$</span>
                        <input wire:model="monto" type="number" min="0" step="0.01" placeholder="0,00"
                               class="w-full pl-7 pr-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('monto') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                    </div>
                    @error('monto') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                    <select wire:model="categoria_gasto_id"
                            class="w-full px-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('categoria_gasto_id') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                        <option value="">Seleccione...</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                    @error('categoria_gasto_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Caja <span class="text-red-500">*</span></label>
                    <select wire:model="caja_id"
                            class="w-full px-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('caja_id') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                        <option value="">Seleccione...</option>
                        @foreach($cajas as $c)
                            <option value="{{ $c->id }}">{{ $c->nombreEtiqueta() }}</option>
                        @endforeach
                    </select>
                    @error('caja_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Forma de pago <span class="text-red-500">*</span></label>
                    <select wire:model="forma_pago"
                            class="w-full px-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('forma_pago') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                        @foreach($formasPago as $fp)
                            <option value="{{ $fp->codigo }}">{{ $fp->descripcion }}</option>
                        @endforeach
                    </select>
                    @error('forma_pago') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor (opcional)</label>
                    <select wire:model="proveedor_id"
                            class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                        <option value="">—</option>
                        @foreach($proveedores as $p)
                            <option value="{{ $p->id }}">{{ $p->razon_social }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Comprobante (opcional)</label>
                    <input wire:model="comprobante" type="text" maxlength="100" placeholder="Nro. factura, ticket, etc."
                           class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <input wire:model="descripcion" type="text" maxlength="255" placeholder="Detalle del gasto..."
                           class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button wire:click="resetForm"
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button wire:click="save" @disabled($cajas->isEmpty())
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Guardar gasto
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal confirmación de borrado --}}
    @if($confirmingDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 p-6 w-full max-w-sm">
            <h3 class="font-semibold text-gray-900 mb-2">Eliminar gasto</h3>
            <p class="text-sm text-gray-500 mb-6">El gasto se dará de baja y se revertirá su efecto en la caja. ¿Confirma?</p>
            <div class="flex gap-3">
                <button wire:click="$set('confirmingDelete', null)"
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button wire:click="delete"
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                    Eliminar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal categorías de gasto --}}
    @if($showCategorias)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" wire:keydown.escape.window="$set('showCategorias', false)">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 w-full max-w-lg flex flex-col max-h-[85vh]">
            <div class="px-6 pt-5 pb-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Categorías de gasto</h3>
                <button wire:click="$set('showCategorias', false)" class="text-gray-400 hover:text-gray-600" title="Cerrar">&times;</button>
            </div>

            <div class="px-6 py-3 border-b border-gray-100">
                <div class="flex gap-2">
                    <input type="text" wire:model="categoriaNombre" wire:keydown.enter="guardarCategoria"
                           placeholder="{{ $categoriaEditId ? 'Nuevo nombre' : 'Nueva categoría' }}"
                           class="flex-1 px-3 py-2 text-sm border {{ $errors->has('categoriaNombre') ? 'border-red-400' : 'border-[#DDE1E7]' }} rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    <button wire:click="guardarCategoria"
                            class="px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-800">
                        {{ $categoriaEditId ? 'Guardar' : 'Agregar' }}
                    </button>
                    @if($categoriaEditId)
                    <button wire:click="cancelarCategoria" class="px-3 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Cancelar</button>
                    @endif
                </div>
                @error('categoriaNombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="overflow-y-auto divide-y divide-gray-100">
                @forelse($todasCategorias as $cat)
                <div class="px-6 py-2 flex items-center justify-between gap-3 {{ $cat->activo ? '' : 'opacity-50' }}">
                    <div class="min-w-0">
                        <p class="text-sm text-gray-800 truncate">{{ $cat->nombre }}</p>
                        <p class="text-xs text-gray-400">{{ $cat->gastos_count }} gasto(s){{ $cat->activo ? '' : ' · inactiva' }}</p>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button wire:click="editarCategoria({{ $cat->id }})" class="px-2 py-1 text-xs text-slate-600 hover:bg-gray-100 rounded">Editar</button>
                        <button wire:click="toggleCategoria({{ $cat->id }})" class="px-2 py-1 text-xs {{ $cat->activo ? 'text-red-600 hover:bg-red-50' : 'text-emerald-600 hover:bg-emerald-50' }} rounded">
                            {{ $cat->activo ? 'Desactivar' : 'Activar' }}
                        </button>
                    </div>
                </div>
                @empty
                <p class="px-6 py-8 text-center text-sm text-gray-400">No hay categorías. Agregue la primera arriba.</p>
                @endforelse
            </div>
        </div>
    </div>
    @endif
</div>
