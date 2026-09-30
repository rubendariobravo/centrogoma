<div>
    @php
        $estadoBadges = [
            'cartera'    => 'bg-blue-100 text-blue-700',
            'depositado' => 'bg-indigo-100 text-indigo-700',
            'acreditado' => 'bg-emerald-100 text-emerald-700',
            'rechazado'  => 'bg-red-100 text-red-700',
            'aplicado'   => 'bg-purple-100 text-purple-700',
            'vendido'    => 'bg-amber-100 text-amber-700',
            'anulado'    => 'bg-gray-200 text-gray-500',
            'emitido'    => 'bg-blue-100 text-blue-700',
            'debitado'   => 'bg-emerald-100 text-emerald-700',
        ];
        $vencDot = ['vencido' => 'bg-red-500', 'proximo' => 'bg-amber-500', 'normal' => 'bg-emerald-500'];

        // Encabezado ordenable: clic alterna asc/desc sobre esa columna.
        $th = function (string $field, string $label) use ($sortField, $sortDir) {
            $activo = $sortField === $field;
            $flecha = $activo ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#8645;';
            $color  = $activo ? 'text-slate-700' : 'text-gray-400 group-hover:text-gray-600';

            return '<button type="button" wire:click="sortBy(\'' . $field . '\')"'
                . ' class="group inline-flex items-center gap-1 uppercase tracking-wide '
                . ($activo ? 'text-slate-700' : 'text-gray-500 hover:text-gray-700') . '">'
                . '<span>' . e($label) . '</span>'
                . '<span class="text-[10px] leading-none ' . $color . '">' . $flecha . '</span>'
                . '</button>';
        };
    @endphp

    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Cheques</h1>
            <p class="text-sm text-gray-500 mt-0.5">Cartera de valores — cheques de terceros y propios</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('bancos') }}" wire:navigate
               class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-600 bg-white border border-[#DDE1E7] rounded-lg hover:bg-gray-50 transition-colors">
                Bancos
            </a>
            <button wire:click="openCreate"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo cheque
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg px-4 py-3">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
        {{ session('error') }}
    </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Tipo</label>
            <select wire:model.live="filterTipo" class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                <option value="">Todos</option>
                <option value="tercero">De terceros</option>
                <option value="propio">Propios</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Estado</label>
            <select wire:model.live="filterEstado" class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                <option value="">Todos</option>
                @foreach($estados as $e)
                    <option value="{{ $e }}">{{ \App\Models\Cheque::ESTADO_LABELS[$e] ?? ucfirst($e) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Banco</label>
            <select wire:model.live="filterBanco" class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                <option value="">Todos</option>
                @foreach($bancos as $b)
                    <option value="{{ $b->id }}">{{ $b->descripcion }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Buscar</label>
            <input wire:model.live.debounce.400ms="search" type="text" placeholder="N° o librador"
                   class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Vence desde</label>
            <input wire:model.live="filterDesde" type="date" class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Vence hasta</label>
            <input wire:model.live="filterHasta" type="date" class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-4 py-3 text-left text-xs font-semibold">
                            {!! $th('nro_cheque', 'N°') !!}
                            <span class="text-gray-300 mx-0.5">/</span>
                            {!! $th('banco', 'Banco') !!}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">
                            {!! $th('librador_nombre', 'Librador / ' . ($filterTipo === 'propio' ? 'Proveedor' : ($filterTipo === 'tercero' ? 'Cliente' : 'Contraparte'))) !!}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">{!! $th('fecha_pago', 'Vencimiento') !!}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">{!! $th('monto', 'Monto') !!}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold">
                            @if($filterTipo === '')
                                {!! $th('tipo', 'Tipo') !!}
                                <span class="text-gray-300 mx-0.5">/</span>
                            @endif
                            {!! $th('estado', 'Estado') !!}
                        </th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($cheques as $cheque)
                    <tr class="hover:bg-[#E9ECF0] transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">
                                {{ $cheque->nro_cheque ?: '—' }}
                                @if($cheque->es_echeq)<span class="ml-1 text-xs text-indigo-500 font-semibold">e-cheq</span>@endif
                            </div>
                            <div class="text-xs text-gray-400">{{ $cheque->banco?->descripcion ?? 'Sin banco' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-gray-700">{{ $cheque->librador_nombre ?: '—' }}</div>
                            <div class="text-xs text-gray-400">
                                {{ $cheque->tipo === 'propio' ? ($cheque->proveedor?->razon_social) : ($cheque->cliente?->razon_social) }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @if($cheque->estado_vencimiento)
                                    <span class="w-2 h-2 rounded-full {{ $vencDot[$cheque->estado_vencimiento] ?? 'bg-gray-300' }}"></span>
                                @endif
                                <span class="text-gray-700">{{ $cheque->fecha_pago?->format('d/m/Y') ?? '—' }}</span>
                            </div>
                            @if($cheque->es_diferido)<span class="text-xs text-gray-400">Diferido</span>@endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-800">${{ number_format($cheque->monto, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($filterTipo === '')
                                <span class="inline-flex px-2 py-0.5 mr-1 rounded-full text-xs font-medium {{ $cheque->tipo === 'propio' ? 'bg-slate-200 text-slate-700' : 'bg-cyan-100 text-cyan-700' }}">
                                    {{ $cheque->tipo_label }}
                                </span>
                            @endif
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $estadoBadges[$cheque->estado] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $cheque->estado_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            {{-- Acciones según estado --}}
                            @if($cheque->tipo === 'tercero' && $cheque->estado === 'cartera')
                                <button wire:click="openAction({{ $cheque->id }}, 'depositar')" title="Depositar"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-indigo-500 hover:bg-indigo-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 6h18M9 14l3 3 3-3M12 17V9"/></svg>
                                </button>
                            @endif
                            @if($cheque->tipo === 'propio' && $cheque->estado === 'emitido')
                                <button wire:click="openAction({{ $cheque->id }}, 'debitar')" title="Debitar"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-emerald-600 hover:bg-emerald-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 6h18M9 14l3-3 3 3M12 11v6"/></svg>
                                </button>
                            @endif
                            @if($cheque->tipo === 'tercero' && $cheque->estado === 'depositado')
                                <button wire:click="openAction({{ $cheque->id }}, 'acreditar')" title="Acreditar"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-emerald-600 hover:bg-emerald-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                                <button wire:click="openAction({{ $cheque->id }}, 'rechazar')" title="Rechazar"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-red-500 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"/></svg>
                                </button>
                            @endif
                            <button wire:click="verHistorial({{ $cheque->id }})" title="Historial"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-slate-700 hover:bg-gray-100 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </button>
                            @if(in_array($cheque->estado, ['cartera', 'emitido']))
                                <button wire:click="openEdit({{ $cheque->id }})" title="Editar"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-slate-700 hover:bg-gray-100 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="openAction({{ $cheque->id }}, 'anular')" title="Anular"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">No hay cheques que coincidan con el filtro</td></tr>
                    @endforelse
                </tbody>
                @if($cheques->isNotEmpty())
                <tfoot>
                    <tr class="border-t border-[#DDE1E7] bg-[#E9ECF0]">
                        <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Total filtrado</td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-gray-800">${{ number_format($totalFiltrado, 2, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if($cheques->hasPages())
        <div class="px-4 py-3 border-t border-[#DDE1E7]">{{ $cheques->links() }}</div>
        @endif
    </div>

    {{-- Modal crear / editar --}}
    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-semibold text-gray-900">{{ $chequeId ? 'Editar cheque' : 'Nuevo cheque' }}</h3>
                <button wire:click="resetForm" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo <span class="text-red-500">*</span></label>
                    <select wire:model.live="tipo" class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                        <option value="tercero">De tercero (recibido)</option>
                        <option value="propio">Propio (emitido)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Banco <span class="text-red-500">*</span></label>
                    <select wire:model="banco_id" class="w-full px-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('banco_id') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                        <option value="">Seleccione...</option>
                        @foreach($bancos as $b)<option value="{{ $b->id }}">{{ $b->descripcion }}</option>@endforeach
                    </select>
                    @error('banco_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">N° de cheque <span class="text-red-500">*</span></label>
                    <input wire:model="nro_cheque" type="text" maxlength="30"
                           class="w-full px-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('nro_cheque') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                    @error('nro_cheque') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium">$</span>
                        <input wire:model="monto" type="number" min="0" step="0.01" placeholder="0,00"
                               class="w-full pl-7 pr-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('monto') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                    </div>
                    @error('monto') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Librador (titular)</label>
                    <input wire:model="librador_nombre" type="text" maxlength="150"
                           class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CUIT librador</label>
                    <input wire:model="librador_cuit" type="text" maxlength="13" placeholder="20-12345678-9"
                           class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                </div>
                @if($tipo === 'tercero')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cliente (de quién se recibió)</label>
                    <x-buscador-cliente vacio="Buscar cliente por nombre o CUIT…" />
                </div>
                @else
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor (a quién se entregó)</label>
                    <select wire:model="proveedor_id" class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                        <option value="">—</option>
                        @foreach($proveedores as $p)<option value="{{ $p->id }}">{{ $p->razon_social }}</option>@endforeach
                    </select>
                </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha emisión</label>
                    <input wire:model="fecha_emision" type="date" class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $tipo === 'propio' ? 'Fecha entrega' : 'Fecha recepción' }}</label>
                    <input wire:model="fecha_recepcion" type="date" class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de pago / cobro <span class="text-red-500">*</span></label>
                    <input wire:model="fecha_pago" type="date"
                           class="w-full px-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('fecha_pago') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                    @error('fecha_pago') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end gap-4 pb-1">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input wire:model="es_diferido" type="checkbox" class="rounded border-gray-300 text-slate-900 focus:ring-slate-900"> Diferido
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input wire:model="es_echeq" type="checkbox" class="rounded border-gray-300 text-slate-900 focus:ring-slate-900"> e-cheq
                    </label>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observación</label>
                    <input wire:model="observacion" type="text" maxlength="255"
                           class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button wire:click="resetForm" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Cancelar</button>
                <button wire:click="save" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-800 transition-colors">Guardar cheque</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal de acción de estado --}}
    @if($actionChequeId)
    @php
        $accionLabels = [
            'depositar' => ['Depositar cheque', 'El cheque pasa a "Depositado". Elegí la caja banco de destino para su posterior acreditación.', 'bg-indigo-600 hover:bg-indigo-700'],
            'acreditar' => ['Acreditar cheque', 'Confirmá la acreditación: la plata ingresa a la caja banco seleccionada.', 'bg-emerald-600 hover:bg-emerald-700'],
            'debitar'   => ['Debitar cheque propio', 'El proveedor cobró el cheque: la plata sale de la caja banco seleccionada.', 'bg-emerald-600 hover:bg-emerald-700'],
            'rechazar'  => ['Rechazar cheque', 'El cheque se marca rechazado. Si tiene cliente, se reabre su deuda por el monto del cheque.', 'bg-red-600 hover:bg-red-700'],
            'anular'    => ['Anular cheque', 'El cheque quedará anulado. Esta acción es definitiva.', 'bg-red-600 hover:bg-red-700'],
        ];
        [$titulo, $desc, $btnClass] = $accionLabels[$actionType] ?? ['Acción', '', 'bg-slate-900 hover:bg-slate-800'];
        $necesitaCaja = in_array($actionType, ['depositar', 'acreditar', 'debitar']);
    @endphp
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 p-6 w-full max-w-md">
            <h3 class="font-semibold text-gray-900 mb-2">{{ $titulo }}</h3>
            <p class="text-sm text-gray-500 mb-4">{{ $desc }}</p>

            @if($necesitaCaja)
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Caja banco</label>
                    @if($cajasBanco->isEmpty())
                        <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            No hay cajas banco/general abiertas. Abrí una en Caja y Finanzas → Cajas.
                        </p>
                    @else
                        <select wire:model="actionCajaId" class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                            @foreach($cajasBanco as $cj)<option value="{{ $cj->id }}">{{ $cj->nombreEtiqueta() }}</option>@endforeach
                        </select>
                    @endif
                </div>
            @endif

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Observación {{ $actionType === 'rechazar' ? '' : '(opcional)' }}</label>
                <input wire:model="actionObs" type="text" maxlength="255"
                       class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
            </div>

            <div class="flex gap-3">
                <button wire:click="cancelAction" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Cancelar</button>
                <button wire:click="confirmAction" @disabled($necesitaCaja && $cajasBanco->isEmpty())
                        class="flex-1 px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed {{ $btnClass }}">
                    Confirmar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Panel lateral de historial --}}
    @if($historialChequeId)
    <div class="fixed inset-0 z-40 bg-black/40" wire:click="cerrarHistorial"></div>
    <div class="fixed top-0 right-0 z-50 h-full w-full max-w-md bg-white shadow-2xl overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b border-[#DDE1E7]">
            <h3 class="font-semibold text-gray-900">Historial del cheque</h3>
            <button wire:click="cerrarHistorial" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-5 space-y-3">
            @forelse($historial as $h)
            <div class="border-l-2 border-slate-300 pl-4 pb-1">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-800">{{ $h['tipo_mov'] }}</span>
                    <span class="text-xs text-gray-400">{{ $h['fecha'] }}</span>
                </div>
                <div class="text-xs text-gray-500">Estado: {{ $h['estado'] }} · ${{ $h['monto'] }} · {{ $h['usuario'] }}</div>
                @if($h['obs'])<div class="text-xs text-gray-400 mt-0.5">{{ $h['obs'] }}</div>@endif
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-8">Sin movimientos registrados</p>
            @endforelse
        </div>
    </div>
    @endif
</div>
