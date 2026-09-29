<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('cajas') }}" wire:navigate
               class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    Detalle de caja — {{ $caja->fecha_apertura->format('d/m/Y') }}
                </h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $caja->user?->name ?? '—' }}
                    · Apertura {{ $caja->hora_apertura?->format('H:i') }}
                    @if($caja->hora_cierre)
                        · Cierre {{ $caja->hora_cierre->format('H:i') }}
                    @endif
                </p>
            </div>
        </div>
        @if($caja->estado === 'abierta')
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Abierta
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">
                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                Cerrada
            </span>
        @endif
    </div>

    {{-- KPIs --}}
    @php
        $estimadoBruto = $caja->saldo_esperado;            // todos los medios (sin tocar)
        $efectivoEsp   = $caja->saldo_efectivo_esperado;   // solo efectivo (arqueo real)
        $cobradoSinCc  = $caja->total_cobrado_sin_cc;
        $ventasMedio   = $caja->ventasPorMedio();
        // La diferencia de arqueo se mide contra el efectivo (es lo que se cuenta físicamente).
        $diff = $caja->estado === 'cerrada'
            ? (float) $caja->saldo_final - $efectivoEsp
            : null;
    @endphp

    {{-- Bloque informativo: flujo comercial (todos los medios) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
        <div class="bg-white rounded-xl border border-[#DDE1E7] p-4">
            <p class="text-xs text-gray-500 mb-1">Saldo inicial <span class="text-gray-400">(efectivo)</span></p>
            <p class="text-lg font-bold text-gray-900">${{ number_format($caja->saldo_inicial, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-[#DDE1E7] p-4">
            <p class="text-xs text-gray-500 mb-1">Ventas <span class="text-gray-400">(todos los medios)</span></p>
            <p class="text-lg font-bold text-gray-900">${{ number_format($caja->total_ventas, 2, ',', '.') }}</p>
            @if(count($ventasMedio))
                <p class="text-[11px] text-gray-400 mt-1 leading-tight">
                    @if(($ventasMedio['efectivo'] ?? 0) > 0)<span class="text-emerald-600">Efvo ${{ number_format($ventasMedio['efectivo'], 0, ',', '.') }}</span>@endif
                    @if(($ventasMedio['transferencia'] ?? 0) > 0) · Transf ${{ number_format($ventasMedio['transferencia'], 0, ',', '.') }}@endif
                    @if(($ventasMedio['cuenta_corriente'] ?? 0) > 0) · <span class="text-amber-600">CC ${{ number_format($ventasMedio['cuenta_corriente'], 0, ',', '.') }}</span>@endif
                </p>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-[#DDE1E7] p-4">
            <p class="text-xs text-gray-500 mb-1">Cobros <span class="text-gray-400">(sin cta. cte.)</span></p>
            <p class="text-lg font-bold text-gray-900">${{ number_format($cobradoSinCc, 2, ',', '.') }}</p>
            <p class="text-[11px] text-gray-400 mt-1">Todo lo cobrado salvo cuenta corriente</p>
        </div>
        <div class="bg-white rounded-xl border border-[#DDE1E7] p-4">
            <p class="text-xs text-gray-500 mb-1">Gastos</p>
            <p class="text-lg font-bold text-red-600">${{ number_format($caja->total_gastos, 2, ',', '.') }}</p>
        </div>
    </div>

    {{-- Bloque arqueo de efectivo (destacado) --}}
    <div class="grid grid-cols-2 {{ $caja->estado === 'cerrada' ? 'lg:grid-cols-3' : 'lg:grid-cols-2' }} gap-3 mb-6">
        <div class="bg-white rounded-xl border border-[#DDE1E7] p-4">
            <p class="text-xs text-gray-500 mb-1">Saldo esperado <span class="text-gray-400">(todos los medios)</span></p>
            <p class="text-lg font-bold text-slate-500">${{ number_format($estimadoBruto, 2, ',', '.') }}</p>
            <p class="text-[11px] text-gray-400 mt-1">Incluye banco, tarjeta y cta. cte.</p>
        </div>
        <div class="bg-indigo-50 rounded-xl border-2 border-indigo-300 p-4">
            <p class="text-xs text-indigo-700 font-semibold mb-1 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Saldo en efectivo
            </p>
            <p class="text-xl font-bold text-indigo-900">${{ number_format($efectivoEsp, 2, ',', '.') }}</p>
            <p class="text-[11px] text-indigo-500 mt-1">Lo que debe haber físicamente en la caja</p>
        </div>
        @if($caja->estado === 'cerrada')
        <div class="bg-white rounded-xl border border-[#DDE1E7] p-4">
            <p class="text-xs text-gray-500 mb-1">Saldo real <span class="text-gray-400">(contado)</span></p>
            <p class="text-lg font-bold {{ abs($diff) > 1 ? 'text-red-600' : 'text-gray-900' }}">
                ${{ number_format($caja->saldo_final, 2, ',', '.') }}
            </p>
            @if(abs($diff) > 0.01)
                <p class="text-xs {{ $diff > 0 ? 'text-emerald-600' : 'text-red-600' }} mt-1">
                    {{ $diff > 0 ? 'Sobrante +' : 'Faltante ' }}${{ number_format($diff, 2, ',', '.') }} vs efectivo
                </p>
            @else
                <p class="text-xs text-emerald-600 mt-1">Arqueo OK</p>
            @endif
        </div>
        @endif
    </div>

    @if($caja->observacion)
    <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-2.5 mb-5 text-sm text-amber-800">
        <span class="font-medium">Observación:</span> {{ $caja->observacion }}
    </div>
    @endif

    {{-- Tabs --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="flex border-b border-[#DDE1E7] overflow-x-auto">
            <button wire:click="$set('tab','movimientos')"
                    class="px-5 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                           {{ $tab === 'movimientos' ? 'border-slate-900 text-slate-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Movimientos
                <span class="ml-1.5 px-1.5 py-0.5 rounded text-xs {{ $tab === 'movimientos' ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-500' }}">
                    {{ $movimientos->count() }}
                </span>
            </button>
            <button wire:click="$set('tab','ventas')"
                    class="px-5 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                           {{ $tab === 'ventas' ? 'border-slate-900 text-slate-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Ventas
                <span class="ml-1.5 px-1.5 py-0.5 rounded text-xs {{ $tab === 'ventas' ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-500' }}">
                    {{ $ventas->count() }}
                </span>
            </button>
            <button wire:click="$set('tab','recibos')"
                    class="px-5 py-3 text-sm font-medium border-b-2 transition-colors
                           {{ $tab === 'recibos' ? 'border-slate-900 text-slate-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Cobros
                <span class="ml-1.5 px-1.5 py-0.5 rounded text-xs {{ $tab === 'recibos' ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-500' }}">
                    {{ $recibos->count() }}
                </span>
            </button>
            <button wire:click="$set('tab','pagos')"
                    class="px-5 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                           {{ $tab === 'pagos' ? 'border-slate-900 text-slate-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Pagos recibidos
                <span class="ml-1.5 px-1.5 py-0.5 rounded text-xs {{ $tab === 'pagos' ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-500' }}">
                    {{ $pagos->count() }}
                </span>
            </button>
            <button wire:click="$set('tab','gastos')"
                    class="px-5 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                           {{ $tab === 'gastos' ? 'border-slate-900 text-slate-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Gastos
                <span class="ml-1.5 px-1.5 py-0.5 rounded text-xs {{ $tab === 'gastos' ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-500' }}">
                    {{ $gastos->count() }}
                </span>
            </button>
            <button wire:click="$set('tab','transferencias')"
                    class="px-5 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                           {{ $tab === 'transferencias' ? 'border-slate-900 text-slate-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Transferencias
                <span class="ml-1.5 px-1.5 py-0.5 rounded text-xs {{ $tab === 'transferencias' ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-500' }}">
                    {{ $transferencias->count() }}
                </span>
            </button>
            <button wire:click="$set('tab','ajustes')"
                    class="px-5 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                           {{ $tab === 'ajustes' ? 'border-slate-900 text-slate-900' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Mov. manuales
                <span class="ml-1.5 px-1.5 py-0.5 rounded text-xs {{ $tab === 'ajustes' ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-500' }}">
                    {{ $ajustes->count() }}
                </span>
            </button>
        </div>

        {{-- TAB: Ventas --}}
        @if($tab === 'ventas')
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Nro</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Comprobante</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Cliente</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Saldo</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($ventas as $venta)
                    <tr class="{{ $venta->estado === 'A' ? 'bg-red-50 opacity-60' : 'hover:bg-[#F7F8FA]' }} transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-900 tabular-nums">
                            #{{ str_pad($venta->nro_venta, 8, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            @if($venta->tipo_comprobante === 'nota_credito')
                                <span class="text-xs font-medium text-purple-600">NC {{ $venta->letra_comprobante }}</span>
                            @elseif($venta->tipo_comprobante === 'nota_debito')
                                <span class="text-xs font-medium text-orange-600">ND {{ $venta->letra_comprobante }}</span>
                            @elseif($venta->letra_comprobante)
                                <span class="text-xs font-medium">Fac. {{ $venta->letra_comprobante }}</span>
                            @else
                                <span class="text-xs text-gray-400">Recibo</span>
                            @endif
                            @if($venta->cae)
                                <span class="ml-1 text-xs text-emerald-600 font-mono">✓</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $venta->cliente?->razon_social ?? ($venta->cliente_vtadiaria ?: 'Consumidor Final') }}
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900 tabular-nums">
                            ${{ number_format($venta->total, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums {{ (float)$venta->saldo > 0 ? 'text-amber-600 font-medium' : 'text-gray-400' }}">
                            ${{ number_format($venta->saldo, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($venta->estado === 'A')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">Anulada</span>
                            @elseif($venta->estado === 'C')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Cobrada</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('ventas.show', $venta) }}" wire:navigate
                               class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-slate-700 hover:bg-gray-100 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">
                            No hay ventas registradas en esta caja
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($ventas->count() > 0)
                <tfoot>
                    <tr class="border-t-2 border-[#DDE1E7] bg-[#F7F8FA]">
                        <td colspan="3" class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Total ({{ $ventas->where('estado','!=','A')->count() }} ventas activas)
                        </td>
                        <td class="px-4 py-2.5 text-right font-bold text-gray-900 tabular-nums">
                            ${{ number_format($ventas->where('estado','!=','A')->sum('total'), 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-2.5 text-right font-bold text-amber-600 tabular-nums">
                            ${{ number_format($ventas->where('estado','!=','A')->sum('saldo'), 2, ',', '.') }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @endif

        {{-- TAB: Cobros (Recibos) --}}
        @if($tab === 'recibos')
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Recibo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Forma de pago</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Referencia</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($recibos as $recibo)
                    <tr class="hover:bg-[#F7F8FA] transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-900 tabular-nums">
                            #{{ str_pad($recibo->nro_recibo, 8, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $recibo->fecha->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $recibo->cliente?->razon_social ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 capitalize">
                            {{ $recibo->forma_pago ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ $recibo->referencia ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900 tabular-nums">
                            ${{ number_format($recibo->total, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('recibos.show', $recibo) }}" wire:navigate
                               class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-slate-700 hover:bg-gray-100 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">
                            No hay cobros registrados en esta caja
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($recibos->count() > 0)
                <tfoot>
                    <tr class="border-t-2 border-[#DDE1E7] bg-[#F7F8FA]">
                        <td colspan="5" class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Total ({{ $recibos->count() }} cobros)
                        </td>
                        <td class="px-4 py-2.5 text-right font-bold text-gray-900 tabular-nums">
                            ${{ number_format($recibos->sum('total'), 2, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @endif

        {{-- TAB: Pagos recibidos (PagoVenta) --}}
        @if($tab === 'pagos')
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Venta</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Forma de pago</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Referencia</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Monto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($pagos as $pago)
                    <tr class="hover:bg-[#F7F8FA] transition-colors">
                        <td class="px-4 py-3 text-gray-600">
                            {{ $pago->fecha->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900 tabular-nums">
                            @if($pago->venta)
                                <a href="{{ route('ventas.show', $pago->venta) }}" wire:navigate
                                   class="hover:underline text-slate-700">
                                    #{{ str_pad($pago->venta->nro_venta, 8, '0', STR_PAD_LEFT) }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $pago->venta?->cliente?->razon_social ?? ($pago->venta?->cliente_vtadiaria ?: 'Consumidor Final') }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 capitalize">
                            {{ $pago->forma_pago ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ $pago->referencia ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900 tabular-nums">
                            ${{ number_format($pago->monto, 2, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">
                            No hay pagos registrados en esta caja
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($pagos->count() > 0)
                <tfoot>
                    <tr class="border-t-2 border-[#DDE1E7] bg-[#F7F8FA]">
                        <td colspan="5" class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Total ({{ $pagos->count() }} pagos)
                        </td>
                        <td class="px-4 py-2.5 text-right font-bold text-gray-900 tabular-nums">
                            ${{ number_format($pagos->sum('monto'), 2, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @endif

        {{-- TAB: Movimientos (libro completo) --}}
        @if($tab === 'movimientos')
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Concepto</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Detalle</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Forma pago</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Ingreso</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Egreso</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-indigo-600 uppercase tracking-wide whitespace-nowrap">Efectivo en caja</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    <tr class="bg-[#F7F8FA]">
                        <td colspan="6" class="px-4 py-2 text-xs font-medium text-gray-500">Saldo de apertura</td>
                        <td class="px-4 py-2 text-right text-xs font-semibold text-indigo-700 tabular-nums">
                            ${{ number_format($caja->saldo_inicial, 2, ',', '.') }}
                        </td>
                    </tr>
                    @forelse($movimientos as $mov)
                    @php
                        $esCredito = $mov->concepto === 'venta' && $mov->forma_pago === 'cuenta_corriente';
                        $mueveEfvo = $esEfectivoMov[$mov->id] ?? false;
                        $fpBadge = match($mov->forma_pago) {
                            'efectivo'         => 'bg-emerald-100 text-emerald-700',
                            'transferencia'    => 'bg-sky-100 text-sky-700',
                            'tarjeta'          => 'bg-violet-100 text-violet-700',
                            'cheque'           => 'bg-orange-100 text-orange-700',
                            'cuenta_corriente' => 'bg-amber-100 text-amber-700',
                            default            => 'bg-gray-100 text-gray-500',
                        };
                    @endphp
                    <tr class="hover:bg-[#F7F8FA] transition-colors {{ $esCredito ? 'bg-amber-50/40' : '' }}">
                        <td class="px-4 py-3 text-gray-600">{{ $mov->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 capitalize">
                                {{ str_replace('_', ' ', $mov->concepto) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $mov->descripcion ?: '—' }}
                            @if($esCredito)
                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-700">a crédito · no ingresó efectivo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($mov->forma_pago)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $fpBadge }} capitalize">
                                    {{ str_replace('_', ' ', $mov->forma_pago) }}
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums {{ $mov->tipo === 'ingreso' ? ($esCredito ? 'text-amber-500' : 'text-emerald-600 font-medium') : 'text-gray-300' }}">
                            {{ $mov->tipo === 'ingreso' ? '$' . number_format($mov->monto, 2, ',', '.') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums {{ $mov->tipo === 'egreso' ? 'text-red-600 font-medium' : 'text-gray-300' }}">
                            {{ $mov->tipo === 'egreso' ? '$' . number_format($mov->monto, 2, ',', '.') : '—' }}
                        </td>
                        {{-- Saldo del cajón después de este movimiento. Los que no son
                             efectivo (crédito, transferencia bancaria, tarjeta) lo dejan
                             igual: se muestra en gris para que se vea que no lo movieron. --}}
                        <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap {{ $mueveEfvo ? 'font-semibold text-indigo-800' : 'text-gray-400' }}">
                            ${{ number_format($saldoCorrido[$mov->id] ?? 0, 2, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">
                            No hay movimientos registrados en esta caja
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($movimientos->count() > 0)
                @php $resMov = $caja->resumenMovimientos(); @endphp
                <tfoot>
                    <tr class="border-t-2 border-[#DDE1E7] bg-[#F7F8FA]">
                        <td colspan="4" class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Totales ({{ $movimientos->count() }} movimientos)
                        </td>
                        <td class="px-4 py-2.5 text-right font-bold text-emerald-600 tabular-nums">
                            ${{ number_format($movimientos->where('tipo','ingreso')->sum('monto'), 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-2.5 text-right font-bold text-red-600 tabular-nums">
                            ${{ number_format($movimientos->where('tipo','egreso')->sum('monto'), 2, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                    <tr class="bg-indigo-50/60 border-t border-indigo-100">
                        <td colspan="4" class="px-4 py-2 text-[11px] font-semibold text-indigo-700 uppercase tracking-wide">
                            De los cuales, en efectivo físico
                        </td>
                        <td class="px-4 py-2 text-right font-semibold text-indigo-700 tabular-nums">
                            ${{ number_format($resMov['efectivo_ingreso'], 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-2 text-right font-semibold text-indigo-700 tabular-nums">
                            ${{ number_format($resMov['efectivo_egreso'], 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-2 text-right font-bold text-indigo-900 tabular-nums whitespace-nowrap">
                            ${{ number_format($caja->saldo_efectivo_esperado, 2, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @endif

        {{-- TAB: Gastos --}}
        @if($tab === 'gastos')
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Categoría</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Descripción</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Forma pago</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Monto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($gastos as $gasto)
                    <tr class="hover:bg-[#F7F8FA] transition-colors">
                        <td class="px-4 py-3 text-gray-600">{{ $gasto->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $gasto->categoria?->nombre ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $gasto->descripcion ?: '—' }}
                            @if($gasto->proveedor)<div class="text-xs text-gray-400">{{ $gasto->proveedor->razon_social }}</div>@endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 capitalize">{{ $gasto->forma_pago }}</td>
                        <td class="px-4 py-3 text-right font-medium text-red-600 tabular-nums">${{ number_format($gasto->monto, 2, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">
                            No hay gastos registrados en esta caja
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($gastos->count() > 0)
                <tfoot>
                    <tr class="border-t-2 border-[#DDE1E7] bg-[#F7F8FA]">
                        <td colspan="4" class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Total ({{ $gastos->count() }} gastos)
                        </td>
                        <td class="px-4 py-2.5 text-right font-bold text-red-600 tabular-nums">
                            ${{ number_format($gastos->sum('monto'), 2, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @endif

        {{-- TAB: Transferencias --}}
        @if($tab === 'transferencias')
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Sentido</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Contraparte</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Descripción</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Monto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($transferencias as $t)
                    @php $esSalida = $t->caja_origen_id === $caja->id; @endphp
                    <tr class="hover:bg-[#F7F8FA] transition-colors">
                        <td class="px-4 py-3 text-gray-600">{{ $t->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($esSalida)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">Salida</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Entrada</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $esSalida ? $t->cajaDestino?->nombreEtiqueta() : $t->cajaOrigen?->nombreEtiqueta() }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $t->descripcion ?: '—' }}</td>
                        <td class="px-4 py-3 text-right font-medium tabular-nums {{ $esSalida ? 'text-red-600' : 'text-emerald-600' }}">
                            {{ $esSalida ? '−' : '+' }}${{ number_format($t->monto, 2, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">
                            No hay transferencias para esta caja
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        {{-- TAB: Movimientos manuales (concepto = ajuste) --}}
        @if($tab === 'ajustes')
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Descripción</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Forma pago</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Usuario</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Ingreso</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Egreso</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($ajustes as $mov)
                    @php
                        $fpBadge = match($mov->forma_pago) {
                            'efectivo'      => 'bg-emerald-100 text-emerald-700',
                            'transferencia' => 'bg-sky-100 text-sky-700',
                            'tarjeta'       => 'bg-violet-100 text-violet-700',
                            'cheque'        => 'bg-orange-100 text-orange-700',
                            default         => 'bg-gray-100 text-gray-500',
                        };
                    @endphp
                    <tr class="hover:bg-[#F7F8FA] transition-colors">
                        <td class="px-4 py-3 text-gray-600">{{ $mov->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            @if($mov->tipo === 'ingreso')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Ingreso</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">Egreso</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $mov->descripcion ?: '—' }}</td>
                        <td class="px-4 py-3">
                            @if($mov->forma_pago)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $fpBadge }} capitalize">
                                    {{ str_replace('_', ' ', $mov->forma_pago) }}
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $mov->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums {{ $mov->tipo === 'ingreso' ? 'text-emerald-600 font-medium' : 'text-gray-300' }}">
                            {{ $mov->tipo === 'ingreso' ? '$' . number_format($mov->monto, 2, ',', '.') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums {{ $mov->tipo === 'egreso' ? 'text-red-600 font-medium' : 'text-gray-300' }}">
                            {{ $mov->tipo === 'egreso' ? '$' . number_format($mov->monto, 2, ',', '.') : '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">
                            No hay movimientos manuales registrados en esta caja
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($ajustes->count() > 0)
                <tfoot>
                    <tr class="border-t-2 border-[#DDE1E7] bg-[#F7F8FA]">
                        <td colspan="5" class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Totales ({{ $ajustes->count() }} movimientos)
                        </td>
                        <td class="px-4 py-2.5 text-right font-bold text-emerald-600 tabular-nums">
                            ${{ number_format($ajustes->where('tipo','ingreso')->sum('monto'), 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-2.5 text-right font-bold text-red-600 tabular-nums">
                            ${{ number_format($ajustes->where('tipo','egreso')->sum('monto'), 2, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @endif
    </div>
</div>
