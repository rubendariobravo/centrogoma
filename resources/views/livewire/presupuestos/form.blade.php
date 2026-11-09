<div
    x-data
    x-on:keydown.window="
        if (event.key === 'F10') {
            event.preventDefault();
            $wire.save();
        }
    "
>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('presupuestos') }}" wire:navigate class="hover:text-gray-700">Presupuestos</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-900">{{ $presupuestoId ? 'Presupuesto #'.str_pad($presupuestoId, 6, '0', STR_PAD_LEFT) : 'Nuevo presupuesto' }}</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900">
                {{ $presupuestoId ? 'Editar presupuesto' : ($duplicadoDe ? 'Nuevo presupuesto (copia del #'.str_pad($duplicadoDe, 6, '0', STR_PAD_LEFT).')' : 'Nuevo presupuesto') }}
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('presupuestos') }}" wire:navigate
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-[#DDE1E7] rounded-lg hover:bg-[#E9ECF0] transition-colors">
                Cancelar
            </a>
            <button wire:click="save" wire:loading.attr="disabled"
                    title="Guardar (F10)"
                    class="px-5 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-700 transition-colors disabled:opacity-50">
                <span wire:loading.remove>Guardar <span class="ml-1 opacity-60 text-xs font-normal">F10</span></span>
                <span wire:loading>Guardando...</span>
            </button>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('error'))
    <div class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
        {{ session('error') }}
    </div>
    @endif

    @if($duplicadoDe)
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 bg-sky-50 border border-sky-200 text-sky-800 text-sm rounded-lg px-4 py-3">
        <span>Copia del <strong>Presupuesto #{{ str_pad($duplicadoDe, 6, '0', STR_PAD_LEFT) }}</strong> con sus precios. Ajustá lo que haga falta: se graba como presupuesto nuevo al guardar.</span>
        <button type="button" wire:click="actualizarPreciosALista" wire:loading.attr="disabled"
                class="px-3 py-1.5 text-xs font-medium text-sky-800 bg-white border border-sky-300 rounded-md hover:bg-sky-100">
            Actualizar precios a la lista de hoy
        </button>
    </div>
    @endif

    <div class="space-y-4">

        {{-- Encabezado del presupuesto --}}
        <div class="bg-white rounded-xl shadow-sm p-5">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Cliente</label>
                    <x-buscador-cliente vacio="Buscar cliente por nombre o CUIT…" error="cliente_id" />
                    @if(! $cliente_id)
                    <input wire:model="cliente_nombre" type="text" maxlength="150"
                           placeholder="Sin cliente registrado: nombre para el presupuesto (opcional)"
                           class="mt-2 w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    @endif
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Fecha</label>
                    <input wire:model="fecha" type="date"
                           class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none @error('fecha') border-red-400 @enderror">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Validez (días)</label>
                    <input wire:model="dias_validez" type="number" min="1" max="365"
                           class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none @error('dias_validez') border-red-400 @enderror">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Lista de precios</label>
                    <select wire:model.live="lista_precio_id"
                            class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                        <option value="">Sin lista</option>
                        @foreach($listasPrecios as $lp)
                            <option value="{{ $lp->id }}">{{ $lp->nombre }}{{ $lp->es_default ? ' (default)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Condición de pago</label>
                    <input wire:model="condicion_pago" type="text" maxlength="100"
                           placeholder="Ej: 30 días"
                           class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Plazo de entrega</label>
                    <input wire:model="plazo_entrega" type="text" maxlength="100"
                           placeholder="Ej: 5 días hábiles"
                           class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
            </div>

            {{-- Cómo se cotiza (Delphi: "Incluye IVA" / "Sin leyenda") --}}
            <div class="mt-3 pt-3 border-t border-gray-50 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                <div class="flex rounded-lg overflow-hidden border border-[#DDE1E7] text-xs">
                    <button type="button" wire:click="cambiarModoIva(true)"
                            class="px-3 py-1.5 {{ $precios_con_iva ? 'bg-slate-900 text-white' : 'text-gray-600 hover:bg-gray-50' }}">Precios con IVA</button>
                    <button type="button" wire:click="cambiarModoIva(false)"
                            class="px-3 py-1.5 border-l border-[#DDE1E7] {{ ! $precios_con_iva ? 'bg-slate-900 text-white' : 'text-gray-600 hover:bg-gray-50' }}">Precios sin IVA (+ IVA)</button>
                </div>
                <label class="inline-flex items-center gap-2 text-xs text-gray-600">
                    <input type="checkbox" wire:model.live="leyenda_iva" class="rounded border-gray-300">
                    Imprimir la leyenda "{{ $precios_con_iva ? 'Los precios incluyen IVA' : 'Los precios no incluyen IVA' }}"
                </label>
            </div>

            @if(! empty($clienteData['razon_social']))
            <div class="mt-3 pt-3 border-t border-gray-50 flex items-center gap-4 text-xs text-gray-500">
                <span class="font-medium text-gray-700">{{ $clienteData['razon_social'] }}</span>
                @if(! empty($clienteData['es_mayorista']))
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full">Mayorista</span>
                @endif
                @if(! empty($clienteData['bonificacion']) && $clienteData['bonificacion'] > 0)
                    <span>Bonif: {{ number_format($clienteData['bonificacion'], 1) }}%</span>
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
                            <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide w-32">{{ $precios_con_iva ? 'Precio final' : 'Precio s/IVA' }}</th>
                            <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-16">IVA</th>
                            <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-20">Desc.%</th>
                            <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide w-32">Subtotal neto</th>
                            <th class="px-3 py-2.5 w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $index => $item)
                        <tr class="border-b border-gray-50">
                            <td class="px-3 py-2 text-xs text-gray-400 text-center">{{ $index + 1 }}</td>
                            <td class="px-3 py-2">
                                <div x-data="articuloSearch({{ $index }}, @js($item['_search'] ?? ''), {{ empty($item['articulo_id']) ? 'false' : 'true' }})"
                                     x-on:click.outside="open = false">
                                    <input type="text"
                                           data-row="{{ $index }}"
                                           data-field="articulo"
                                           x-model="search"
                                           x-on:focus="onFocus()"
                                           x-on:input.debounce.250ms="buscar()"
                                           x-on:keydown.enter.prevent="seleccionarHighlight()"
                                           x-on:keydown.arrow-down.prevent="highlight = Math.min(highlight + 1, resultados.length - 1)"
                                           x-on:keydown.arrow-up.prevent="highlight = Math.max(highlight - 1, 0)"
                                           x-on:keydown.escape="open = false"
                                           placeholder="Buscar artículo..."
                                           autocomplete="off"
                                           class="w-full px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                                    <div x-show="open && resultados.length > 0"
                                         class="absolute z-50 mt-0.5 bg-white border border-[#DDE1E7] rounded-md shadow-lg max-h-52 overflow-y-auto min-w-[280px]">
                                        <template x-for="(art, artIdx) in resultados" :key="art.id">
                                            <div x-on:click="seleccionar(art)"
                                                 x-on:mouseenter="highlight = artIdx"
                                                 :class="artIdx === highlight ? 'bg-slate-100 text-slate-900' : 'hover:bg-gray-50 text-gray-700'"
                                                 class="px-3 py-2 text-sm cursor-pointer flex items-center gap-2">
                                                <span class="font-mono text-xs text-gray-400 shrink-0" x-text="art.codigo"></span>
                                                <span class="truncate" x-text="art.descripcion"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                @error('items.'.$index.'.articulo_id')
                                    <p class="mt-1 text-xs text-red-600 px-2">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-3 py-2">
                                <input wire:model.live="items.{{ $index }}.cantidad"
                                       data-row="{{ $index }}"
                                       data-field="cantidad"
                                       type="number" min="0.001" step="0.001"
                                       class="w-full text-center px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                            </td>
                            <td class="px-3 py-2 text-center text-xs text-gray-500">{{ $item['unidad_medida'] }}</td>
                            <td class="px-3 py-2">
                                <input wire:model.live="items.{{ $index }}.precio"
                                       data-row="{{ $index }}"
                                       data-field="precio"
                                       type="number" min="0" step="0.01"
                                       class="w-full text-right px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                            </td>
                            <td class="px-3 py-2 text-center text-xs text-gray-500">{{ number_format((float)$item['alicuota_iva'], 1) }}%</td>
                            <td class="px-3 py-2">
                                <input wire:model.live="items.{{ $index }}.descuento"
                                       data-row="{{ $index }}"
                                       data-field="descuento"
                                       x-on:keydown.enter.prevent="enterEnDescuentoPresupuesto($el, {{ $index }}, {{ count($items) }}, {{ empty($item['articulo_id']) ? 'false' : 'true' }}, $wire)"
                                       type="number" min="0" max="100" step="0.01"
                                       class="w-full text-center px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                            </td>
                            <td class="px-3 py-2 text-right font-medium text-gray-900">
                                ${{ number_format((float)$item['subtotal'], 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if(count($items) > 1)
                                <button wire:click="removeItem({{ $index }})"
                                        class="p-1 text-gray-300 hover:text-red-500 transition-colors rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-50 flex items-center justify-between">
                <button wire:click="addItem"
                        class="inline-flex items-center gap-1.5 text-sm text-slate-600 hover:text-slate-900 font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Agregar ítem
                </button>
                @error('items')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Panel inferior: observación + totales --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Observación</label>
                    <textarea wire:model="observacion" rows="3" maxlength="500"
                              class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none resize-none"
                              placeholder="Notas, condiciones especiales..."></textarea>
                </div>
            </div>

            {{-- Totales --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Totales</h3>
                <div class="space-y-2 text-sm">
                    @if($neto_21 > 0)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Neto gravado 21%</span>
                        <span>${{ number_format($neto_21, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">IVA 21%</span>
                        <span>${{ number_format($iva21, 2, ',', '.') }}</span>
                    </div>
                    @endif
                    @if($neto_10_5 > 0)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Neto gravado 10,5%</span>
                        <span>${{ number_format($neto_10_5, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">IVA 10,5%</span>
                        <span>${{ number_format($iva10_5, 2, ',', '.') }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between items-center pt-3 border-t border-[#DDE1E7]">
                        <span class="font-bold text-gray-900 text-base">TOTAL</span>
                        <span class="font-bold text-gray-900 text-xl">${{ number_format($total, 2, ',', '.') }}</span>
                    </div>
                </div>

                <button wire:click="save" wire:loading.attr="disabled" data-enter-guardar
                        class="mt-5 w-full px-4 py-2.5 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-700 transition-colors disabled:opacity-50 focus:outline-none focus:ring-4 focus:ring-emerald-400 focus:ring-offset-2">
                    <span wire:loading.remove>Guardar presupuesto</span>
                    <span wire:loading>Guardando...</span>
                </button>
            </div>
        </div>

    </div>
</div>

<script>
/**
 * Enter sobre el descuento (cierra el renglón): siguiente renglón; en el último, uno nuevo si este
 * tiene artículo, y si está vacío, al botón Guardar.
 */
function enterEnDescuentoPresupuesto(el, index, cantidad, tieneArticulo, $wire) {
    const articulo = (i) => document.querySelector(`[data-row="${i}"][data-field="articulo"]`);
    if (index < cantidad - 1) {
        articulo(index + 1)?.focus();
    } else if (tieneArticulo) {
        $wire.addItem().then(() => setTimeout(() => articulo(index + 1)?.focus(), 50));
    } else {
        window.enterGuardar?.(el);
    }
}

function articuloSearch(rowIndex, initialSearch, tieneArticulo = false) {
    return {
        rowIndex: rowIndex,
        tieneArticulo: tieneArticulo,
        search: initialSearch || '',
        resultados: [],
        open: false,
        highlight: 0,

        // Un renglón ya cargado no abre la lista al entrar: un Enter de paso lo volvería a elegir
        // y pisaría el precio pactado. Se abre al tipear.
        onFocus() {
            if (! this.tieneArticulo && this.search.length >= 2) this.buscar();
        },

        async buscar() {
            if (this.search.length < 2) { this.resultados = []; this.open = false; return; }
            const res = await this.$wire.buscarArticulos(this.search);
            this.resultados = res || [];
            this.open = this.resultados.length > 0;
            this.highlight = 0;
        },

        // Enter: elige el resaltado; en un renglón cargado pasa a la cantidad; con la fila en blanco,
        // se terminó de cargar y va al botón Guardar.
        seleccionarHighlight() {
            if (this.open && this.resultados.length > 0) {
                this.seleccionar(this.resultados[this.highlight]);
            } else if (this.tieneArticulo) {
                this.open = false;
                const el = document.querySelector(`[data-row="${this.rowIndex}"][data-field="cantidad"]`);
                if (el) { el.focus(); el.select?.(); }
            } else if (this.search.trim() === '') {
                window.enterGuardar?.(this.$el);
            }
        },

        async seleccionar(art) {
            this.search = art.codigo + ' — ' + art.descripcion;
            this.open = false;
            this.resultados = [];
            this.tieneArticulo = true;
            await this.$wire.seleccionarArticulo(this.rowIndex, art.id);
            this.$nextTick(() => {
                const el = document.querySelector(`[data-row="${this.rowIndex}"][data-field="cantidad"]`);
                if (el) { el.focus(); if (el.select) el.select(); }
            });
        },
    };
}
</script>
