<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('recibos') }}" wire:navigate
               class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-md transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    {{ $this->reciboId ? 'Ver recibo' : 'Nuevo recibo' }}
                </h1>
                <p class="text-sm text-gray-500 mt-0.5">Registro de cobro a cliente</p>
            </div>
        </div>
    </div>

    {{-- Sin caja --}}
    @if($sinCaja && ! $readOnly)
    <div class="mb-4 flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg px-4 py-3">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        No hay una caja abierta. El recibo no se asociará a ninguna caja.
    </div>
    @endif

    @if($errors->has('detalles'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
        {{ $errors->first('detalles') }}
    </div>
    @endif

    @if($errors->has('cliente_id') || $errors->has('fecha') || $errors->has('forma_pago'))
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
            @if(count($reciboDetalles) > 0)
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-[#DDE1E7]">
                    <h2 class="text-sm font-semibold text-gray-700">Facturas cobradas</h2>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0] text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            <th class="px-4 py-2 text-left">Nº Venta</th>
                            <th class="px-4 py-2 text-left">Fecha</th>
                            <th class="px-4 py-2 text-right">Total factura</th>
                            <th class="px-4 py-2 text-right">Monto cobrado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E9ECF0]">
                        @foreach($reciboDetalles as $det)
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs text-gray-700">#{{ $det['nro_venta'] }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ $det['fecha'] }}</td>
                            <td class="px-4 py-2 text-right text-gray-700">${{ number_format($det['total'], 2, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right font-medium text-emerald-700">${{ number_format($det['monto'], 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        <div class="xl:col-span-1">
            <div class="bg-white rounded-xl shadow-sm p-4 space-y-3">
                <h2 class="text-sm font-semibold text-gray-700">Datos del recibo</h2>
                <div class="grid grid-cols-2 gap-y-2 text-sm">
                    <span class="text-gray-500">Cliente</span>
                    <span class="font-medium text-gray-900">{{ $clientes->find($cliente_id)?->razon_social ?? '—' }}</span>
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
                    <span class="text-gray-500 font-medium">Total cobrado</span>
                    <span class="font-bold text-emerald-700">${{ number_format($total, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    @else
    {{-- MODO CREACIÓN con Alpine.js --}}
    <div
        x-data="{
            ventas: $wire.entangle('ventasPendientes'),
            checked: {},
            montos: {},
            init() {
                this.$watch('ventas', () => {
                    this.checked = {};
                    this.montos  = {};
                });
            },
            get total() {
                return this.ventas
                    .filter(v => this.checked[v.id])
                    .reduce((sum, v) => sum + (parseFloat(this.montos[v.id]) || 0), 0);
            },
            fmt(v) {
                const n = parseFloat(v || 0).toFixed(2);
                const parts = n.split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                return '$' + parts[0] + ',' + parts[1];
            },
            toggle(venta) {
                if (this.checked[venta.id]) {
                    delete this.checked[venta.id];
                    delete this.montos[venta.id];
                } else {
                    this.checked[venta.id] = true;
                    this.montos[venta.id]  = venta.saldo;
                }
            },
            setMonto(ventaId, value, maxSaldo) {
                let v = parseFloat(value) || 0;
                if (v > maxSaldo) v = maxSaldo;
                if (v < 0)        v = 0;
                this.montos[ventaId] = v;
            },
            selectAll() {
                this.ventas.forEach(v => {
                    this.checked[v.id] = true;
                    this.montos[v.id]  = v.saldo;
                });
            },
            clearAll() {
                this.checked = {};
                this.montos  = {};
            },
            getDetalles() {
                return this.ventas
                    .filter(v => this.checked[v.id] && parseFloat(this.montos[v.id]) > 0)
                    .map(v => ({ venta_id: v.id, monto_aplicar: parseFloat(this.montos[v.id]) }));
            }
        }"
        class="grid grid-cols-1 xl:grid-cols-3 gap-4"
    >

        {{-- Izquierda: facturas pendientes --}}
        <div class="xl:col-span-2">

            @if($cliente_id)
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-[#DDE1E7] flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-700">Facturas pendientes</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Seleccione las facturas y asigne el monto a cobrar</p>
                    </div>
                    <div class="flex gap-2" x-show="ventas.length > 0">
                        <button type="button" @click="selectAll()"
                                class="text-xs text-emerald-700 hover:text-emerald-900 underline">
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
                <div x-show="ventas.length > 0">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0] text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                <th class="px-3 py-2 w-8"></th>
                                <th class="px-4 py-2 text-left">Nº Venta</th>
                                <th class="px-4 py-2 text-left">Fecha</th>
                                <th class="px-4 py-2 text-right">Total</th>
                                <th class="px-4 py-2 text-right">Saldo</th>
                                <th class="px-4 py-2 text-right">Monto a cobrar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E9ECF0]">
                            <template x-for="venta in ventas" :key="venta.id">
                                <tr :class="checked[venta.id] ? 'bg-emerald-50' : 'hover:bg-[#E9ECF0]'"
                                    class="transition-colors">
                                    <td class="px-3 py-2 text-center">
                                        <input type="checkbox"
                                               :checked="checked[venta.id]"
                                               @change="toggle(venta)"
                                               class="w-4 h-4 rounded accent-emerald-700 cursor-pointer">
                                    </td>
                                    <td class="px-4 py-2 font-mono text-xs text-gray-700"
                                        x-text="'#' + venta.nro_venta"></td>
                                    <td class="px-4 py-2 text-gray-600" x-text="venta.fecha"></td>
                                    <td class="px-4 py-2 text-right text-gray-700"
                                        x-text="fmt(venta.total)"></td>
                                    <td class="px-4 py-2 text-right font-medium text-red-600"
                                        x-text="fmt(venta.saldo)"></td>
                                    <td class="px-4 py-2">
                                        <input type="number" step="0.01" min="0.01"
                                               :max="venta.saldo"
                                               :disabled="!checked[venta.id]"
                                               :value="montos[venta.id] ?? 0"
                                               @change="setMonto(venta.id, $event.target.value, venta.saldo)"
                                               @input="setMonto(venta.id, $event.target.value, venta.saldo)"
                                               class="w-full text-right text-sm border border-[#DDE1E7] rounded-lg px-2 py-1 focus:ring-2 focus:ring-emerald-600 focus:border-transparent outline-none disabled:bg-[#E9ECF0] disabled:text-gray-400">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-[#DDE1E7] bg-[#E9ECF0]">
                                <td colspan="5" class="px-4 py-2 text-xs font-semibold text-gray-600 text-right uppercase tracking-wide">
                                    Total seleccionado
                                </td>
                                <td class="px-4 py-2 text-right font-bold text-emerald-700"
                                    x-text="fmt(total)"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Sin facturas pendientes --}}
                <div x-show="ventas.length === 0" class="px-4 py-12 text-center text-gray-400">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm">Este cliente no tiene facturas pendientes</p>
                </div>
            </div>

            @else
            <div class="bg-white rounded-xl border border-[#DDE1E7] px-4 py-16 text-center text-gray-300">
                <svg class="w-10 h-10 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-sm">Seleccione un cliente para ver sus facturas</p>
            </div>
            @endif
        </div>

        {{-- Derecha: datos del recibo --}}
        <div class="xl:col-span-1 space-y-4">
            <div class="bg-white rounded-xl shadow-sm p-4 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700">Datos del recibo</h2>

                {{-- Cliente --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Cliente *</label>
                    <select wire:model.live="cliente_id"
                            class="w-full text-sm border {{ $errors->has('cliente_id') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                        <option value="">— Seleccionar —</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c->id }}">{{ $c->razon_social }}</option>
                        @endforeach
                    </select>
                    @error('cliente_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Saldo actual --}}
                @if($cliente_id)
                <div class="bg-[#E9ECF0] rounded-lg px-3 py-2 flex justify-between items-center">
                    <span class="text-xs text-gray-500">Saldo actual del cliente</span>
                    <span class="text-sm font-bold {{ $saldoCliente > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                        ${{ number_format($saldoCliente, 2, ',', '.') }}
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
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2 flex justify-between items-center">
                    <span class="text-xs text-emerald-700 font-medium">Total a cobrar</span>
                    <span class="text-base font-bold text-emerald-700" x-text="fmt(total)">$0,00</span>
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
                        class="w-full px-4 py-2.5 bg-emerald-700 text-white text-sm font-medium rounded-lg hover:bg-emerald-600 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="save">Registrar cobro</span>
                    <span wire:loading wire:target="save">Procesando...</span>
                </button>
            </div>
        </div>

    </div>
    @endif
</div>
