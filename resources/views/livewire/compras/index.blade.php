<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Compras</h1>
            <p class="text-sm text-gray-500 mt-0.5">Registro de compras y facturas de proveedores</p>
        </div>
        <a href="{{ route('compras.create') }}" wire:navigate
           class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva compra
        </a>
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

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <div class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[180px]">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" type="text"
                           placeholder="Proveedor o Nº de factura..."
                           class="w-full pl-9 pr-4 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
            </div>
            <select wire:model.live="filterProveedor"
                    class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                <option value="">Todos los proveedores</option>
                @foreach($proveedores as $p)
                    <option value="{{ $p->id }}">{{ $p->razon_social }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-500">Desde</label>
                <input wire:model.live="filterDesde" type="date"
                       class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-500">Hasta</label>
                <input wire:model.live="filterHasta" type="date"
                       class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
            </div>
            <select wire:model.live="filterEstado"
                    class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                <option value="">Todos los estados</option>
                <option value="P">Pendiente</option>
                <option value="C">Pagada</option>
                <option value="A">Anulada</option>
            </select>
        </div>
    </div>

    {{-- Resumen período --}}
    <div class="bg-white rounded-xl border border-[#DDE1E7] px-4 py-3 mb-4 flex items-center justify-between">
        <span class="text-sm text-gray-500">Total del período</span>
        <span class="text-lg font-bold text-gray-900">${{ number_format($totalPeriodo, 2, ',', '.') }}</span>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Nº Factura</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Proveedor</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Neto</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">IVA</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Saldo</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($compras as $compra)
                    <tr class="hover:bg-[#E9ECF0] transition-colors group">
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">
                            @if($compra->saldo_inicial)
                                <span class="ml-1 inline-flex whitespace-nowrap px-1.5 py-0.5 rounded text-[10px] font-sans font-medium bg-slate-100 text-slate-600">Saldo inicial</span>
                            @elseif($compra->nro_factura)
                                <span class="font-medium text-gray-800">{{ $compra->letra }}-{{ $compra->nro_factura }}</span>
                            @else
                                <span class="text-gray-400">Sin número</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $compra->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            @if($compra->proveedor)
                                <div class="font-medium text-gray-900">{{ $compra->proveedor->razon_social }}</div>
                                @if($compra->proveedor->nombre_fantasia && $compra->proveedor->nombre_fantasia !== $compra->proveedor->razon_social)
                                    <div class="text-xs text-gray-400">{{ $compra->proveedor->nombre_fantasia }}</div>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">
                            ${{ number_format(($compra->neto_21 ?? 0) + ($compra->neto_10_5 ?? 0) + ($compra->neto ?? 0), 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">
                            ${{ number_format(($compra->monto_iva21 ?? 0) + ($compra->monto_iva10_5 ?? 0), 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">
                            ${{ number_format($compra->total, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @php $saldo = (float) $compra->saldo; @endphp
                            <span class="{{ $saldo > 0.01 ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                                ${{ number_format($saldo, 2, ',', '.') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($compra->estado === 'C')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Pagada</span>
                            @elseif($compra->estado === 'A')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">Anulada</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('compras.show', $compra->id) }}" wire:navigate
                                   class="p-1.5 text-gray-400 hover:text-slate-900 hover:bg-gray-100 rounded-md transition-colors" title="Ver">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                @if($compra->estado !== 'A')
                                <button type="button" wire:click="confirmarAnular({{ $compra->id }})"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Anular">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-16 text-center text-gray-400">
                            <p class="text-sm">No se encontraron compras en el período</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($compras->hasPages())
        <div class="px-4 py-3 border-t border-[#DDE1E7]">
            {{ $compras->links() }}
        </div>
        @endif
    </div>

    {{-- Modal anular --}}
    @if($anularId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:keydown.escape.window="cancelarAnular">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h2 class="text-base font-semibold text-gray-900">Anular compra #{{ $anularId }}</h2>
            <p class="mt-1 text-sm text-gray-500">Se revierte el stock y la cuenta corriente del proveedor (o el egreso de caja si fue de contado). El costo de los artículos no se modifica.</p>

            @if(count($anularPagos))
            <div class="mt-4 bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-lg px-3 py-2">
                <p class="font-medium">Esta compra ya tiene pagos aplicados (${{ $anularPagado }}):</p>
                <ul class="mt-1 list-disc list-inside">
                    @foreach($anularPagos as $p)
                        <li>OP #{{ $p['nro'] }} del {{ $p['fecha'] }} — ${{ $p['monto'] }}</li>
                    @endforeach
                </ul>
                <p class="mt-1">Ese importe quedará como crédito a favor con el proveedor.</p>
            </div>
            @endif

            <label class="block mt-4 text-xs font-medium text-gray-600 mb-1">Motivo</label>
            <textarea wire:model="motivoAnulacion" rows="2"
                      class="w-full px-3 py-2 text-sm border {{ $errors->has('motivoAnulacion') ? 'border-red-400' : 'border-[#DDE1E7]' }} rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none"></textarea>
            @error('motivoAnulacion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" wire:click="cancelarAnular"
                        class="px-4 py-2 text-sm text-gray-700 bg-white border border-[#DDE1E7] rounded-lg hover:bg-gray-50">Cancelar</button>
                <button type="button" wire:click="anular" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Anular compra</button>
            </div>
        </div>
    </div>
    @endif
</div>
