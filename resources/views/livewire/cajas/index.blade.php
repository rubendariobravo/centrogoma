<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Caja</h1>
            <p class="text-sm text-gray-500 mt-0.5">Control de apertura y cierre de caja diaria</p>
        </div>
        @if(! $cajaAbierta)
            <button wire:click="$set('showApertura', true)"
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

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg px-4 py-3">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Estado actual --}}
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
                    <p class="font-semibold text-emerald-900">Caja abierta</p>
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

        <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">Ventas</p>
                <p class="font-semibold text-gray-900">${{ number_format($cajaAbierta->total_ventas, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">Cobros</p>
                <p class="font-semibold text-gray-900">${{ number_format($cajaAbierta->total_cobros, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">Gastos</p>
                <p class="font-semibold text-red-600">${{ number_format($cajaAbierta->total_gastos, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg p-3 text-center">
                <p class="text-xs text-gray-500 mb-1">Saldo estimado</p>
                @php
                    $estimado = (float)$cajaAbierta->saldo_inicial
                        + (float)$cajaAbierta->total_ventas
                        + (float)$cajaAbierta->total_cobros
                        - (float)$cajaAbierta->total_gastos;
                @endphp
                <p class="font-bold text-slate-900">${{ number_format($estimado, 2, ',', '.') }}</p>
            </div>
        </div>
    </div>
    @else
    <div class="bg-[#E9ECF0] border border-[#DDE1E7] rounded-xl p-5 mb-6 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <div>
            <p class="font-semibold text-gray-700">Caja cerrada</p>
            <p class="text-sm text-gray-500">No hay una caja abierta. Presione "Abrir caja" para comenzar.</p>
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Apertura / Cierre</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Saldo inicial</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Ventas</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Cobros</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Gastos</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Saldo final</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($historial as $caja)
                    <tr class="hover:bg-[#E9ECF0] transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-900">
                            {{ $caja->fecha_apertura->format('d/m/Y') }}
                        </td>
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
                                @php
                                    $diff = (float)$caja->saldo_final
                                        - ((float)$caja->saldo_inicial + (float)$caja->total_ventas + (float)$caja->total_cobros - (float)$caja->total_gastos);
                                @endphp
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
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-400">
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

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Saldo inicial <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium">$</span>
                        <input wire:model="saldo_inicial" type="number" min="0" step="0.01"
                               class="w-full pl-7 pr-3 py-2.5 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('saldo_inicial') ? 'border-red-300' : 'border-[#DDE1E7]' }}"
                               placeholder="0,00" autofocus>
                    </div>
                    @error('saldo_inicial') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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

            {{-- Resumen --}}
            <div class="bg-[#E9ECF0] rounded-lg p-4 mb-4 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Saldo inicial</span>
                    <span class="font-medium">${{ number_format($cajaAbierta->saldo_inicial, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">+ Ventas</span>
                    <span class="font-medium">${{ number_format($cajaAbierta->total_ventas, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">+ Cobros</span>
                    <span class="font-medium">${{ number_format($cajaAbierta->total_cobros, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">− Gastos</span>
                    <span class="font-medium text-red-600">${{ number_format($cajaAbierta->total_gastos, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between border-t border-[#DDE1E7] pt-2">
                    <span class="font-semibold text-gray-700">Saldo esperado</span>
                    @php
                        $esperado = (float)$cajaAbierta->saldo_inicial
                            + (float)$cajaAbierta->total_ventas
                            + (float)$cajaAbierta->total_cobros
                            - (float)$cajaAbierta->total_gastos;
                    @endphp
                    <span class="font-bold text-slate-900">${{ number_format($esperado, 2, ',', '.') }}</span>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Saldo real contado <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium">$</span>
                        <input wire:model="saldo_final" type="number" min="0" step="0.01"
                               class="w-full pl-7 pr-3 py-2.5 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('saldo_final') ? 'border-red-300' : 'border-[#DDE1E7]' }}"
                               placeholder="0,00">
                    </div>
                    @error('saldo_final') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
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
</div>
