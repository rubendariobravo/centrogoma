<div>
    {{-- Barra superior --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('produccion') }}" wire:navigate
               class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    Producción #{{ $produccion->nro_produccion }}
                </h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    Creada el {{ $produccion->created_at->format('d/m/Y H:i') }}
                    · por {{ $produccion->user?->name ?? '—' }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if($produccion->estado === 'cerrada')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Cerrada</span>
                <a href="{{ route('produccion.pdf', $produccion) }}" target="_blank"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Descargar PDF
                </a>
            @elseif($produccion->estado === 'anulada')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-600">Anulada</span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Abierta</span>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg px-4 py-3">
        {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    @php $bloqueado = $produccion->estado !== 'abierta'; @endphp

    {{-- Cabecera --}}
    <div class="bg-white rounded-xl shadow-sm p-5 mb-4">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Datos de la producción</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Fecha *</label>
                <input wire:model="fecha" type="date" {{ $bloqueado ? 'disabled' : '' }}
                       class="w-full text-sm border {{ $errors->has('fecha') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none {{ $bloqueado ? 'bg-[#E9ECF0] text-gray-500' : '' }}">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Tipo de producción</label>
                <select wire:model="tipo_produccion" {{ $bloqueado ? 'disabled' : '' }}
                        class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none bg-white {{ $bloqueado ? 'bg-[#E9ECF0] text-gray-500' : '' }}">
                    <option value="troceo">Troceo</option>
                    <option value="milanesas">Milanesas</option>
                    <option value="otras">Otras</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Empleado</label>
                <select wire:model="empleado_id" {{ $bloqueado ? 'disabled' : '' }}
                        class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none bg-white {{ $bloqueado ? 'bg-[#E9ECF0] text-gray-500' : '' }}">
                    <option value="">— Ninguno —</option>
                    @foreach($empleados as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Observación</label>
                <input wire:model="observacion" type="text" {{ $bloqueado ? 'disabled' : '' }}
                       class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none {{ $bloqueado ? 'bg-[#E9ECF0] text-gray-500' : '' }}"
                       placeholder="Notas opcionales">
            </div>
        </div>
    </div>

    {{-- Insumo de entrada --}}
    <div class="bg-white rounded-xl shadow-sm p-5 mb-4">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">
            Insumo de entrada
            <span class="ml-2 text-xs font-normal text-gray-400 normal-case">(artículo que se procesa)</span>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-1">
                <label class="block text-xs font-medium text-gray-500 mb-1">Artículo *</label>
                <select wire:model.live="insumo_articulo_id" {{ $bloqueado ? 'disabled' : '' }}
                        class="w-full text-sm border {{ $errors->has('insumo_articulo_id') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none bg-white {{ $bloqueado ? 'bg-[#E9ECF0] text-gray-500' : '' }}">
                    <option value="">— Seleccionar artículo —</option>
                    @foreach($articulosInsumo as $art)
                        <option value="{{ $art->id }}">{{ $art->descripcion }}</option>
                    @endforeach
                </select>
                @error('insumo_articulo_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Cantidad (kg / unidades) *</label>
                <input wire:model.live="insumo_cantidad" type="number" step="0.001" min="0"
                       {{ $bloqueado ? 'disabled' : '' }}
                       class="w-full text-sm border {{ $errors->has('insumo_cantidad') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none {{ $bloqueado ? 'bg-[#E9ECF0]' : '' }}">
                @error('insumo_cantidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Precio costo ($)</label>
                <input wire:model.live="insumo_precio_costo" type="number" step="0.0001" min="0"
                       {{ $bloqueado ? 'disabled' : '' }}
                       class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none {{ $bloqueado ? 'bg-[#E9ECF0]' : '' }}">
                <p class="text-xs text-gray-400 mt-1">
                    Costo total entrada:
                    <strong class="text-gray-700">${{ number_format($this->costo_entrada, 2, ',', '.') }}</strong>
                </p>
            </div>
        </div>
    </div>

    {{-- Productos obtenidos --}}
    <div class="bg-white rounded-xl shadow-sm p-5 mb-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">
                Productos obtenidos
                <span class="ml-2 text-xs font-normal text-gray-400 normal-case">(subproductos que ingresan al stock)</span>
            </h2>
            @if(! $bloqueado)
            <button wire:click="agregarProducto" type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Agregar producto
            </button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Artículo *</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 w-32">Cantidad *</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 w-36">Precio costo ($)</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 w-28">Rendimiento %</th>
                        @if(! $bloqueado)
                        <th class="px-3 py-2 w-10"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($productos as $i => $prod)
                    <tr>
                        <td class="px-3 py-2">
                            <select wire:model.live="productos.{{ $i }}.articulo_id" {{ $bloqueado ? 'disabled' : '' }}
                                    class="w-full text-sm border {{ $errors->has("productos.$i.articulo_id") ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-slate-900 outline-none bg-white {{ $bloqueado ? 'bg-[#E9ECF0]' : '' }}">
                                <option value="">— Seleccionar —</option>
                                @foreach($articulosProducto as $art)
                                    <option value="{{ $art->id }}">{{ $art->descripcion }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-3 py-2">
                            <input wire:model.live="productos.{{ $i }}.cantidad" type="number" step="0.001" min="0"
                                   {{ $bloqueado ? 'disabled' : '' }}
                                   class="w-full text-sm border {{ $errors->has("productos.$i.cantidad") ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-2 py-1.5 text-right focus:ring-2 focus:ring-slate-900 outline-none {{ $bloqueado ? 'bg-[#E9ECF0]' : '' }}">
                        </td>
                        <td class="px-3 py-2">
                            <input wire:model.live="productos.{{ $i }}.precio_costo" type="number" step="0.0001" min="0"
                                   {{ $bloqueado ? 'disabled' : '' }}
                                   class="w-full text-sm border border-[#DDE1E7] rounded-lg px-2 py-1.5 text-right focus:ring-2 focus:ring-slate-900 outline-none {{ $bloqueado ? 'bg-[#E9ECF0]' : '' }}">
                        </td>
                        <td class="px-3 py-2 text-right text-gray-500 text-xs">
                            @php
                                $ts = $this->total_salida;
                                $rend = ($ts > 0 && ($prod['cantidad'] ?? 0) > 0)
                                    ? round(($prod['cantidad'] / $ts) * 100, 1)
                                    : 0;
                            @endphp
                            {{ $rend > 0 ? $rend . '%' : '—' }}
                        </td>
                        @if(! $bloqueado)
                        <td class="px-3 py-2 text-center">
                            <button wire:click="quitarProducto({{ $i }})" type="button"
                                    class="p-1 text-gray-300 hover:text-red-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-3 py-8 text-center text-gray-400 text-sm">
                            No hay productos. Hacé clic en "Agregar producto".
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Resumen --}}
    <div class="bg-white rounded-xl shadow-sm p-5 mb-6">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Resumen</h2>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-[#E9ECF0] rounded-lg p-3">
                <p class="text-xs text-gray-500 mb-1">Total entrada</p>
                <p class="text-lg font-semibold text-gray-900">{{ number_format($this->total_entrada, 3, ',', '.') }}</p>
            </div>
            <div class="bg-[#E9ECF0] rounded-lg p-3">
                <p class="text-xs text-gray-500 mb-1">Total salida</p>
                <p class="text-lg font-semibold text-gray-900">{{ number_format($this->total_salida, 3, ',', '.') }}</p>
            </div>
            <div class="rounded-lg p-3 {{ $this->merma > 0 ? 'bg-amber-50' : 'bg-[#E9ECF0]' }}">
                <p class="text-xs text-gray-500 mb-1">Merma</p>
                <p class="text-lg font-semibold {{ $this->merma > 0 ? 'text-amber-600' : 'text-gray-900' }}">
                    {{ number_format($this->merma, 3, ',', '.') }}
                    @if($this->merma > 0)
                        <span class="text-sm font-normal text-gray-400">({{ $this->merma_porcentaje }}%)</span>
                    @endif
                </p>
            </div>
            <div class="bg-[#E9ECF0] rounded-lg p-3">
                <p class="text-xs text-gray-500 mb-1">Costo entrada total</p>
                <p class="text-lg font-semibold text-gray-900">${{ number_format($this->costo_entrada, 2, ',', '.') }}</p>
            </div>
            <div class="bg-slate-900 rounded-lg p-3">
                <p class="text-xs text-slate-300 mb-1">Costo promedio / kg</p>
                <p class="text-lg font-semibold text-white">${{ number_format($this->costo_promedio, 4, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- Acciones --}}
    @if($produccion->estado === 'abierta')
    <div class="flex items-center justify-between">
        <button wire:click="$set('confirmandoAnulado', true)" type="button"
                class="px-4 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
            Anular producción
        </button>
        <div class="flex gap-3">
            <button wire:click="guardar" type="button"
                    class="px-5 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Guardar borrador
            </button>
            <button wire:click="$set('confirmandoCierre', true)" type="button"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                Cerrar producción
            </button>
        </div>
    </div>
    @endif

    {{-- Modal confirmación cierre --}}
    @if($confirmandoCierre)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 w-full max-w-md p-6">
            <div class="flex items-start gap-3 mb-5">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">¿Cerrar producción?</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Esta acción <strong>no se puede deshacer</strong>. Se actualizará el stock de todos los artículos:
                    </p>
                    <ul class="mt-2 text-sm text-gray-600 space-y-1">
                        <li>· El insumo de entrada <span class="text-red-600">restará</span> del stock</li>
                        <li>· Cada producto obtenido <span class="text-emerald-600">sumará</span> al stock</li>
                        <li>· El precio costo de cada producto se actualizará al costo promedio</li>
                    </ul>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button wire:click="$set('confirmandoCierre', false)"
                        class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                <button wire:click="cerrar" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="cerrar">Sí, cerrar y actualizar stock</span>
                    <span wire:loading wire:target="cerrar">Procesando...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal confirmación anulación --}}
    @if($confirmandoAnulado)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 w-full max-w-sm p-6">
            <div class="flex items-start gap-3 mb-4">
                <div class="flex-shrink-0 w-9 h-9 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">¿Anular producción?</h3>
                    <p class="text-sm text-gray-500 mt-0.5">La producción quedará como anulada sin afectar el stock.</p>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button wire:click="$set('confirmandoAnulado', false)" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                <button wire:click="anular" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">Anular</button>
            </div>
        </div>
    </div>
    @endif
</div>
