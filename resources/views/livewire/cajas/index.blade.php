<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Caja</h1>
            <p class="text-sm text-gray-500 mt-0.5">Control de apertura, cierre y transferencias</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="abrirAjuste"
                    class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-[#DDE1E7] text-slate-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Movimiento
            </button>
            <button wire:click="abrirTransferencia"
                    class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-[#DDE1E7] text-slate-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Transferir
            </button>
            @if($esAdmin)
            <button wire:click="$set('showNuevaCaja', true)"
                    class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-[#DDE1E7] text-slate-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva caja
            </button>
            @endif
            @if(! $cajaAbierta)
                <button wire:click="abrirApertura"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Abrir caja
                </button>
            @else
                <button wire:click="$set('showCierre', true)"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Cerrar caja
                </button>
            @endif
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

    {{-- Estado de mi caja --}}
    @if($cajaAbierta)
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 mb-6">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-emerald-900">Mi caja está abierta</p>
                    <p class="text-sm text-emerald-700">
                        Apertura: {{ $cajaAbierta->fecha_apertura->format('d/m/Y') }}
                        a las {{ $cajaAbierta->hora_apertura->format('H:i') }}
                    </p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs text-emerald-600 font-medium uppercase tracking-wide">Saldo inicial</p>
                <p class="text-lg font-bold text-emerald-900">${{ number_format($cajaAbierta->saldo_inicial, 2, ',', '.') }}</p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3">
            <div class="bg-white rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">Ventas</p>
                <p class="font-semibold text-gray-900">${{ number_format($cajaAbierta->total_ventas, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">Cobros <span class="text-gray-400">s/CC</span></p>
                <p class="font-semibold text-gray-900">${{ number_format($cajaAbierta->total_cobrado_sin_cc, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">Pagos</p>
                <p class="font-semibold text-red-600">${{ number_format($cajaAbierta->total_pagos, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">Gastos</p>
                <p class="font-semibold text-red-600">${{ number_format($cajaAbierta->total_gastos, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">Transferencias</p>
                <p class="font-semibold text-gray-900">
                    <span class="text-emerald-600">+{{ number_format($cajaAbierta->total_transferencias_entrada, 0, ',', '.') }}</span>
                    /
                    <span class="text-red-600">-{{ number_format($cajaAbierta->total_transferencias_salida, 0, ',', '.') }}</span>
                </p>
            </div>
            <div class="bg-white rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">Saldo estimado <span class="text-gray-400">(todos)</span></p>
                <p class="font-bold text-slate-500">${{ number_format($cajaAbierta->saldo_esperado, 2, ',', '.') }}</p>
            </div>
            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3 text-center">
                <p class="text-xs text-indigo-700 font-semibold mb-1">Saldo en efectivo</p>
                <p class="font-bold text-indigo-900">${{ number_format($cajaAbierta->saldo_efectivo_esperado, 2, ',', '.') }}</p>
            </div>
        </div>

        {{-- Detalle de lo que movió el efectivo. Sin esto el operador ve un número
             suelto que cambia entre una consulta y la siguiente, sin poder atribuir
             el salto a la venta o el cobro que lo produjo. --}}
        @if(count($ultimosEfectivo) > 0)
        <div class="mt-4" x-data="{ abierto: false }">
            <button type="button" x-on:click="abierto = !abierto"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-800 hover:text-emerald-900">
                <svg class="w-3.5 h-3.5 transition-transform" :class="abierto && 'rotate-90'"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                <span x-text="abierto ? 'Ocultar los movimientos del efectivo' : '¿Por qué cambió el saldo en efectivo?'"></span>
            </button>

            <div x-cloak x-show="abierto" class="mt-2 bg-white rounded-lg overflow-hidden">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-[#F7F8FA] text-gray-500">
                            <th class="px-3 py-2 text-left font-semibold">Hora</th>
                            <th class="px-3 py-2 text-left font-semibold">Movimiento</th>
                            <th class="px-3 py-2 text-right font-semibold">Importe</th>
                            <th class="px-3 py-2 text-right font-semibold text-indigo-600">Efectivo en caja</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E9ECF0]">
                        @foreach($ultimosEfectivo as $m)
                        <tr>
                            <td class="px-3 py-1.5 text-gray-400 tabular-nums">{{ $m['hora'] }}</td>
                            <td class="px-3 py-1.5 text-gray-700">{{ $m['descripcion'] }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums font-medium {{ $m['signo'] === '+' ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $m['signo'] }}${{ $m['monto'] }}
                            </td>
                            <td class="px-3 py-1.5 text-right tabular-nums font-semibold text-indigo-800">${{ $m['saldo'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="px-3 py-2 text-[11px] text-gray-400 bg-[#F7F8FA]">
                    Últimos {{ count($ultimosEfectivo) }} movimientos que tocaron el efectivo.
                    Las ventas a cuenta corriente, por transferencia o con tarjeta no aparecen acá porque no entran al cajón.
                    <a href="{{ route('cajas.show', $cajaAbierta) }}" wire:navigate class="text-slate-600 underline">Ver el detalle completo</a>.
                </p>
            </div>
        </div>
        @endif
    </div>
    @else
    <div class="bg-[#E9ECF0] border border-[#DDE1E7] rounded-xl p-5 mb-6 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <div>
            <p class="font-semibold text-gray-700">Mi caja está cerrada</p>
            <p class="text-sm text-gray-500">No tiene una caja diaria abierta. Presione "Abrir caja" para comenzar.</p>
        </div>
    </div>
    @endif

    {{-- Cajas activas (generales / bancos / otras) --}}
    @php
        $otrasCajas = $cajasAbiertas->filter(fn($c) => ! $cajaAbierta || $c->id !== $cajaAbierta->id);
    @endphp
    @if($otrasCajas->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-4 py-3 border-b border-[#DDE1E7]">
            <h2 class="text-sm font-semibold text-gray-900">Otras cajas abiertas</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 p-4">
            @foreach($otrasCajas as $c)
            <a href="{{ route('cajas.show', $c) }}" wire:navigate
               class="block border border-[#DDE1E7] rounded-lg p-4 hover:bg-[#E9ECF0] transition-colors">
                <div class="flex items-center justify-between mb-1">
                    <span class="font-medium text-gray-900">{{ $c->nombreEtiqueta() }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $c->tipo === 'banco' ? 'bg-blue-100 text-blue-700' : ($c->tipo === 'general' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500') }}">
                        {{ ucfirst($c->tipo) }}
                    </span>
                </div>
                <p class="text-xs text-indigo-600 font-medium">Saldo en efectivo</p>
                <p class="text-lg font-bold text-indigo-900">${{ number_format($c->saldo_efectivo_esperado, 2, ',', '.') }}</p>
                <p class="text-[11px] text-gray-400 mt-0.5">Estimado (todos los medios): ${{ number_format($c->saldo_esperado, 2, ',', '.') }}</p>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Historial --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-[#DDE1E7]">
            <h2 class="text-sm font-semibold text-gray-900">Historial de cajas</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Caja</th>
                        @if($esAdmin)
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Usuario</th>
                        @endif
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Apertura / Cierre</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Saldo inicial</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Ventas</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Cobros</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Gastos</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Saldo final</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($historial as $caja)
                    <tr class="hover:bg-[#E9ECF0] transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-900">
                            {{ $caja->fecha_apertura->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-xs">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium
                                {{ $caja->tipo === 'banco' ? 'bg-blue-100 text-blue-700' : ($caja->tipo === 'general' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600') }}">
                                {{ $caja->nombreEtiqueta() }}
                            </span>
                        </td>
                        @if($esAdmin)
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $caja->user?->name ?? '—' }}</td>
                        @endif
                        <td class="px-4 py-3 text-xs text-gray-500">
                            <div>Abre: {{ $caja->hora_apertura?->format('H:i') }}</div>
                            @if($caja->hora_cierre)
                                <div>Cierra: {{ $caja->hora_cierre?->format('H:i') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">${{ number_format($caja->saldo_inicial, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">${{ number_format($caja->total_ventas, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">${{ number_format($caja->total_cobros, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-red-600">${{ number_format($caja->total_gastos, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-medium">
                            @if($caja->estado === 'cerrada')
                                @php $diff = (float)$caja->saldo_final - $caja->saldo_efectivo_esperado; @endphp
                                <span class="{{ abs($diff) > 1 ? 'text-red-600' : 'text-gray-900' }}">
                                    ${{ number_format($caja->saldo_final, 2, ',', '.') }}
                                </span>
                                @if(abs($diff) > 0.01)
                                    <div class="text-xs {{ $diff > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $diff > 0 ? '+' : '' }}${{ number_format($diff, 2, ',', '.') }}
                                    </div>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($caja->estado === 'abierta')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Abierta</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Cerrada</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('cajas.show', $caja) }}" wire:navigate
                               class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-slate-700 hover:bg-gray-100 transition-colors"
                               title="Ver movimientos">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $esAdmin ? 11 : 10 }}" class="px-4 py-12 text-center text-gray-400">
                            <p class="text-sm">No hay cajas registradas</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($historial->hasPages())
        <div class="px-4 py-3 border-t border-[#DDE1E7]">
            {{ $historial->links() }}
        </div>
        @endif
    </div>

    {{-- Modal apertura --}}
    @if($showApertura)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 p-6 w-full max-w-sm">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-semibold text-gray-900">Abrir caja</h3>
                <button wire:click="$set('showApertura', false)" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-4"
                 x-data="{ cierre: {{ $cierreAnterior }}, monto: @entangle('saldo_inicial') }">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Saldo inicial <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium">$</span>
                        <input wire:model="saldo_inicial" x-model.number="monto" type="number" min="0" step="0.01"
                               class="w-full pl-7 pr-3 py-2.5 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('saldo_inicial') ? 'border-red-300' : 'border-[#DDE1E7]' }}"
                               placeholder="0,00" autofocus>
                    </div>
                    @error('saldo_inicial') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                    {{-- El cierre anterior viene precargado: si se cambia, se avisa la
                         diferencia en el momento en vez de descubrirla al cerrar. --}}
                    @if($cierreAnteriorFecha)
                        <div class="mt-2 text-xs">
                            <div class="flex items-center justify-between text-gray-500">
                                <span>Cierre del {{ $cierreAnteriorFecha }}</span>
                                <button type="button" x-on:click="monto = cierre"
                                        class="font-medium text-slate-700 hover:text-slate-900 underline decoration-dotted">
                                    ${{ number_format($cierreAnterior, 2, ',', '.') }}
                                </button>
                            </div>
                            <p x-cloak x-show="Math.abs((parseFloat(monto) || 0) - cierre) > 0.01"
                               class="mt-1.5 px-2.5 py-1.5 rounded-md bg-amber-50 border border-amber-200 text-amber-800">
                                Está abriendo con
                                <strong x-text="((parseFloat(monto) || 0) - cierre > 0 ? '+$' : '−$') + Math.abs((parseFloat(monto) || 0) - cierre).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></strong>
                                respecto del cierre de ayer. Si es correcto, anótelo en la observación.
                            </p>
                        </div>
                    @else
                        <p class="mt-2 text-xs text-gray-400">No hay una caja cerrada previa para tomar como referencia.</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observación</label>
                    <input wire:model="obs_apertura" type="text" maxlength="200"
                           class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none"
                           placeholder="Opcional...">
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button wire:click="$set('showApertura', false)"
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button wire:click="abrirCaja"
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                    Abrir caja
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal nueva caja general / banco (admin) --}}
    @if($showNuevaCaja)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 p-6 w-full max-w-sm">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-semibold text-gray-900">Nueva caja</h3>
                <button wire:click="$set('showNuevaCaja', false)" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input wire:model="nueva_nombre" type="text" maxlength="100" placeholder="Ej. Caja General, Banco Nación"
                           class="w-full px-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('nueva_nombre') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                    @error('nueva_nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo <span class="text-red-500">*</span></label>
                    <select wire:model="nueva_tipo" class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                        <option value="general">Caja general</option>
                        <option value="banco">Banco</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Saldo inicial <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium">$</span>
                        <input wire:model="nueva_saldo" type="number" min="0" step="0.01" placeholder="0,00"
                               class="w-full pl-7 pr-3 py-2.5 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('nueva_saldo') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                    </div>
                    @error('nueva_saldo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button wire:click="$set('showNuevaCaja', false)"
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button wire:click="crearCajaGeneral"
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-800 transition-colors">
                    Crear caja
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal transferencia --}}
    @if($showTransfer)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 p-6 w-full max-w-sm">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-semibold text-gray-900">Transferir entre cajas</h3>
                <button wire:click="$set('showTransfer', false)" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Desde <span class="text-red-500">*</span></label>
                    <select wire:model="transferOrigen"
                            class="w-full px-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('transferOrigen') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                        <option value="">Seleccione...</option>
                        @foreach($cajasAbiertas as $c)
                            <option value="{{ $c->id }}">{{ $c->nombreEtiqueta() }} — efvo ${{ number_format($c->saldo_transferible, 2, ',', '.') }}</option>
                        @endforeach
                    </select>
                    @error('transferOrigen') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hacia <span class="text-red-500">*</span></label>
                    <select wire:model="transferDestino"
                            class="w-full px-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('transferDestino') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                        <option value="">Seleccione...</option>
                        @foreach($cajasAbiertas as $c)
                            <option value="{{ $c->id }}">{{ $c->nombreEtiqueta() }}</option>
                        @endforeach
                    </select>
                    @error('transferDestino') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium">$</span>
                        <input wire:model="transferMonto" type="number" min="0" step="0.01" placeholder="0,00"
                               class="w-full pl-7 pr-3 py-2.5 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('transferMonto') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                    </div>
                    @error('transferMonto') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <input wire:model="transferDesc" type="text" maxlength="200" placeholder="Opcional..."
                           class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button wire:click="$set('showTransfer', false)"
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button wire:click="transferir"
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-800 transition-colors">
                    Transferir
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal cierre --}}
    @if($showCierre && $cajaAbierta)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 p-6 w-full max-w-sm">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-semibold text-gray-900">Cerrar caja</h3>
                <button wire:click="$set('showCierre', false)" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Resumen del efectivo (lo que se cuenta al cerrar) --}}
            @php
                $resCierre  = $cajaAbierta->resumenMovimientos();
                $efectivoEsp = $cajaAbierta->saldo_efectivo_esperado;
            @endphp
            <div x-data="{ contado: 0, esperado: @js((float) $efectivoEsp) }" class="space-y-4">
                <div class="bg-[#E9ECF0] rounded-lg p-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Saldo inicial (efectivo)</span>
                        <span class="font-medium">${{ number_format($cajaAbierta->saldo_inicial, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">+ Ingresos en efectivo</span>
                        <span class="font-medium text-emerald-600">${{ number_format($resCierre['efectivo_ingreso'], 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">− Egresos en efectivo</span>
                        <span class="font-medium text-red-600">${{ number_format($resCierre['efectivo_egreso'], 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-t border-indigo-200 pt-2">
                        <span class="font-semibold text-indigo-700">Saldo en efectivo esperado</span>
                        <span class="font-bold text-indigo-900">${{ number_format($efectivoEsp, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-[11px] text-gray-400 pt-1">
                        <span>Saldo estimado (todos los medios)</span>
                        <span>${{ number_format($cajaAbierta->saldo_esperado, 2, ',', '.') }}</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Efectivo real contado <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium">$</span>
                        <input wire:model="saldo_final" type="number" min="0" step="0.01"
                               x-init="contado = parseFloat($el.value) || 0"
                               @input="contado = parseFloat($event.target.value) || 0"
                               class="w-full pl-7 pr-3 py-2.5 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('saldo_final') ? 'border-red-300' : 'border-[#DDE1E7]' }}"
                               placeholder="0,00">
                    </div>
                    @error('saldo_final') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    {{-- Diferencia de arqueo en vivo vs efectivo esperado --}}
                    <template x-if="contado > 0">
                        <p class="mt-1.5 text-xs font-medium"
                           :class="Math.abs(contado - esperado) < 1 ? 'text-emerald-600' : (contado - esperado > 0 ? 'text-emerald-600' : 'text-red-600')">
                            <span x-show="Math.abs(contado - esperado) < 1">✓ Arqueo OK</span>
                            <span x-show="Math.abs(contado - esperado) >= 1"
                                  x-text="(contado - esperado > 0 ? 'Sobrante +$' : 'Faltante -$') + Math.abs(contado - esperado).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                        </p>
                    </template>
                </div>

                {{-- Transferencia al cerrar --}}
                @if($cajasGenerales->isNotEmpty())
                <div class="border border-[#DDE1E7] rounded-lg p-3">
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer">
                        <input type="checkbox" wire:model.live="transferirAlCerrar" class="rounded border-gray-300 text-slate-900 focus:ring-slate-900">
                        Transferir el saldo contado a otra caja
                    </label>
                    @if($transferirAlCerrar)
                    <div class="mt-3">
                        <select wire:model="transferDestinoCierre"
                                class="w-full px-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('transferDestinoCierre') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                            <option value="">Caja destino...</option>
                            @foreach($cajasGenerales as $c)
                                <option value="{{ $c->id }}">{{ $c->nombreEtiqueta() }}</option>
                            @endforeach
                        </select>
                        @error('transferDestinoCierre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-gray-500">Se transferirá el saldo real contado.</p>
                    </div>
                    @endif
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observación del cierre</label>
                    <input wire:model="obs_cierre" type="text" maxlength="200"
                           class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none"
                           placeholder="Opcional...">
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button wire:click="$set('showCierre', false)"
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button wire:click="cerrarCaja"
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                    Confirmar cierre
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal movimiento manual (ajuste ingreso / egreso) --}}
    @if($showAjuste)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-semibold text-gray-900">Registrar movimiento</h3>
                <button wire:click="$set('showAjuste', false)" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-4">

                {{-- Tipo: Ingreso / Egreso --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de movimiento <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" wire:click="$set('ajusteTipo', 'ingreso')"
                                class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border-2 text-sm font-medium transition-colors
                                    {{ $ajusteTipo === 'ingreso' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-[#DDE1E7] text-gray-600 hover:bg-gray-50' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0-16l-4 4m4-4l4 4"/>
                            </svg>
                            Ingreso
                        </button>
                        <button type="button" wire:click="$set('ajusteTipo', 'egreso')"
                                class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border-2 text-sm font-medium transition-colors
                                    {{ $ajusteTipo === 'egreso' ? 'border-red-500 bg-red-50 text-red-700' : 'border-[#DDE1E7] text-gray-600 hover:bg-gray-50' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 20V4m0 16l-4-4m4 4l4-4"/>
                            </svg>
                            Egreso
                        </button>
                    </div>
                    @error('ajusteTipo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Caja --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Caja <span class="text-red-500">*</span></label>
                    <select wire:model="ajusteCajaId"
                            class="w-full px-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('ajusteCajaId') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                        <option value="">Seleccionar caja...</option>
                        @foreach($cajasAbiertas as $c)
                            <option value="{{ $c->id }}">{{ $c->nombreEtiqueta() }}</option>
                        @endforeach
                    </select>
                    @error('ajusteCajaId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Monto --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium">$</span>
                        <input wire:model="ajusteMonto" type="number" min="0.01" step="0.01"
                               class="w-full pl-7 pr-3 py-2.5 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('ajusteMonto') ? 'border-red-300' : 'border-[#DDE1E7]' }}"
                               placeholder="0,00">
                    </div>
                    @error('ajusteMonto') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Descripción --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción <span class="text-red-500">*</span></label>
                    <input wire:model="ajusteDesc" type="text" maxlength="200"
                           class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('ajusteDesc') ? 'border-red-300' : 'border-[#DDE1E7]' }}"
                           placeholder="Motivo del movimiento...">
                    @error('ajusteDesc') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Forma de pago + Fecha --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Forma de pago</label>
                        <select wire:model="ajusteFormaPago"
                                class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="cheque">Cheque</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                        <input wire:model="ajusteFecha" type="date"
                               class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('ajusteFecha') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                        @error('ajusteFecha') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

            </div>

            <div class="flex gap-3 mt-6">
                <button wire:click="$set('showAjuste', false)"
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button wire:click="registrarAjuste"
                        class="{{ $ajusteTipo === 'ingreso' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700' }} flex-1 px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors">
                    Registrar {{ $ajusteTipo === 'ingreso' ? 'ingreso' : 'egreso' }}
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
