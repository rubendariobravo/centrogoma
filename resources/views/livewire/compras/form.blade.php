<div>
<script>
window._articulosCompra = {{ Js::from($articulosData) }};
window._initialCompraItems = {{ Js::from($items ?: [['articulo_id'=>null,'descripcion'=>'','unidad_medida'=>'unidad','cantidad'=>1,'precio'=>0,'alicuota_iva'=>21,'subtotal'=>0]]) }};
</script>
<div
    x-data="compraForm({
        items: window._initialCompraItems,
        articulosData: window._articulosCompra,
    })"
>
    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg px-4 py-3">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('compras') }}" wire:navigate class="hover:text-gray-700">Compras</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-900">{{ $compraId ? 'Compra #'.$compraId : 'Nueva compra' }}</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900">
                @if($readOnly) Compra #{{ $compraId }} @else Nueva compra @endif
            </h1>
        </div>
        @if(! $readOnly)
        <div class="flex items-center gap-3">
            <a href="{{ route('compras') }}" wire:navigate
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-[#DDE1E7] rounded-lg hover:bg-[#E9ECF0] transition-colors">
                Cancelar
            </a>
            <button type="button" x-on:click="guardar($wire)"
                    class="px-5 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-700 transition-colors">
                Registrar compra
            </button>
        </div>
        @endif
    </div>

    {{-- Formulario --}}
    <div class="space-y-4">

        {{-- Encabezado del comprobante --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                {{-- Proveedor --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Proveedor</label>
                    <select wire:model="proveedor_id" {{ $readOnly ? 'disabled' : '' }}
                            class="w-full px-3 py-2 text-sm border {{ $errors->has('proveedor_id') ? 'border-red-400' : 'border-[#DDE1E7]' }} rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white {{ $readOnly ? 'bg-[#E9ECF0]' : '' }}">
                        <option value="">— Seleccionar —</option>
                        @foreach($proveedores as $p)
                            <option value="{{ $p->id }}">{{ $p->razon_social }}</option>
                        @endforeach
                    </select>
                    @error('proveedor_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                {{-- Fecha --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Fecha</label>
                    <input wire:model="fecha" type="date" {{ $readOnly ? 'readonly' : '' }}
                           class="w-full px-3 py-2 text-sm border {{ $errors->has('fecha') ? 'border-red-400' : 'border-[#DDE1E7]' }} rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $readOnly ? 'bg-[#E9ECF0]' : '' }}">
                </div>
                {{-- Letra + Nº Factura --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nº de factura</label>
                    <div class="flex gap-2">
                        <select wire:model="letra" {{ $readOnly ? 'disabled' : '' }}
                                class="w-16 px-2 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white font-bold {{ $readOnly ? 'bg-[#E9ECF0]' : '' }}">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="M">M</option>
                            <option value="X">X</option>
                        </select>
                        <input wire:model="nro_factura" type="text" placeholder="0001-00001234"
                               {{ $readOnly ? 'readonly' : '' }}
                               class="flex-1 px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $readOnly ? 'bg-[#E9ECF0]' : '' }}">
                    </div>
                </div>
            </div>
            {{-- Forma de pago / Estado --}}
            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        {{ $readOnly ? 'Estado' : 'Forma de pago' }}
                    </label>
                    @if(! $readOnly)
                    <select wire:model.live="forma_pago"
                            class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                        @foreach($formasPago as $fp)
                            <option value="{{ $fp->codigo }}">{{ $fp->descripcion }}</option>
                        @endforeach
                    </select>
                    @error('forma_pago') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @else
                    <p class="px-3 py-2 text-sm text-gray-700">
                        @if($compraId)
                            @php $c = \App\Models\Compra::find($compraId); @endphp
                            {{ $c?->estado === 'C' ? 'Pagada' : ($c?->estado === 'A' ? 'Anulada' : 'Pendiente') }}
                        @endif
                    </p>
                    @endif
                </div>

                @if(! $readOnly && $this->esPagoContado())
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Caja de pago</label>
                    @if($cajas->isEmpty())
                        <p class="px-3 py-2 text-sm text-red-600 bg-red-50 rounded-lg">No hay cajas abiertas. Abra una caja o cargue la compra en cuenta corriente.</p>
                    @else
                        <select wire:model="cajaId"
                                class="w-full px-3 py-2 text-sm border {{ $errors->has('cajaId') ? 'border-red-400' : 'border-[#DDE1E7]' }} rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                            <option value="">— Seleccionar caja —</option>
                            @foreach($cajas as $caja)
                                <option value="{{ $caja->id }}">{{ $caja->nombreEtiqueta() }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-400">El total se descuenta de esta caja al registrar la compra.</p>
                    @endif
                    @error('cajaId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                @endif
            </div>
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
                            <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide w-28">P. costo</th>
                            <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-20">IVA</th>
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
                                           x-on:input="recalcularItem(index)"
                                           x-on:keydown.enter.prevent="focusField(index, 'precio')"
                                           :data-row="index"
                                           data-field="cantidad"
                                           type="number" step="0.001" min="0"
                                           {{ $readOnly ? 'readonly' : '' }}
                                           class="w-full text-center px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $readOnly ? 'bg-[#E9ECF0]' : '' }}">
                                </td>
                                <td class="px-3 py-2 text-center text-xs text-gray-500" x-text="item.unidad_medida"></td>
                                <td class="px-3 py-2">
                                    <input x-model.number="item.precio"
                                           x-on:input="recalcularItem(index)"
                                           x-on:keydown.enter.prevent="focusField(index, 'iva')"
                                           :data-row="index"
                                           data-field="precio"
                                           type="number" step="0.01" min="0"
                                           {{ $readOnly ? 'readonly' : '' }}
                                           class="w-full text-right px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $readOnly ? 'bg-[#E9ECF0]' : '' }}">
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @if(! $readOnly)
                                    <select x-model.number="item.alicuota_iva"
                                            x-on:change="recalcularItem(index)"
                                            x-on:keydown.enter.prevent="
                                                if (index === items.length - 1) {
                                                    addItemAndFocus();
                                                } else {
                                                    focusField(index + 1, 'articulo');
                                                }
                                            "
                                            :data-row="index"
                                            data-field="iva"
                                            class="text-sm border border-[#DDE1E7] rounded-md px-2 py-1.5 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                                        <option value="0">0%</option>
                                        <option value="10.5">10.5%</option>
                                        <option value="21">21%</option>
                                        <option value="27">27%</option>
                                    </select>
                                    @else
                                    <span class="text-gray-600 text-xs" x-text="item.alicuota_iva + '%'"></span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right font-medium text-gray-900">
                                    <span x-text="'$' + formatNum(item.subtotal)"></span>
                                </td>
                                @if(! $readOnly)
                                <td class="px-3 py-2 text-center">
                                    <button type="button" x-on:click="removeItem(index)"
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
                <button type="button" x-on:click="addItem()"
                        class="inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900 font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Agregar ítem
                </button>
            </div>
            @endif
        </div>

        {{-- Panel inferior: observación + totales --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- Observación --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Observación</label>
                    <textarea wire:model="observacion" rows="2" {{ $readOnly ? 'readonly' : '' }}
                              class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none resize-none {{ $readOnly ? 'bg-[#E9ECF0]' : '' }}"
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
                    <div class="flex justify-between items-center pt-3 border-t border-[#DDE1E7]">
                        <span class="font-bold text-gray-900 text-base">Total</span>
                        <span class="font-bold text-gray-900 text-xl" x-text="'$' + formatNum(total)"></span>
                    </div>
                </div>

                @if(! $readOnly)
                <button type="button" x-on:click="guardar($wire)"
                        class="mt-5 w-full px-4 py-2.5 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors">
                    Registrar compra
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function compraForm({ items, articulosData }) {
    return {
        items: items.map(item => ({
            ...item,
            _search: '',
            _open: false,
            _highlight: 0,
            _dropdownStyle: '',
        })),
        articulos: articulosData || [],

        init() {
            this.items.forEach(item => {
                if (item.articulo_id) {
                    const art = this.articulos.find(a => a.id == item.articulo_id);
                    if (art) item._search = art.codigo + ' — ' + art.descripcion;
                }
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
                ? this.articulos.filter(a =>
                    a.codigo.toLowerCase().includes(q) ||
                    a.descripcion.toLowerCase().includes(q))
                : this.articulos;
            return data.slice(0, 50);
        },

        seleccionarCombo(index, art) {
            this.items[index].articulo_id = art.id;
            this.items[index]._search = art.codigo + ' — ' + art.descripcion;
            this.items[index]._open = false;
            this.items[index]._highlight = 0;
            this.seleccionarArticulo(index, art.id);
            this.focusField(index, 'cantidad');
        },

        focusField(rowIndex, field) {
            this.$nextTick(() => {
                const el = document.querySelector(`[data-row="${rowIndex}"][data-field="${field}"]`);
                if (el) { el.focus(); if (el.select) el.select(); }
            });
        },

        addItemAndFocus() {
            this.addItem();
            const newIndex = this.items.length - 1;
            this.$nextTick(() => {
                const el = document.querySelector(`[data-row="${newIndex}"][data-field="articulo"]`);
                if (el) el.focus();
            });
        },

        addItem() {
            this.items.push({
                articulo_id: null,
                descripcion: '',
                unidad_medida: 'unidad',
                cantidad: 1,
                precio: 0,
                alicuota_iva: 21,
                subtotal: 0,
                _search: '',
                _open: false,
                _highlight: 0,
                _dropdownStyle: '',
            });
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },

        seleccionarArticulo(index, articuloId) {
            const art = this.articulos.find(a => a.id == articuloId);
            if (!art) return;
            this.items[index].descripcion   = art.descripcion;
            this.items[index].unidad_medida = art.unidad_medida;
            this.items[index].alicuota_iva  = art.alicuota_iva;
            this.items[index].precio        = art.precio_costo;
            this.recalcularItem(index);
        },

        recalcularItem(index) {
            const item = this.items[index];
            item.subtotal = Math.round(item.cantidad * item.precio * 100) / 100;
        },

        get neto21() {
            return this.items.reduce((sum, item) =>
                sum + (parseFloat(item.alicuota_iva) === 21 ? parseFloat(item.subtotal || 0) : 0), 0);
        },

        get neto105() {
            return this.items.reduce((sum, item) =>
                sum + (parseFloat(item.alicuota_iva) === 10.5 ? parseFloat(item.subtotal || 0) : 0), 0);
        },

        get netoExento() {
            return this.items.reduce((sum, item) =>
                sum + (parseFloat(item.alicuota_iva) === 0 ? parseFloat(item.subtotal || 0) : 0), 0);
        },

        get iva21() {
            return Math.round(this.neto21 * 0.21 * 100) / 100;
        },

        get iva105() {
            return Math.round(this.neto105 * 0.105 * 100) / 100;
        },

        get total() {
            return Math.round((this.neto21 + this.neto105 + this.netoExento + this.iva21 + this.iva105) * 100) / 100;
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
                return item;
            });
            $wire.save({
                items:       plainItems,
                neto_21:     this.neto21,
                neto_10_5:   this.neto105,
                neto_exento: this.netoExento,
                iva_21:      this.iva21,
                iva_10_5:    this.iva105,
                total:       this.total,
            });
        },
    };
}
</script>
</div>
