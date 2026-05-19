<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Cuentas corrientes proveedores</h1>
            <p class="text-sm text-gray-500 mt-0.5">Saldos y movimientos de proveedores</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('compras.create') }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 text-white text-sm font-medium rounded-lg hover:bg-slate-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva compra
            </a>
            <a href="{{ route('ordenes-pago.create') }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Registrar pago
            </a>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <div class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[180px]">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" type="text"
                           placeholder="Buscar proveedor..."
                           class="w-full pl-9 pr-4 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
            </div>
            <div class="flex rounded-lg shadow-sm overflow-hidden text-sm">
                <button wire:click="$set('filterSaldo', 'deudores')"
                        class="px-3 py-2 {{ $filterSaldo === 'deudores' ? 'bg-slate-900 text-white' : 'text-gray-600 hover:bg-[#E9ECF0]' }} transition-colors">
                    Con deuda
                </button>
                <button wire:click="$set('filterSaldo', 'all')"
                        class="px-3 py-2 {{ $filterSaldo === 'all' ? 'bg-slate-900 text-white' : 'text-gray-600 hover:bg-[#E9ECF0]' }} transition-colors border-x border-[#DDE1E7]">
                    Todos
                </button>
                <button wire:click="$set('filterSaldo', 'saldado')"
                        class="px-3 py-2 {{ $filterSaldo === 'saldado' ? 'bg-slate-900 text-white' : 'text-gray-600 hover:bg-[#E9ECF0]' }} transition-colors">
                    Saldados
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">

        {{-- Lista de proveedores --}}
        <div class="xl:col-span-2">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Proveedor</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Deuda</th>
                                <th class="px-4 py-3 w-8"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E9ECF0]">
                            @forelse($proveedores as $proveedor)
                            <tr wire:click="seleccionarProveedor({{ $proveedor->id }})"
                                class="cursor-pointer hover:bg-[#E9ECF0] transition-colors
                                    {{ $proveedorSeleccionado === $proveedor->id ? 'bg-blue-50 hover:bg-blue-50' : '' }}">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 text-sm">{{ $proveedor->razon_social }}</div>
                                    @if($proveedor->nombre_fantasia && $proveedor->nombre_fantasia !== $proveedor->razon_social)
                                        <div class="text-xs text-gray-400">{{ $proveedor->nombre_fantasia }}</div>
                                    @endif
                                    @if($proveedor->cuit)
                                        <div class="text-xs text-gray-400">{{ $proveedor->cuit }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-bold
                                    {{ (float)$proveedor->saldo > 0 ? 'text-amber-600' : 'text-gray-400' }}">
                                    ${{ number_format($proveedor->saldo, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <svg class="w-4 h-4 text-gray-400 {{ $proveedorSeleccionado === $proveedor->id ? 'rotate-90' : '' }} transition-transform"
                                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-4 py-12 text-center text-gray-400 text-sm">
                                    No hay proveedores con deuda
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($proveedores->hasPages())
                <div class="px-4 py-3 border-t border-[#DDE1E7]">
                    {{ $proveedores->links() }}
                </div>
                @endif
            </div>
        </div>

        {{-- Movimientos del proveedor seleccionado --}}
        <div class="xl:col-span-3">
            @if($proveedorSeleccionado)
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-[#DDE1E7] flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-700">Movimientos</h2>
                        <p class="text-xs text-gray-400">Últimos 50 registros</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($saldoSeleccionado > 0)
                        <a href="{{ route('ordenes-pago.create', ['proveedor_id' => $proveedorSeleccionado]) }}" wire:navigate
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 text-white text-xs font-medium rounded-lg hover:bg-amber-500 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Registrar pago
                        </a>
                        @endif
                        <div class="text-right">
                            <p class="text-xs text-gray-400">Deuda actual</p>
                            <p class="text-base font-bold {{ $saldoSeleccionado > 0 ? 'text-amber-600' : 'text-gray-500' }}">
                                ${{ number_format($saldoSeleccionado, 2, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
                @if(count($movimientos) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0] text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                <th class="px-4 py-2 text-left">Fecha</th>
                                <th class="px-4 py-2 text-left">Concepto</th>
                                <th class="px-4 py-2 text-right">Debe</th>
                                <th class="px-4 py-2 text-right">Haber</th>
                                <th class="px-4 py-2 text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E9ECF0]">
                            @foreach($movimientos as $mov)
                            <tr class="hover:bg-[#E9ECF0]">
                                <td class="px-4 py-2 text-gray-600 text-xs">{{ $mov['fecha'] }}</td>
                                <td class="px-4 py-2 text-gray-800">{{ $mov['concepto'] }}</td>
                                <td class="px-4 py-2 text-right {{ $mov['debe'] > 0 ? 'text-amber-600 font-medium' : 'text-gray-300' }}">
                                    {{ $mov['debe'] > 0 ? '$'.number_format($mov['debe'], 2, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-2 text-right {{ $mov['haber'] > 0 ? 'text-emerald-600 font-medium' : 'text-gray-300' }}">
                                    {{ $mov['haber'] > 0 ? '$'.number_format($mov['haber'], 2, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-2 text-right font-medium {{ $mov['saldo'] > 0 ? 'text-amber-600' : 'text-gray-700' }}">
                                    ${{ number_format($mov['saldo'], 2, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="px-4 py-12 text-center text-gray-400 text-sm">
                    No hay movimientos registrados
                </div>
                @endif
            </div>
            @else
            <div class="bg-white rounded-xl border border-[#DDE1E7] px-4 py-16 text-center text-gray-300">
                <svg class="w-10 h-10 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                <p class="text-sm">Seleccione un proveedor para ver su cuenta corriente</p>
            </div>
            @endif
        </div>
    </div>
</div>
