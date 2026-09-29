<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Cheques</h1>
            <p class="text-sm text-gray-500 mt-0.5">Cartera de cheques recibidos y emitidos</p>
        </div>
        @if(! $showForm)
        <button wire:click="nuevo"
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Registrar cheque
        </button>
        @endif
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
    @if(session('error'))
    <div class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('error') }}
    </div>
    @endif

    {{-- Formulario inline --}}
    @if($showForm)
    <div class="bg-white rounded-xl shadow-sm p-5 mb-4">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">
            {{ $editingId ? 'Editar cheque' : 'Registrar cheque' }}
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            {{-- Tipo --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                <select wire:model.live="tipo"
                        class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none bg-white"
                        @if($editingId) disabled @endif>
                    <option value="recibido">Recibido (en cartera)</option>
                    <option value="emitido">Emitido (a proveedor)</option>
                </select>
            </div>
            {{-- Banco --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Banco <span class="text-red-500">*</span></label>
                <input wire:model="banco" type="text" maxlength="100"
                       class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none @error('banco') border-red-400 @enderror"
                       placeholder="Ej: Banco Nación">
                @error('banco') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            {{-- Serie --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Serie / Talonario</label>
                <input wire:model="serie" type="text" maxlength="10"
                       class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none"
                       placeholder="Ej: 0001">
            </div>
            {{-- Nº cheque --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Nº cheque <span class="text-red-500">*</span></label>
                <input wire:model="nro_cheque" type="text" maxlength="50"
                       class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none @error('nro_cheque') border-red-400 @enderror"
                       placeholder="00001234">
                @error('nro_cheque') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            {{-- Titular --}}
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Titular (librador) <span class="text-red-500">*</span></label>
                <input wire:model="titular" type="text" maxlength="150"
                       class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none @error('titular') border-red-400 @enderror"
                       placeholder="Nombre del librador del cheque">
                @error('titular') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            {{-- CUIT titular --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">CUIT titular</label>
                <input wire:model="cuit_titular" type="text" maxlength="20"
                       class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none"
                       placeholder="20-12345678-9">
            </div>
            {{-- Monto --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Monto <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">$</span>
                    <input wire:model="monto" type="number" min="0.01" step="0.01"
                           class="w-full pl-7 pr-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none @error('monto') border-red-400 @enderror">
                </div>
                @error('monto') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            {{-- Fecha emisión --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Fecha emisión <span class="text-red-500">*</span></label>
                <input wire:model="fecha_emision" type="date"
                       class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none @error('fecha_emision') border-red-400 @enderror">
                @error('fecha_emision') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            {{-- Fecha de cobro --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Fecha de cobro <span class="text-red-500">*</span></label>
                <input wire:model="fecha_cobro" type="date"
                       class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none @error('fecha_cobro') border-red-400 @enderror">
                @error('fecha_cobro') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            {{-- Cliente o Proveedor según tipo --}}
            @if($tipo === 'recibido')
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Cliente (de quién se recibió)</label>
                <select wire:model="cliente_id"
                        class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none bg-white">
                    <option value="">Sin cliente</option>
                    @foreach($clientes as $c)
                        <option value="{{ $c->id }}">{{ $c->razon_social }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Proveedor (a quién se emite)</label>
                <select wire:model="proveedor_id"
                        class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none bg-white">
                    <option value="">Sin proveedor</option>
                    @foreach($proveedores as $p)
                        <option value="{{ $p->id }}">{{ $p->razon_social }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            {{-- Referencia --}}
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Referencia / Comprobante origen</label>
                <input wire:model="referencia" type="text" maxlength="150"
                       class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none"
                       placeholder="Ej: Venta #42, OP #7, Recibo #15">
            </div>
            {{-- Observación --}}
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Observación</label>
                <input wire:model="observacion" type="text" maxlength="300"
                       class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none">
            </div>
        </div>
        <div class="flex items-center gap-3 mt-4 pt-4 border-t border-gray-100">
            <button wire:click="guardar" wire:loading.attr="disabled"
                    class="px-5 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-700 transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="guardar">{{ $editingId ? 'Actualizar' : 'Registrar' }}</span>
                <span wire:loading wire:target="guardar">Guardando...</span>
            </button>
            <button wire:click="cancelarForm"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-[#DDE1E7] rounded-lg hover:bg-[#E9ECF0] transition-colors">
                Cancelar
            </button>
        </div>
    </div>
    @endif

    {{-- Panel de movimientos --}}
    @if($showMovimientos && $chequeMovimientos)
    <div class="bg-white rounded-xl shadow-sm p-5 mb-4">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-900">Historial de movimientos</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $chequeMovimientos->banco }} · Nº {{ $chequeMovimientos->nro_cheque }} · {{ $chequeMovimientos->titular }}
                </p>
            </div>
            <button wire:click="cerrarMovimientos" class="text-gray-400 hover:text-gray-600 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        @if($movimientos->isEmpty())
            <p class="text-sm text-gray-400 py-4 text-center">Sin movimientos registrados.</p>
        @else
        <div class="space-y-2">
            @foreach($movimientos as $mov)
            @php
                $iconColor = match($mov->tipo_mov) {
                    'recepcion','emision' => 'text-slate-500 bg-slate-100',
                    'deposito'           => 'text-blue-600 bg-blue-100',
                    'cobro'              => 'text-emerald-600 bg-emerald-100',
                    'rechazo'            => 'text-red-500 bg-red-100',
                    'endoso'             => 'text-orange-600 bg-orange-100',
                    'anulacion'          => 'text-gray-400 bg-gray-100',
                    default              => 'text-gray-500 bg-gray-100',
                };
            @endphp
            <div class="flex items-start gap-3 py-2 border-b border-gray-50 last:border-0">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold {{ $iconColor }} flex-shrink-0 mt-0.5">
                    {{ strtoupper(substr($mov->tipo_mov, 0, 2)) }}
                </span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-900">{{ $mov->label_tipo_mov }}</span>
                        @if($mov->comprobante)
                            <span class="text-xs text-gray-400">— {{ $mov->comprobante }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-3 mt-0.5">
                        <span class="text-xs text-gray-400">{{ $mov->fecha_mov->format('d/m/Y') }}</span>
                        @if($mov->user)
                            <span class="text-xs text-gray-400">{{ $mov->user->name }}</span>
                        @endif
                        @if($mov->observacion)
                            <span class="text-xs text-gray-500 italic">{{ $mov->observacion }}</span>
                        @endif
                    </div>
                </div>
                <span class="text-sm font-medium text-gray-700 flex-shrink-0">
                    ${{ number_format($mov->monto, 2, ',', '.') }}
                </span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    {{-- Tarjetas de resumen --}}
    <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-xl border border-[#DDE1E7] px-4 py-3">
            <p class="text-xs text-gray-500 mb-1">En cartera (recibidos)</p>
            <p class="text-xl font-bold text-gray-900">${{ number_format($totalCartera, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-amber-200 px-4 py-3">
            <p class="text-xs text-amber-600 mb-1">Vencen en 7 días</p>
            <p class="text-xl font-bold text-amber-600">${{ number_format($totalPorVencer, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-purple-200 px-4 py-3">
            <p class="text-xs text-purple-600 mb-1">Emitidos pendientes</p>
            <p class="text-xl font-bold text-purple-700">${{ number_format($totalEmitidosPendientes, 2, ',', '.') }}</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <div class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[160px]">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" type="text"
                           placeholder="Titular, banco o Nº..."
                           class="w-full pl-9 pr-4 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 outline-none">
                </div>
            </div>
            <select wire:model.live="filterTipo"
                    class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none bg-white">
                <option value="">Recibidos + Emitidos</option>
                <option value="recibido">Recibidos</option>
                <option value="emitido">Emitidos</option>
            </select>
            <select wire:model.live="filterEstado"
                    class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none bg-white">
                <option value="">Todos los estados</option>
                <option value="en_cartera">En cartera</option>
                <option value="pendiente">Pendiente (emitido)</option>
                <option value="depositado">Depositado</option>
                <option value="cobrado">Cobrado</option>
                <option value="rechazado">Rechazado</option>
                <option value="endosado">Endosado</option>
                <option value="anulado">Anulado</option>
            </select>
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-500 whitespace-nowrap">Cobro desde</label>
                <input wire:model.live="filterDesde" type="date"
                       class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-500">hasta</label>
                <input wire:model.live="filterHasta" type="date"
                       class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none">
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Banco / Nº</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Titular</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">F. Cobro</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Monto</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Referencia</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($cheques as $cheque)
                    @php
                        $vencePronto = $cheque->fecha_cobro->isBefore(now()->addDays(7))
                                       && in_array($cheque->estado, ['en_cartera', 'depositado', 'pendiente'])
                                       && $cheque->fecha_cobro->isFuture();
                        $vencido     = $cheque->fecha_cobro->isPast()
                                       && in_array($cheque->estado, ['en_cartera', 'depositado', 'pendiente']);
                        $rowClass    = $vencido ? 'bg-red-50' : ($vencePronto ? 'bg-amber-50' : '');

                        $badgeColor = match($cheque->estado) {
                            'en_cartera' => 'bg-slate-100 text-slate-700',
                            'pendiente'  => 'bg-purple-100 text-purple-700',
                            'depositado' => 'bg-blue-100 text-blue-700',
                            'cobrado'    => 'bg-emerald-100 text-emerald-700',
                            'rechazado'  => 'bg-red-100 text-red-600',
                            'endosado'   => 'bg-orange-100 text-orange-700',
                            'anulado'    => 'bg-gray-100 text-gray-400',
                            default      => 'bg-gray-100 text-gray-500',
                        };
                    @endphp
                    <tr class="hover:bg-[#E9ECF0] transition-colors group {{ $rowClass }}">
                        <td class="px-4 py-3">
                            @if($cheque->tipo === 'recibido')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">↓ Recibido</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">↑ Emitido</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $cheque->banco }}</div>
                            <div class="font-mono text-xs text-gray-400">
                                @if($cheque->serie) {{ $cheque->serie }}-@endif{{ $cheque->nro_cheque }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-gray-900">{{ $cheque->titular }}</div>
                            @if($cheque->tipo === 'recibido' && $cheque->cliente)
                                <div class="text-xs text-gray-400">{{ $cheque->cliente->razon_social }}</div>
                            @elseif($cheque->tipo === 'emitido' && $cheque->proveedor)
                                <div class="text-xs text-gray-400">{{ $cheque->proveedor->razon_social }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="{{ $vencido ? 'text-red-600 font-semibold' : ($vencePronto ? 'text-amber-600 font-medium' : 'text-gray-700') }}">
                                {{ $cheque->fecha_cobro->format('d/m/Y') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900">
                            ${{ number_format($cheque->monto, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 max-w-[160px] truncate">
                            {{ $cheque->referencia ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }}">
                                {{ $cheque->label_estado }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">

                                {{-- Historial --}}
                                <button wire:click="verMovimientos({{ $cheque->id }})"
                                        class="p-1.5 text-gray-400 hover:text-slate-700 hover:bg-gray-100 rounded-md transition-colors" title="Ver historial">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>

                                {{-- Editar (solo estados no terminales) --}}
                                @if(! $cheque->isTerminal())
                                <button wire:click="editar({{ $cheque->id }})"
                                        class="p-1.5 text-gray-400 hover:text-slate-900 hover:bg-gray-100 rounded-md transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                @endif

                                {{-- Transiciones de estado: RECIBIDO --}}
                                @if($cheque->estado === 'en_cartera')
                                    <button wire:click="cambiarEstado({{ $cheque->id }}, 'depositado')"
                                            class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors" title="Depositar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                    </button>
                                    <button wire:click="cambiarEstado({{ $cheque->id }}, 'endosado')"
                                            wire:confirm="¿Confirmar endoso? El cheque pasará a 'Endosado' y no se podrá modificar."
                                            class="p-1.5 text-gray-400 hover:text-orange-600 hover:bg-orange-50 rounded-md transition-colors" title="Endosar a proveedor">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                        </svg>
                                    </button>
                                    <button wire:click="cambiarEstado({{ $cheque->id }}, 'anulado')"
                                            wire:confirm="¿Anular este cheque?"
                                            class="p-1.5 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-md transition-colors" title="Anular">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    </button>
                                @elseif($cheque->estado === 'depositado')
                                    <button wire:click="cambiarEstado({{ $cheque->id }}, 'cobrado')"
                                            class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-md transition-colors" title="Marcar cobrado">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                    <button wire:click="cambiarEstado({{ $cheque->id }}, 'rechazado')"
                                            wire:confirm="¿Marcar este cheque como rechazado / sin fondos?"
                                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-md transition-colors" title="Marcar rechazado">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>

                                {{-- Transiciones de estado: EMITIDO --}}
                                @elseif($cheque->estado === 'pendiente')
                                    <button wire:click="cambiarEstado({{ $cheque->id }}, 'cobrado')"
                                            class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-md transition-colors" title="Marcar debitado / cobrado">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                    <button wire:click="cambiarEstado({{ $cheque->id }}, 'rechazado')"
                                            wire:confirm="¿Marcar este cheque emitido como rechazado?"
                                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-md transition-colors" title="Rechazado / devuelto">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                    <button wire:click="cambiarEstado({{ $cheque->id }}, 'anulado')"
                                            wire:confirm="¿Anular este cheque emitido?"
                                            class="p-1.5 text-gray-400 hover:text-gray-500 hover:bg-gray-100 rounded-md transition-colors" title="Anular">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 115.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-16 text-center text-gray-400">
                            <p class="text-sm">No se encontraron cheques</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($cheques->hasPages())
        <div class="px-4 py-3 border-t border-[#DDE1E7]">
            {{ $cheques->links() }}
        </div>
        @endif
    </div>
</div>
