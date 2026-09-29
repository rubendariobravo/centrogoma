@php
$defaultItem = [['articulo_id' => null, 'descripcion' => '', 'unidad_medida' => 'unidad', 'cantidad' => 1, 'precio' => 0, 'alicuota_iva' => 21, 'descuento' => 0, 'subtotal' => 0]];
$defaultClienteData = ['razon_social' => '', 'percepcion_iva' => false, 'porcentaje_iibb' => 0, 'es_mayorista' => false, 'tipoiva_codigo' => '99'];
@endphp
<div>
<script>
window._articulosVenta = {{ Js::from($articulosData) }};
window._initialItems = {{ Js::from($items ?: $defaultItem) }};
window._initialClienteData = {{ Js::from($clienteData ?: $defaultClienteData) }};
window._initialDescuento = {{ Js::from($descuento_general ?: 0) }};
</script>
<div
    x-data="ventaForm({
        items: window._initialItems,
        clienteData: window._initialClienteData,
        descuentoGeneral: window._initialDescuento,
        articulosData: window._articulosVenta,
    })"
    x-init="
        wire = $wire;
        wire.items = items;
        wire.clienteData = clienteData;
        wire.descuento_general = descuentoGeneral;
    "
    x-on:keydown.window="
        if (event.key === 'F10') {
            event.preventDefault();
            @if(! $readOnly && ! $sinCaja)
                guardar($wire);
            @endif
        }
    "
>
    {{-- Alerta sin caja --}}
    @if($sinCaja)
    <div class="mb-4 flex items-center gap-3 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-xl px-4 py-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <span>No hay una caja abierta. <a href="{{ route('cajas') }}" wire:navigate class="font-medium underline">Abrir caja</a> antes de registrar ventas.</span>
    </div>
    @endif

    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('ventas') }}" wire:navigate class="hover:text-gray-700">Ventas</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-900">{{ $ventaId ? 'Venta #'.$ventaId : 'Nueva venta' }}</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900">
                @if($readOnly) Venta #{{ $ventaId }} @else Nueva venta @endif
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" x-on:click="toggleVerNetos()"
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-[#DDE1E7] rounded-lg hover:bg-[#E9ECF0] transition-colors inline-flex items-center gap-2"
                    title="Alternar entre precios finales (con IVA) y netos (sin IVA)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span x-text="verNetos ? 'Ver finales' : 'Ver netos'"></span>
            </button>
            @if(! $readOnly)
            <a href="{{ route('ventas') }}" wire:navigate
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-[#DDE1E7] rounded-lg hover:bg-[#E9ECF0] transition-colors">
                Cancelar
            </a>
            <button
                x-on:click="guardar($wire)"
                :disabled="{{ $sinCaja ? 'true' : 'false' }}"
                title="Registrar venta (F10)"
                class="px-5 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                Registrar venta <span class="ml-1 opacity-60 text-xs font-normal">F10</span>
            </button>
            @endif
        </div>
    </div>

    {{-- Banner: origen presupuesto --}}
    @if($presupuestoOrigenId && ! $readOnly)
    <div class="mb-4 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg px-4 py-3">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        <span>Pre-cargado desde <strong>Presupuesto #{{ $presupuestoOrigenId }}</strong>. Al registrar la venta, el presupuesto quedará marcado como <em>Facturado</em>.</span>
    </div>
    @endif

    {{-- Formulario --}}
    <div class="space-y-4">

        {{-- Encabezado del comprobante --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Cliente</label>
                    <select wire:model.live="cliente_id"
                            {{ $readOnly ? 'disabled' : '' }}
                            class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white @error('cliente_id') border-red-400 @enderror">
                        <option value="">Consumidor final</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}">{{ $cliente->razon_social }}</option>
                        @endforeach
                    </select>
                    @error('cliente_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Fecha</label>
                    <input wire:model="fecha" type="date"
                           {{ $readOnly ? 'readonly' : '' }}
                           class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tipo comprobante</label>
                    <div class="flex gap-2">
                        <select wire:model="tipo_comprobante"
                                {{ $readOnly ? 'disabled' : '' }}
                                class="flex-1 px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                            <option value="factura">Factura</option>
                            <option value="nota_credito">Nota crédito</option>
                            <option value="nota_debito">Nota débito</option>
                        </select>
                        <select wire:model="letra"
                                {{ $readOnly ? 'disabled' : '' }}
                                class="w-16 px-2 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white font-bold">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Info cliente si seleccionado --}}
            <div x-show="$wire.clienteData?.razon_social" x-cloak class="mt-3 pt-3 border-t border-gray-50">
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <span x-text="$wire.clienteData?.razon_social" class="font-medium text-gray-700"></span>
                    <span x-show="$wire.clienteData?.es_mayorista" class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full">Mayorista</span>
                    <span x-show="($wire.clienteData?.porcentaje_iibb || 0) > 0">
                        IIBB: <span x-text="parseFloat($wire.clienteData?.porcentaje_iibb || 0).toFixed(2) + '%'" class="font-medium text-gray-700"></span>
                    </span>
                    <span x-show="$wire.clienteData?.percepcion_iva" class="text-amber-600">Percepción IVA</span>
                </div>
            </div>

            {{-- Selector de lista de precios --}}
            @if(! $readOnly)
            <div class="mt-3 pt-3 border-t border-gray-50 flex flex-wrap items-center gap-x-3 gap-y-2">
                <span class="text-xs text-gray-400 font-medium uppercase tracking-wide">Lista activa:</span>

                @if(auth()->user()->hasRole(['super-admin', 'admin']))
                    {{-- Selector editable para admins --}}
                    <select wire:change="cambiarLista($event.target.value)"
                            class="text-xs border border-[#DDE1E7] rounded-lg px-2.5 py-1.5 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white font-medium text-gray-800">
                        @foreach($listasPrecios as $lp)
                            <option value="{{ $lp->id }}" @selected($listaIdForzada == $lp->id)>
                                {{ $lp->nombre }}
                                @if($lp->es_default) (default) @endif
                            </option>
                        @endforeach
                    </select>

                    @if($listaModificada)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01"/></svg>
                            Cambiada
                        </span>
                        <button wire:click="restaurarLista" type="button"
                                class="text-xs text-gray-400 hover:text-gray-700 underline underline-offset-2 transition-colors">
                            Restaurar
                        </button>
                    @endif
                @else
                    {{-- Solo lectura para roles sin permiso --}}
                    <span class="text-xs font-medium text-gray-800">
                        {{ $listaActual?->nombre ?? '—' }}
                    </span>
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" title="No tenés permiso para cambiar la lista">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    @if($listaModificada)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Cambiada</span>
                    @endif
                @endif
            </div>
            @endif
        </div>

        {{-- Tabla de ítems --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-8">#</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Artículo</th>
                            <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-24">Cantidad</th>
                            <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-16">UM</th>
                            <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide w-28">
                                <span x-text="verNetos ? 'Precio neto' : 'Precio final'"></span>
                            </th>
                            <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-16">IVA</th>
                            <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-20">Desc.%</th>
                            <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide w-32">Subtotal</th>
                            @if(! $readOnly)
                            <th class="px-3 py-2.5 w-10"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="border-b border-gray-50">
                                <td class="px-3 py-2 text-xs text-gray-400 text-center" x-text="index + 1"></td>
                                <td class="px-3 py-2">
                                    @if(! $readOnly)
                                    <div class="relative" x-on:click.outside="item._open = false">
                                        <input type="text"
                                               :data-row="index"
                                               data-field="articulo"
                                               x-model="item._search"
                                               x-on:focus="openDropdown(index, $el)"
                                               x-on:input="openDropdown(index, $el)"
                                               x-on:keydown.enter.prevent="
                                                   const _f = filteredArticulos(item);
                                                   if (item._open && _f.length > 0) {
                                                       seleccionarCombo(index, _f[item._highlight]);
                                                   } else if (item.articulo_id) {
                                                       item._open = false;
                                                       focusField(index, 'cantidad');
                                                   }
                                               "
                                               x-on:keydown.arrow-down.prevent="item._highlight = Math.min(item._highlight + 1, filteredArticulos(item).length - 1)"
                                               x-on:keydown.arrow-up.prevent="item._highlight = Math.max(item._highlight - 1, 0)"
                                               x-on:keydown.escape="item._open = false"
                                               placeholder="Buscar artículo..."
                                               autocomplete="off"
                                               class="w-full px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                                        <div x-show="item._open && filteredArticulos(item).length > 0"
                                             :style="item._dropdownStyle"
                                             class="fixed z-[9999] bg-white border border-[#DDE1E7] rounded-md shadow-lg max-h-52 overflow-y-auto">
                                            <template x-for="(art, artIdx) in filteredArticulos(item)" :key="art.id">
                                                <div x-on:click="seleccionarCombo(index, art)"
                                                     x-on:mouseenter="item._highlight = artIdx"
                                                     :class="artIdx === item._highlight ? 'bg-slate-100 text-slate-900' : 'hover:bg-gray-50 text-gray-700'"
                                                     class="px-3 py-2 text-sm cursor-pointer flex items-center gap-2">
                                                    <span class="font-mono text-xs text-gray-400 shrink-0" x-text="art.codigo"></span>
                                                    <span class="truncate" x-text="art.descripcion"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    @else
                                    <span x-text="item.descripcion" class="text-gray-900"></span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <input x-model.number="item.cantidad"
                                           x-on:input.debounce.300ms="cotizarFila(index)"
                                           x-on:keydown.enter.prevent="focusField(index, 'precio')"
                                           :data-row="index"
                                           data-field="cantidad"
                                           type="number" min="0.001" step="0.001"
                                           {{ $readOnly ? 'readonly' : '' }}
                                           class="w-full text-center px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $readOnly ? 'bg-[#E9ECF0]' : '' }}">
                                </td>
                                <td class="px-3 py-2 text-center text-xs text-gray-500" x-text="item.unidad_medida"></td>
                                <td class="px-3 py-2">
                                    <div class="relative">
                                        <input x-model.number="item._precioDisplay"
                                               x-on:focus="onPrecioFocus(index)"
                                               x-on:blur="onPrecioBlur(index)"
                                               x-on:input.debounce.400ms="onPrecioInput($event.target.value, index)"
                                               x-on:keydown.enter.prevent="focusField(index, 'descuento')"
                                               :data-row="index"
                                               data-field="precio"
                                               type="number" min="0" step="0.01"
                                               {{ $readOnly ? 'readonly' : '' }}
                                               :class="item.precio_manual ? 'border-amber-400 pr-6' : 'border-[#DDE1E7]'"
                                               class="w-full text-right px-2 py-1.5 text-sm rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $readOnly ? 'bg-[#E9ECF0]' : '' }}">
                                        @if(! $readOnly)
                                        <button type="button"
                                                x-show="item.precio_manual"
                                                x-on:click="restaurarPrecio(index)"
                                                title="Restaurar precio del motor"
                                                class="absolute right-1.5 top-1/2 -translate-y-1/2 text-amber-500 hover:text-amber-700">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-center text-xs text-gray-500">
                                    <span x-text="item.alicuota_iva + '%'"></span>
                                </td>
                                <td class="px-3 py-2">
                                    <input x-model.number="item.descuento"
                                           x-on:input="recalcularItem(index)"
                                           x-on:keydown.enter.prevent="
                                               if (index === items.length - 1) {
                                                   addItemAndFocus();
                                               } else {
                                                   focusField(index + 1, 'articulo');
                                               }
                                           "
                                           :data-row="index"
                                           data-field="descuento"
                                           type="number" min="0" max="100" step="0.01"
                                           {{ $readOnly ? 'readonly' : '' }}
                                           class="w-full text-center px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $readOnly ? 'bg-[#E9ECF0]' : '' }}">
                                </td>
                                <td class="px-3 py-2 text-right font-medium text-gray-900">
                                    <span x-text="'$' + formatNum(subtotalMostrado(item))"></span>
                                </td>
                                @if(! $readOnly)
                                <td class="px-3 py-2 text-center">
                                    <button x-on:click="removeItem(index)"
                                            x-show="items.length > 1"
                                            class="p-1 text-gray-300 hover:text-red-500 transition-colors rounded">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </td>
                                @endif
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            @if(! $readOnly)
            <div class="px-4 py-3 border-t border-gray-50">
                <button x-on:click="addItem()"
                        class="inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900 font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Agregar ítem
                </button>
            </div>
            @endif
        </div>

        {{-- Panel inferior: observación + totales + pago --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- Observación y pago --}}
            <div class="lg:col-span-2 space-y-4">
                @if(! $readOnly)
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">Formas de pago</h3>
                        <button type="button" x-on:click="addPago()" class="text-xs font-medium text-slate-600 hover:text-slate-900">+ Agregar pago</button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(pago, index) in pagos" :key="index">
                            <div>
                            <div class="grid grid-cols-12 gap-2 items-center">
                                <select x-model="pago.forma_pago"
                                        x-on:change="initCheque(pago)"
                                        x-on:keydown.enter.prevent="focusPagoField(index, 'monto')"
                                        :data-pago-row="index"
                                        data-pago-field="forma_pago"
                                        class="col-span-6 px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="cuenta_corriente">Cta. Cte.</option>
                                    <option value="otro">Otro</option>
                                </select>
                                <div class="col-span-5 relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                                    <input type="number" step="0.01" min="0"
                                           x-model="pago.monto"
                                           x-on:input="pago._autoMonto = false"
                                           x-on:keydown.enter.prevent="
                                               if (index === pagos.length - 1) {
                                                   addPagoAndFocus();
                                               } else {
                                                   focusPagoField(index + 1, 'forma_pago');
                                               }
                                           "
                                           :data-pago-row="index"
                                           data-pago-field="monto"
                                           class="w-full pl-7 pr-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                                </div>
                                <button type="button" x-on:click="removePago(index)" x-show="pagos.length > 1" class="col-span-1 p-2 text-gray-300 hover:text-red-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            {{-- Datos del cheque recibido --}}
                            <template x-if="pago.forma_pago === 'cheque'">
                                <div class="mt-1 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                    <p class="text-xs font-semibold text-blue-700 mb-2">Datos del cheque recibido</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-0.5">Banco *</label>
                                            <select x-model.number="pago.cheque.banco_id"
                                                    class="w-full px-2 py-1.5 text-xs border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none">
                                                <option value="">Seleccionar...</option>
                                                @foreach($bancos as $banco)
                                                    <option value="{{ $banco->id }}">{{ $banco->descripcion }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-0.5">Serie</label>
                                            <input type="text" x-model="pago.cheque.serie" maxlength="10"
                                                   class="w-full px-2 py-1.5 text-xs border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none"
                                                   placeholder="0001">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-0.5">Nº cheque *</label>
                                            <input type="text" x-model="pago.cheque.nro" maxlength="30"
                                                   class="w-full px-2 py-1.5 text-xs border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none"
                                                   placeholder="00001234">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-0.5">CUIT librador</label>
                                            <input type="text" x-model="pago.cheque.librador_cuit" maxlength="13"
                                                   class="w-full px-2 py-1.5 text-xs border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none"
                                                   placeholder="20-12345678-9">
                                        </div>
                                        <div class="col-span-2">
                                            <label class="block text-xs text-gray-500 mb-0.5">Librador *</label>
                                            <input type="text" x-model="pago.cheque.librador_nombre" maxlength="150"
                                                   class="w-full px-2 py-1.5 text-xs border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none"
                                                   placeholder="Nombre del librador">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-0.5">Fecha emisión</label>
                                            <input type="date" x-model="pago.cheque.fecha_emision"
                                                   class="w-full px-2 py-1.5 text-xs border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-0.5">Fecha cobro *</label>
                                            <input type="date" x-model="pago.cheque.fecha_pago"
                                                   class="w-full px-2 py-1.5 text-xs border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none">
                                        </div>
                                        <div class="col-span-2">
                                            <label class="inline-flex items-center gap-2 text-xs text-gray-500">
                                                <input type="checkbox" x-model="pago.cheque.es_echeq"
                                                       class="rounded border-gray-300 text-slate-900 focus:ring-slate-900">
                                                Es e-Cheq (electrónico)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            </div>
                        </template>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 text-xs space-y-1">
                        <div class="flex justify-between text-gray-500">
                            <span>Total a pagar</span>
                            <span>$<span x-text="formatNum(total)"></span></span>
                        </div>
                        <div class="flex justify-between" x-bind:class="saldoPagos === 0 ? 'text-green-600' : 'text-amber-600'">
                            <span>Saldo pendiente</span>
                            <span>$<span x-text="formatNum(saldoPagos)"></span></span>
                        </div>
                        @error('pagos')
                            <p class="text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                @else
                {{-- Modo lectura: mostrar pagos registrados --}}
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Pagos registrados</h3>
                    @php
                        $formasPagoLabels = [
                            'efectivo' => 'Efectivo',
                            'transferencia' => 'Transferencia',
                            'tarjeta' => 'Tarjeta',
                            'cheque' => 'Cheque',
                            'cuenta_corriente' => 'Cta. Cte.',
                            'otro' => 'Otro',
                        ];
                    @endphp
                    @if(count($pagosVenta) > 0)
                        <div class="space-y-2">
                            @foreach($pagosVenta as $pago)
                                <div class="flex items-center justify-between text-sm border border-gray-100 rounded-lg px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-700 rounded">{{ $formasPagoLabels[$pago['forma_pago']] ?? $pago['forma_pago'] }}</span>
                                        @if(!empty($pago['fecha']))
                                            <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($pago['fecha'])->format('d/m/Y') }}</span>
                                        @endif
                                    </div>
                                    <span class="font-medium text-gray-900">${{ number_format($pago['monto'], 2, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400">Sin pagos registrados.</p>
                    @endif

                    <div class="mt-4 pt-3 border-t border-gray-100 space-y-1.5 text-sm">
                        <div class="flex justify-between text-gray-500">
                            <span>Total venta</span>
                            <span>${{ number_format($ventaInfo['total'] ?? 0, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <span>Pagado</span>
                            <span>${{ number_format($ventaInfo['pagado'] ?? 0, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between font-medium {{ ($ventaInfo['saldo'] ?? 0) > 0 ? 'text-amber-600' : 'text-green-600' }}">
                            <span>{{ ($ventaInfo['saldo'] ?? 0) > 0 ? 'Saldo pendiente' : 'Cancelada' }}</span>
                            <span>${{ number_format($ventaInfo['saldo'] ?? 0, 2, ',', '.') }}</span>
                        </div>
                        @if(($ventaInfo['estado'] ?? null) === 'P')
                            <p class="mt-1 text-xs text-amber-600">Esta venta tiene saldo pendiente en cuenta corriente.</p>
                        @endif
                    </div>
                </div>
                @endif

                <div class="bg-white rounded-xl shadow-sm p-5">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Observación</label>
                    <textarea wire:model="observacion" rows="2" maxlength="300"
                              {{ $readOnly ? 'readonly' : '' }}
                              class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none resize-none"
                              placeholder="Notas internas..."></textarea>
                </div>
            </div>

            {{-- Totales --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Totales</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between" x-show="neto21 > 0">
                        <span class="text-gray-500">Neto gravado 21%</span>
                        <span x-text="'$' + formatNum(neto21)"></span>
                    </div>
                    <div class="flex justify-between" x-show="iva21 > 0">
                        <span class="text-gray-500">IVA 21%</span>
                        <span x-text="'$' + formatNum(iva21)"></span>
                    </div>
                    <div class="flex justify-between" x-show="neto105 > 0">
                        <span class="text-gray-500">Neto gravado 10,5%</span>
                        <span x-text="'$' + formatNum(neto105)"></span>
                    </div>
                    <div class="flex justify-between" x-show="iva105 > 0">
                        <span class="text-gray-500">IVA 10,5%</span>
                        <span x-text="'$' + formatNum(iva105)"></span>
                    </div>
                    <div class="flex justify-between" x-show="netoExento > 0">
                        <span class="text-gray-500">Exento / sin IVA</span>
                        <span x-text="'$' + formatNum(netoExento)"></span>
                    </div>
                    <div class="flex justify-between" x-show="percepIIBB > 0">
                        <span class="text-gray-500">Percep. IIBB</span>
                        <span x-text="'$' + formatNum(percepIIBB)"></span>
                    </div>
                    <div class="flex justify-between" x-show="percepIVA > 0">
                        <span class="text-gray-500">Percep. IVA</span>
                        <span x-text="'$' + formatNum(percepIVA)"></span>
                    </div>

                    @if(! $readOnly)
                    <div class="flex items-center justify-between pt-2 border-t border-[#DDE1E7]">
                        <label class="text-gray-500 text-xs">Desc. general %</label>
                        <input x-model.number="descuentoGeneral"
                               type="number" min="0" max="100" step="0.01"
                               class="w-20 text-right px-2 py-1 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    </div>
                    @endif

                    <div class="flex justify-between items-center pt-3 border-t border-[#DDE1E7]">
                        <span class="font-bold text-gray-900 text-base">TOTAL</span>
                        <span class="font-bold text-gray-900 text-xl" x-text="'$' + formatNum(total)"></span>
                    </div>
                </div>

                @if(! $readOnly)
                <button
                    x-on:click="guardar($wire)"
                    :disabled="{{ $sinCaja ? 'true' : 'false' }}"
                    title="Registrar venta (F10)"
                    class="mt-5 w-full px-4 py-2.5 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Registrar venta <span class="opacity-60 text-xs font-normal">F10</span>
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function ventaForm(config) {
    return {
        items: config.items.map(item => {
            const _verNetos = localStorage.getItem('ventas.verNetos') === '1';
            const neto = parseFloat(item.precio) || 0;
            const iva  = parseFloat(item.alicuota_iva) || 0;
            return {
                ...item,
                _search: '',
                _open: false,
                _highlight: 0,
                _dropdownStyle: '',
                _editingPrecio: false,
                _precioDisplay: _verNetos ? neto : Math.round(neto * (1 + iva / 100) * 100) / 100,
                precio_manual: !!item.precio_manual,
            };
        }),
        clienteData: config.clienteData,
        descuentoGeneral: config.descuentoGeneral,
        articulosData: config.articulosData || [],
        pagos: [{ forma_pago: 'efectivo', monto: '', _autoMonto: true, cheque: null }],
        wire: null,

        // Toggle de visualizacion de precios: false = ver finales (con IVA),
        // true = ver netos. El BACKEND siempre trabaja en NETO; este toggle
        // solo afecta la presentacion y la edicion manual del precio.
        verNetos: localStorage.getItem('ventas.verNetos') === '1',

        init() {
            this.items.forEach(item => {
                if (item.articulo_id) {
                    const art = this.articulosData.find(a => a.id == item.articulo_id);
                    if (art) item._search = art.codigo + ' — ' + art.descripcion;
                }
            });
            this.$watch('total', val => {
                if (this.pagos.length === 1 && this.pagos[0]._autoMonto) {
                    this.pagos[0].monto = val > 0 ? val : '';
                }
            });

            // Cuando el backend recotiza items en respuesta a un cambio de cliente
            // (updatedClienteId), no hay accion Alpine explicita que dispare syncItems.
            // El backend nos avisa con dispatch('items-actualizados').
            this.$wire.on('items-actualizados', () => {
                this.syncItems();
            });
        },

        openDropdown(index, inputEl) {
            const rect = inputEl.getBoundingClientRect();
            this.items[index]._dropdownStyle = `top:${rect.bottom + 2}px;left:${rect.left}px;width:${rect.width}px`;
            this.items[index]._open = true;
            this.items[index]._highlight = 0;
        },

        filteredArticulos(item) {
            const q = (item._search || '').toLowerCase().trim();
            const data = q
                ? this.articulosData.filter(a =>
                    a.codigo.toLowerCase().includes(q) ||
                    a.descripcion.toLowerCase().includes(q))
                : this.articulosData;
            return data.slice(0, 50);
        },

        async seleccionarCombo(index, art) {
            // Pre-llenado optimista para UX inmediata
            this.items[index].articulo_id = art.id;
            this.items[index].descripcion = art.descripcion;
            this.items[index].unidad_medida = art.unidad_medida;
            this.items[index].alicuota_iva = art.alicuota_iva;
            this.items[index]._search = art.codigo + ' — ' + art.descripcion;
            this.items[index]._open = false;
            this.items[index]._highlight = 0;

            // El backend resuelve precio + valida duplicados (sumar cantidades).
            // Pasamos la cantidad explicitamente porque el estado Livewire de items
            // no se sincroniza automaticamente con Alpine.
            const cantidad = parseFloat(this.items[index].cantidad) || 1;
            await this.$wire.seleccionarArticulo(index, art.id, cantidad);
            this.syncItems();
            this.focusField(index, 'cantidad');
        },

        focusField(rowIndex, field) {
            this.$nextTick(() => {
                const el = document.querySelector(`[data-row="${rowIndex}"][data-field="${field}"]`);
                if (el) { el.focus(); if (el.select) el.select(); }
            });
        },

        focusPagoField(rowIndex, field) {
            this.$nextTick(() => {
                const el = document.querySelector(`[data-pago-row="${rowIndex}"][data-pago-field="${field}"]`);
                if (el) { el.focus(); if (el.select) el.select(); }
            });
        },

        addPagoAndFocus() {
            this.addPago();
            const newIndex = this.pagos.length - 1;
            this.$nextTick(() => {
                const el = document.querySelector(`[data-pago-row="${newIndex}"][data-pago-field="forma_pago"]`);
                if (el) el.focus();
            });
        },

        async addItemAndFocus() {
            await this.addItem();
            const newIndex = this.items.length - 1;
            this.$nextTick(() => {
                const el = document.querySelector(`[data-row="${newIndex}"][data-field="articulo"]`);
                if (el) el.focus();
            });
        },

        // ── Cotizacion via backend (PriceEngine) ─────────────────────────
        // El precio NO se calcula en JS: cada cambio dispara cotizarItem
        // en el componente Livewire, que aplica lista del cliente, overrides
        // por rubro, precios especiales y bonificaciones.

        async cotizarFila(index) {
            const item = this.items[index];
            if (!item.articulo_id) return;
            // Pasamos la cantidad explicita porque el estado Livewire de items
            // no se sincroniza automaticamente con Alpine.
            const cantidad = parseFloat(item.cantidad) || 0;
            if (cantidad <= 0) return;
            await this.$wire.actualizarCantidad(index, cantidad);
            this.syncItems();
        },

        async marcarPrecioManual(index) {
            const item = this.items[index];
            const precio = parseFloat(item.precio || 0);
            await this.$wire.marcarPrecioManual(index, precio);
            this.syncItems();
        },

        subtotalMostrado(item) {
            if (this.verNetos) return parseFloat(item.subtotal) || 0;
            // Multiplicar desde el precio final ya redondeado evita acumular
            // el error de redondeo de la conversion neto→final en el subtotal.
            const precio   = parseFloat(item._precioDisplay) || 0;
            const cantidad = parseFloat(item.cantidad) || 0;
            const desc     = parseFloat(item.descuento) || 0;
            return Math.round(precio * cantidad * (1 - desc / 100) * 100) / 100;
        },

        onPrecioFocus(index) {
            this.items[index]._editingPrecio = true;
        },

        onPrecioBlur(index) {
            const item = this.items[index];
            item._editingPrecio = false;
            // Recompute _precioDisplay from the stored neto so x-effect updates the input.
            const neto = parseFloat(item.precio) || 0;
            const iva  = parseFloat(item.alicuota_iva) || 0;
            item._precioDisplay = this.verNetos ? neto : Math.round(neto * (1 + iva / 100) * 100) / 100;
        },

        // El usuario edito el precio en el input. Si esta viendo finales,
        // convertimos a neto antes de pasarlo al backend.
        async onPrecioInput(valor, index) {
            const item = this.items[index];
            const iva = parseFloat(item.alicuota_iva) || 0;
            const valorNum = parseFloat(valor) || 0;
            const neto = this.verNetos
                ? valorNum
                : (iva > 0 ? Math.round((valorNum / (1 + iva / 100)) * 100) / 100 : valorNum);
            item.precio = neto;
            item._precioDisplay = valorNum; // preservar lo tipeado mientras edita
            await this.$wire.marcarPrecioManual(index, neto);
            this.syncItems();
        },

        toggleVerNetos() {
            this.verNetos = !this.verNetos;
            localStorage.setItem('ventas.verNetos', this.verNetos ? '1' : '0');
            this.items.forEach(item => {
                if (!item._editingPrecio) {
                    const neto = parseFloat(item.precio) || 0;
                    const iva  = parseFloat(item.alicuota_iva) || 0;
                    item._precioDisplay = this.verNetos ? neto : Math.round(neto * (1 + iva / 100) * 100) / 100;
                }
            });
        },

        async restaurarPrecio(index) {
            await this.$wire.restaurarPrecio(index);
            this.syncItems();
        },

        recalcularItem(index) {
            // Solo descuento por linea: el subtotal se recalcula en cliente
            // a partir del precio del motor + el descuento manual.
            const item = this.items[index];
            const subtotalNeto = parseFloat(item.cantidad) * parseFloat(item.precio)
                * (1 - parseFloat(item.descuento || 0) / 100);
            item.subtotal = Math.round(subtotalNeto * 100) / 100;
        },

        // Sincroniza el array Alpine con lo que devolvio Livewire.
        // Conserva las claves locales (_search, _open, etc) que Livewire no maneja.
        syncItems() {
            const wireItems = this.$wire.items || [];
            const merged = wireItems.map((wireItem, idx) => {
                const local = this.items[idx] || {};
                const neto = parseFloat(wireItem.precio) || 0;
                const iva  = parseFloat(wireItem.alicuota_iva) || 0;
                // Preserve what the user typed while editing; otherwise recompute from neto.
                const _precioDisplay = local._editingPrecio
                    ? (local._precioDisplay !== undefined ? local._precioDisplay : neto)
                    : (this.verNetos ? neto : Math.round(neto * (1 + iva / 100) * 100) / 100);
                return {
                    ...wireItem,
                    _search: local._search || (wireItem.descripcion || ''),
                    _open: false,
                    _highlight: 0,
                    _dropdownStyle: '',
                    _editingPrecio: !!local._editingPrecio,
                    _precioDisplay,
                    precio_manual: !!wireItem.precio_manual,
                };
            });
            this.items = merged;
        },

        // CRITICO: addItem debe agregar la fila TANTO en Alpine como en Livewire.
        // Si solo se agrega en Alpine, el siguiente seleccionarArticulo (que valida
        // por indice contra $items en backend) no encuentra la fila y se descarta
        // silenciosamente, y luego syncItems colapsa el array Alpine al estado
        // backend, haciendo "desaparecer" la fila recien agregada.
        async addItem() {
            await this.$wire.addItem();
            this.syncItems();
        },

        async removeItem(index) {
            if (this.items.length > 1) {
                await this.$wire.removeItem(index);
                this.syncItems();
            }
        },

        initCheque(pago) {
            if (pago.forma_pago === 'cheque' && !pago.cheque) {
                const today = new Date().toISOString().slice(0,10);
                const d30 = new Date(Date.now() + 30*86400000).toISOString().slice(0,10);
                pago.cheque = { banco_id: '', serie: '', nro: '', librador_nombre: '', librador_cuit: '', fecha_emision: today, fecha_pago: d30, es_echeq: false };
            }
        },
        addPago() {
            this.pagos.forEach(p => p._autoMonto = false);
            this.pagos.push({ forma_pago: 'efectivo', monto: '', _autoMonto: false, cheque: null });
        },

        removePago(index) {
            if (this.pagos.length > 1) {
                this.pagos.splice(index, 1);
            }
        },

        // Neto desde el backend (siempre en neto): base fiscal y para percepciones.
        get neto21() {
            return this.items
                .filter(i => parseFloat(i.alicuota_iva) === 21)
                .reduce((s, i) => s + parseFloat(i.subtotal || 0), 0);
        },
        get neto105() {
            return this.items
                .filter(i => parseFloat(i.alicuota_iva) === 10.5)
                .reduce((s, i) => s + parseFloat(i.subtotal || 0), 0);
        },
        get netoExento() {
            return this.items
                .filter(i => parseFloat(i.alicuota_iva) === 0)
                .reduce((s, i) => s + parseFloat(i.subtotal || 0), 0);
        },
        // Cuando se muestran finales, IVA = finalSubtotal - netoSubtotal.
        // Esto evita que neto*tasa produzca centavos de drift frente al subtotal mostrado.
        get iva21() {
            if (this.verNetos) return Math.round(this.neto21 * 0.21 * 100) / 100;
            const finalBruto21 = this.items
                .filter(i => parseFloat(i.alicuota_iva) === 21)
                .reduce((s, i) => s + this.subtotalMostrado(i), 0);
            return Math.round((finalBruto21 - this.neto21) * 100) / 100;
        },
        get iva105() {
            if (this.verNetos) return Math.round(this.neto105 * 0.105 * 100) / 100;
            const finalBruto105 = this.items
                .filter(i => parseFloat(i.alicuota_iva) === 10.5)
                .reduce((s, i) => s + this.subtotalMostrado(i), 0);
            return Math.round((finalBruto105 - this.neto105) * 100) / 100;
        },
        get subtotalBruto() {
            if (this.verNetos) return this.neto21 + this.neto105 + this.netoExento + this.iva21 + this.iva105;
            return this.items.reduce((s, i) => s + this.subtotalMostrado(i), 0);
        },
        get descuentoMonto() {
            return Math.round(this.subtotalBruto * (parseFloat(this.descuentoGeneral || 0) / 100) * 100) / 100;
        },
        get percepIIBB() {
            const pct = parseFloat(this.clienteData.porcentaje_iibb || 0) / 100;
            return pct > 0 ? Math.round((this.neto21 + this.neto105) * pct * 100) / 100 : 0;
        },
        get percepIVA() {
            if (!this.clienteData.percepcion_iva) return 0;
            return Math.round(this.neto21 * 0.21 * 0.5 * 100) / 100;
        },
        get total() {
            return Math.round((this.subtotalBruto - this.descuentoMonto + this.percepIIBB + this.percepIVA) * 100) / 100;
        },
        get totalPagos() {
            return Math.round(this.pagos.reduce((s, p) => s + parseFloat(p.monto || 0), 0) * 100) / 100;
        },
        get saldoPagos() {
            return Math.max(Math.round((this.total - this.totalPagos) * 100) / 100, 0);
        },

        formatNum(val) {
            return parseFloat(val || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        guardar($wire) {
            const plainItems = JSON.parse(JSON.stringify(this.items)).map(item => {
                delete item._search;
                delete item._open;
                delete item._highlight;
                delete item._dropdownStyle;
                delete item._editingPrecio;
                delete item._precioDisplay;
                // precio_manual SI se conserva: el backend lo usa para no pisar
                // el override del usuario en la validacion dura.
                return item;
            });
            const plainClienteData = JSON.parse(JSON.stringify(this.clienteData));
            const plainPagos = JSON.parse(JSON.stringify(this.pagos)).map(p => { delete p._autoMonto; return p; });

            const payload = {
                items: plainItems,
                clienteData: plainClienteData,
                pagos: plainPagos,
                descuento_general: this.descuentoGeneral,
                neto_21:     this.neto21,
                neto_10_5:   this.neto105,
                iva_21:      this.iva21,
                iva_10_5:    this.iva105,
                percepcion:  this.percepIIBB,
                percepiva21: this.percepIVA,
                percepiva10_5: 0,
                total:       this.total,
            };

            $wire.registrarVenta(payload);
        },
    };
}
</script>
</div>
