<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('ordenes-pago') }}" wire:navigate
               class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-md transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    {{ $this->ordenPagoId ? 'Ver orden de pago' : 'Nueva orden de pago' }}
                </h1>
                <p class="text-sm text-gray-500 mt-0.5">Registro de pago a proveedor</p>
            </div>
        </div>
    </div>

    {{-- Sin caja --}}
    @if($sinCaja && ! $readOnly)
    <div class="mb-4 flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg px-4 py-3">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        No hay una caja abierta. La orden de pago no se asociará a ninguna caja.
    </div>
    @endif

    @if($errors->has('detalles'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
        {{ $errors->first('detalles') }}
    </div>
    @endif

    @if($errors->has('proveedor_id') || $errors->has('fecha') || $errors->has('forma_pago'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($readOnly)
    {{-- MODO LECTURA --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="xl:col-span-2">
            @if(count($ordenDetalles) > 0)
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-[#DDE1E7]">
                    <h2 class="text-sm font-semibold text-gray-700">Facturas pagadas</h2>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0] text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            <th class="px-4 py-2 text-left">Nº Factura</th>
                            <th class="px-4 py-2 text-left">Fecha</th>
                            <th class="px-4 py-2 text-right">Total factura</th>
                            <th class="px-4 py-2 text-right">Monto pagado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E9ECF0]">
                        @foreach($ordenDetalles as $det)
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs text-gray-700">{{ $det['nro_factura'] }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ $det['fecha'] }}</td>
                            <td class="px-4 py-2 text-right text-gray-700">${{ number_format($det['total'], 2, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right font-medium text-amber-700">${{ number_format($det['monto'], 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        <div class="xl:col-span-1">
            <div class="bg-white rounded-xl shadow-sm p-4 space-y-3">
                <h2 class="text-sm font-semibold text-gray-700">Datos de la orden de pago</h2>
                <div class="grid grid-cols-2 gap-y-2 text-sm">
                    <span class="text-gray-500">Proveedor</span>
                    <span class="font-medium text-gray-900">{{ $proveedores->find($proveedor_id)?->razon_social ?? '—' }}</span>
                    <span class="text-gray-500">Fecha</span>
                    <span class="text-gray-900">{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</span>
                    <span class="text-gray-500">Forma de pago</span>
                    <span class="text-gray-900">{{ ucfirst($forma_pago) }}</span>
                    @if($referencia)
                    <span class="text-gray-500">Referencia</span>
                    <span class="text-gray-900">{{ $referencia }}</span>
                    @endif
                    @if($observacion)
                    <span class="text-gray-500">Observación</span>
                    <span class="text-gray-900">{{ $observacion }}</span>
                    @endif
                    <span class="text-gray-500 font-medium">Total pagado</span>
                    <span class="font-bold text-amber-700">${{ number_format($total, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    @else
    {{-- MODO CREACIÓN con Alpine.js --}}
    <div
        x-data="{
            compras: $wire.entangle('comprasPendientes'),
            checked: {},
            montos: {},
            init() {
                this.$watch('compras', () => {
                    this.checked = {};
                    this.montos  = {};
                });
            },
            get total() {
                return this.compras
                    .filter(c => this.checked[c.id])
                    .reduce((sum, c) => sum + (parseFloat(this.montos[c.id]) || 0), 0);
            },
            fmt(v) {
                const n = parseFloat(v || 0).toFixed(2);
                const parts = n.split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                return '$' + parts[0] + ',' + parts[1];
            },
            toggle(compra) {
                if (this.checked[compra.id]) {
                    delete this.checked[compra.id];
                    delete this.montos[compra.id];
                } else {
                    this.checked[compra.id] = true;
                    this.montos[compra.id]  = compra.saldo;
                }
            },
            setMonto(compraId, value, maxSaldo) {
                let v = parseFloat(value) || 0;
                if (v > maxSaldo) v = maxSaldo;
                if (v < 0)        v = 0;
                this.montos[compraId] = v;
            },
            selectAll() {
                this.compras.forEach(c => {
                    this.checked[c.id] = true;
                    this.montos[c.id]  = c.saldo;
                });
            },
            clearAll() {
                this.checked = {};
                this.montos  = {};
            },
            getDetalles() {
                return this.compras
                    .filter(c => this.checked[c.id] && parseFloat(this.montos[c.id]) > 0)
                    .map(c => ({ compra_id: c.id, monto_aplicar: parseFloat(this.montos[c.id]) }));
            }
        }"
        class="grid grid-cols-1 xl:grid-cols-3 gap-4"
    >

        {{-- Izquierda: facturas pendientes --}}
        <div class="xl:col-span-2">

            @if($proveedor_id)
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-[#DDE1E7] flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-700">Facturas pendientes de pago</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Seleccione las facturas y asigne el monto a pagar</p>
                    </div>
                    <div class="flex gap-2" x-show="compras.length > 0">
                        <button type="button" @click="selectAll()"
                                class="text-xs text-amber-700 hover:text-amber-900 underline">
                            Seleccionar todas
                        </button>
                        <span class="text-gray-300">|</span>
                        <button type="button" @click="clearAll()"
                                class="text-xs text-gray-500 hover:text-gray-700 underline">
                            Limpiar
                        </button>
                    </div>
                </div>

                {{-- Lista cargada --}}
                <div x-show="compras.length > 0">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0] text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                <th class="px-3 py-2 w-8"></th>
                                <th class="px-4 py-2 text-left">Nº Factura</th>
                                <th class="px-4 py-2 text-left">Fecha</th>
                                <th class="px-4 py-2 text-right">Total</th>
                                <th class="px-4 py-2 text-right">Saldo</th>
                                <th class="px-4 py-2 text-right">Monto a pagar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E9ECF0]">
                            <template x-for="compra in compras" :key="compra.id">
                                <tr :class="checked[compra.id] ? 'bg-amber-50' : 'hover:bg-[#E9ECF0]'"
                                    class="transition-colors">
                                    <td class="px-3 py-2 text-center">
                                        <input type="checkbox"
                                               :checked="checked[compra.id]"
                                               @change="toggle(compra)"
                                               class="w-4 h-4 rounded accent-amber-700 cursor-pointer">
                                    </td>
                                    <td class="px-4 py-2 font-mono text-xs text-gray-700"
                                        x-text="compra.letra + '-' + compra.nro_factura"></td>
                                    <td class="px-4 py-2 text-gray-600" x-text="compra.fecha"></td>
                                    <td class="px-4 py-2 text-right text-gray-700"
                                        x-text="fmt(compra.total)"></td>
                                    <td class="px-4 py-2 text-right font-medium text-amber-600"
                                        x-text="fmt(compra.saldo)"></td>
                                    <td class="px-4 py-2">
                                        <input type="number" step="0.01" min="0.01"
                                               :max="compra.saldo"
                                               :disabled="!checked[compra.id]"
                                               :value="montos[compra.id] ?? 0"
                                               @change="setMonto(compra.id, $event.target.value, compra.saldo)"
                                               @input="setMonto(compra.id, $event.target.value, compra.saldo)"
                                               class="w-full text-right text-sm border border-[#DDE1E7] rounded-lg px-2 py-1 focus:ring-2 focus:ring-amber-600 focus:border-transparent outline-none disabled:bg-[#E9ECF0] disabled:text-gray-400">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-[#DDE1E7] bg-[#E9ECF0]">
                                <td colspan="5" class="px-4 py-2 text-xs font-semibold text-gray-600 text-right uppercase tracking-wide">
                                    Total seleccionado
                                </td>
                                <td class="px-4 py-2 text-right font-bold text-amber-700"
                                    x-text="fmt(total)"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Sin facturas pendientes --}}
                <div x-show="compras.length === 0" class="px-4 py-12 text-center text-gray-400">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm">Este proveedor no tiene facturas pendientes de pago</p>
                </div>
            </div>

            @else
            <div class="bg-white rounded-xl border border-[#DDE1E7] px-4 py-16 text-center text-gray-300">
                <svg class="w-10 h-10 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <p class="text-sm">Seleccione un proveedor para ver sus facturas</p>
            </div>
            @endif
        </div>

        {{-- Derecha: datos de la orden de pago --}}
        <div class="xl:col-span-1 space-y-4">
            <div class="bg-white rounded-xl shadow-sm p-4 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700">Datos de la orden de pago</h2>

                {{-- Proveedor --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Proveedor *</label>
                    <select wire:model.live="proveedor_id"
                            class="w-full text-sm border {{ $errors->has('proveedor_id') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                        <option value="">— Seleccionar —</option>
                        @foreach($proveedores as $p)
                            <option value="{{ $p->id }}">{{ $p->razon_social }}</option>
                        @endforeach
                    </select>
                    @error('proveedor_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Saldo actual --}}
                @if($proveedor_id)
                <div class="bg-[#E9ECF0] rounded-lg px-3 py-2 flex justify-between items-center">
                    <span class="text-xs text-gray-500">Deuda actual con el proveedor</span>
                    <span class="text-sm font-bold {{ $saldoProveedor > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                        ${{ number_format($saldoProveedor, 2, ',', '.') }}
                    </span>
                </div>
                @endif

                {{-- Fecha --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Fecha</label>
                    <input wire:model="fecha" type="date"
                           class="w-full text-sm border {{ $errors->has('fecha') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>

                {{-- Total calculado --}}
                <div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 flex justify-between items-center">
                    <span class="text-xs text-amber-700 font-medium">Total a pagar</span>
                    <span class="text-base font-bold text-amber-700" x-text="fmt(total)">$0,00</span>
                </div>

                {{-- Forma de pago --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Forma de pago</label>
                    <select wire:model="forma_pago"
                            class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="cheque">Cheque</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>

                {{-- Datos del cheque emitido (visible solo si forma_pago = cheque) --}}
                @if($forma_pago === 'cheque')
                <div class="p-3 bg-purple-50 border border-purple-200 rounded-lg space-y-3">
                    <p class="text-xs font-semibold text-purple-700">Datos del cheque propio emitido</p>
                    <p class="text-[11px] text-purple-600">La plata sale de la caja banco al debitar el cheque desde <span class="font-semibold">Cheques</span>, no ahora.</p>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Banco <span class="text-red-500">*</span></label>
                            <select wire:model="cheque_banco_id"
                                    class="w-full px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none @error('cheque_banco_id') border-red-400 @enderror">
                                <option value="">Seleccionar...</option>
                                @foreach($bancos as $banco)
                                    <option value="{{ $banco->id }}">{{ $banco->descripcion }}</option>
                                @endforeach
                            </select>
                            @error('cheque_banco_id') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Serie / Talonario</label>
                            <input wire:model="cheque_serie" type="text" maxlength="10"
                                   class="w-full px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none"
                                   placeholder="0001">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Nº cheque <span class="text-red-500">*</span></label>
                            <input wire:model="cheque_nro" type="text" maxlength="30"
                                   class="w-full px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none @error('cheque_nro') border-red-400 @enderror"
                                   placeholder="00001234">
                            @error('cheque_nro') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Fecha de emisión</label>
                            <input wire:model="cheque_fecha_emision" type="date"
                                   class="w-full px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Fecha de pago (diferido) <span class="text-red-500">*</span></label>
                            <input wire:model="cheque_fecha_pago" type="date"
                                   class="w-full px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none @error('cheque_fecha_pago') border-red-400 @enderror">
                            @error('cheque_fecha_pago') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-600">
                                <input wire:model="cheque_es_echeq" type="checkbox"
                                       class="rounded border-gray-300 text-slate-900 focus:ring-slate-900">
                                Es e-Cheq (electrónico)
                            </label>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Referencia --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Referencia / Comprobante</label>
                    <input wire:model="referencia" type="text"
                           placeholder="Nº transferencia, cheque, etc."
                           class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>

                {{-- Observación --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Observación</label>
                    <textarea wire:model="observacion" rows="2"
                              class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none resize-none"></textarea>
                </div>

                <button type="button"
                        @click="
                            const detalles = getDetalles();
                            if (detalles.length === 0) { alert('Seleccione al menos una factura y asigne un monto.'); return; }
                            $wire.save(detalles);
                        "
                        wire:loading.attr="disabled"
                        :disabled="total <= 0"
                        class="w-full px-4 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-500 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="save">Registrar pago</span>
                    <span wire:loading wire:target="save">Procesando...</span>
                </button>
            </div>
        </div>

    </div>
    @endif
</div>
