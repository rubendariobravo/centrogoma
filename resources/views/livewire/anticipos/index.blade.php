<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Anticipos de Sueldo</h1>
            <p class="text-sm text-gray-500 mt-0.5">Registro y seguimiento de anticipos al personal</p>
        </div>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo anticipo
        </button>
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
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('error') }}
    </div>
    @endif

    {{-- Banner empleado filtrado --}}
    @if($empleadoFiltrado)
    <div class="bg-indigo-50 border border-indigo-200 rounded-xl px-4 py-3 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-indigo-200 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-indigo-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div>
                <p class="font-medium text-indigo-900 text-sm">{{ $empleadoFiltrado->nombre_completo }}</p>
                <p class="text-xs text-indigo-600">{{ ucfirst($empleadoFiltrado->rol) }}</p>
            </div>
        </div>
        <a href="{{ route('anticipos-empleados') }}" wire:navigate
           class="text-xs text-indigo-600 hover:text-indigo-800 underline underline-offset-2">
            Ver todos los empleados
        </a>
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
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar empleado..."
                           class="w-full pl-9 pr-4 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
            </div>
            @if(!$filterEmpleado)
            <select wire:model.live="filterEmpleado"
                    class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                <option value="">Todos los empleados</option>
                @foreach($empleados as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->nombre_completo }}</option>
                @endforeach
            </select>
            @endif
            <select wire:model.live="filterEstado"
                    class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendiente</option>
                <option value="aprobado">Aprobado</option>
                <option value="rechazado">Rechazado</option>
                <option value="liquidado">Liquidado</option>
            </select>
            <select wire:model.live="filterFormaPago"
                    class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                <option value="">Todas las formas</option>
                @foreach($formasPago as $fp)
                    <option value="{{ $fp->codigo }}">{{ $fp->descripcion }}</option>
                @endforeach
            </select>
            <input wire:model.live="fechaDesde" type="date"
                   class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
            <input wire:model.live="fechaHasta" type="date"
                   class="text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Empleado</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Monto</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Forma pago</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#E9ECF0]">
                @forelse($anticipos as $anticipo)
                @php
                    $estadoConfig = [
                        'pendiente'  => ['bg-amber-100 text-amber-700',  'Pendiente'],
                        'aprobado'   => ['bg-blue-100 text-blue-700',    'Aprobado'],
                        'rechazado'  => ['bg-red-100 text-red-700',      'Rechazado'],
                        'liquidado'  => ['bg-emerald-100 text-emerald-700','Liquidado'],
                    ];
                    [$estadoClass, $estadoLabel] = $estadoConfig[$anticipo->estado] ?? ['bg-gray-100 text-gray-600', $anticipo->estado];
                @endphp
                <tr class="hover:bg-[#E9ECF0] transition-colors group">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $anticipo->empleado?->nombre_completo ?? '—' }}</div>
                        @if($anticipo->referencia)
                            <div class="text-xs text-gray-400 font-mono">Ref: {{ $anticipo->referencia }}</div>
                        @endif
                        @if($anticipo->observaciones)
                            <div class="text-xs text-gray-400 truncate max-w-xs">{{ $anticipo->observaciones }}</div>
                        @endif
                        @if($anticipo->estado === 'rechazado' && $anticipo->motivo_rechazo)
                            <div class="text-xs text-red-500 mt-0.5">Motivo: {{ $anticipo->motivo_rechazo }}</div>
                        @endif
                        @if(in_array($anticipo->estado, ['aprobado', 'rechazado', 'liquidado']) && $anticipo->aprobador)
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ $anticipo->estado === 'rechazado' ? 'Rechazado' : 'Aprobado' }} por {{ $anticipo->aprobador->name }}
                                @if($anticipo->fecha_aprobacion)
                                    el {{ $anticipo->fecha_aprobacion->format('d/m/Y') }}
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600">
                        {{ $anticipo->fecha->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900">
                        ${{ number_format($anticipo->monto, 2, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600">
                        @if($anticipo->esMixto())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700"
                                  title="{{ $anticipo->pagos->map(fn ($p) => ucfirst($p->forma_pago) . ' $' . number_format($p->monto, 2, ',', '.'))->join(' · ') }}">
                                {{ $anticipo->formaPagoEtiqueta() }}
                            </span>
                            <div class="text-xs text-gray-400 mt-0.5">
                                @foreach($anticipo->pagos as $p)
                                    <div>{{ ucfirst($p->forma_pago) }} ${{ number_format($p->monto, 2, ',', '.') }}</div>
                                @endforeach
                            </div>
                        @else
                            {{ $anticipo->formaPagoEtiqueta() }}
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $estadoClass }}">
                            {{ $estadoLabel }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            @if($anticipo->estado === 'pendiente')
                                <button wire:click="openEdit({{ $anticipo->id }})"
                                        title="Editar"
                                        class="p-1.5 text-gray-400 hover:text-slate-900 hover:bg-gray-100 rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button wire:click="aprobar({{ $anticipo->id }})"
                                        wire:confirm="¿Aprobar este anticipo?"
                                        title="Aprobar"
                                        class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>
                                <button wire:click="openRechazar({{ $anticipo->id }})"
                                        title="Rechazar"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>
                                <button wire:click="confirmDelete({{ $anticipo->id }})"
                                        title="Eliminar"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            @elseif($anticipo->estado === 'aprobado')
                                <button wire:click="liquidar({{ $anticipo->id }})"
                                        wire:confirm="¿Marcar este anticipo como liquidado? Indica que fue descontado del sueldo."
                                        title="Liquidar"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-emerald-700 bg-emerald-100 hover:bg-emerald-200 rounded-md transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Liquidar
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-16 text-center text-gray-400">
                        <p class="text-sm">No se encontraron anticipos</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($anticipos->total() > 0)
            <tfoot>
                <tr class="bg-[#E9ECF0] border-t-2 border-[#DDE1E7]">
                    <td class="px-4 py-3 text-xs text-gray-500">
                        {{ $anticipos->total() }} {{ $anticipos->total() === 1 ? 'registro' : 'registros' }}
                    </td>
                    <td></td>
                    <td class="px-4 py-3 text-right font-bold text-gray-900">
                        ${{ number_format($totalMonto, 2, ',', '.') }}
                    </td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            @endif
        </table>

        @if($anticipos->hasPages())
        <div class="px-4 py-3 border-t border-[#DDE1E7]">
            {{ $anticipos->links() }}
        </div>
        @endif
    </div>

    {{-- Modal crear/editar --}}
    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 w-full max-w-2xl flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#DDE1E7] shrink-0">
                <h3 class="font-semibold text-gray-900">{{ $anticipoId ? 'Editar anticipo' : 'Nuevo anticipo' }}</h3>
                <button wire:click="$set('showForm', false)" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                {{-- Empleado --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Empleado <span class="text-red-500">*</span></label>
                    <select wire:model="empleado_id"
                            @if($anticipoId) disabled @endif
                            class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white {{ $errors->has('empleado_id') ? 'border-red-300' : 'border-[#DDE1E7]' }} {{ $anticipoId ? 'bg-gray-50 text-gray-500' : '' }}">
                        <option value="">Seleccionar empleado...</option>
                        @foreach($empleados as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->nombre_completo }}</option>
                        @endforeach
                    </select>
                    @error('empleado_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Fecha y Monto --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Fecha <span class="text-red-500">*</span></label>
                        <input wire:model="fecha" type="date"
                               class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('fecha') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                        @error('fecha') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Monto <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                            <input wire:model.blur="monto" type="number" min="0.01" step="0.01"
                                   class="w-full pl-6 pr-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('monto') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                        </div>
                        @error('monto') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Formas de pago (multi-medio: mitad efectivo / mitad transferencia) --}}
                <div class="border border-[#DDE1E7] rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-3 py-2 bg-[#E9ECF0] border-b border-[#DDE1E7]">
                        <span class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Formas de pago</span>
                        <button type="button" wire:click="agregarPago"
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-slate-700 bg-white border border-[#DDE1E7] rounded-md hover:bg-gray-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Agregar medio
                        </button>
                    </div>

                    <div class="p-3 space-y-2">
                        @error('pagos') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                        @foreach($pagos as $i => $pago)
                        <div class="grid grid-cols-12 gap-2 items-start">
                            {{-- Forma --}}
                            <div class="col-span-3">
                                <select wire:model.live="pagos.{{ $i }}.forma_pago"
                                        class="w-full px-2 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white {{ $errors->has("pagos.$i.forma_pago") ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                                    @foreach($formasPago as $fp)
                                        <option value="{{ $fp->codigo }}">{{ $fp->descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Caja: cada medio sale de su propia caja (efvo del cajón, transf. del banco) --}}
                            <div class="col-span-4">
                                <select wire:model.live="pagos.{{ $i }}.caja_id"
                                        class="w-full px-2 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white {{ $errors->has("pagos.$i.caja_id") ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                                    <option value="">Caja...</option>
                                    @foreach($cajas as $c)
                                        <option value="{{ $c->id }}">{{ $c->nombreEtiqueta() }} — efvo ${{ number_format($c->saldo_efectivo_esperado, 2, ',', '.') }}</option>
                                    @endforeach
                                </select>
                                @error("pagos.$i.caja_id") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            {{-- Monto --}}
                            <div class="col-span-2">
                                <div class="relative">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                                    <input wire:model.blur="pagos.{{ $i }}.monto" type="number" min="0.01" step="0.01"
                                           class="w-full pl-5 pr-2 py-2 text-sm text-right border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has("pagos.$i.monto") ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                                </div>
                            </div>

                            {{-- Referencia --}}
                            <div class="col-span-2">
                                <input wire:model.blur="pagos.{{ $i }}.referencia" type="text" maxlength="100" placeholder="Ref."
                                       class="w-full px-2 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none font-mono">
                            </div>

                            {{-- Quitar --}}
                            <div class="col-span-1 flex justify-end pt-1">
                                @if(count($pagos) > 1)
                                <button type="button" wire:click="quitarPago({{ $i }})" title="Quitar medio"
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </div>
                        @endforeach

                        {{-- Cuadre --}}
                        <div class="flex items-center justify-between pt-2 border-t border-[#E9ECF0] text-xs">
                            <span class="text-gray-500">
                                Asignado <span class="font-semibold text-gray-900">${{ number_format($this->totalPagos, 2, ',', '.') }}</span>
                                de ${{ number_format((float) ($monto ?: 0), 2, ',', '.') }}
                            </span>
                            @if($this->totalPagos <= 0)
                                {{-- Modal recién abierto: todavía no hay nada que cuadrar --}}
                                <span class="text-gray-400">Sin importes asignados</span>
                            @elseif(abs($this->restoPagos) < 0.009)
                                <span class="inline-flex items-center gap-1 font-medium text-emerald-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Cuadra
                                </span>
                            @elseif($this->restoPagos > 0)
                                <button type="button" wire:click="completarResto({{ count($pagos) - 1 }})"
                                        class="inline-flex items-center gap-1 px-2 py-1 font-medium text-amber-700 bg-amber-100 hover:bg-amber-200 rounded-md transition-colors">
                                    Falta ${{ number_format($this->restoPagos, 2, ',', '.') }} — asignar
                                </button>
                            @else
                                <span class="font-medium text-red-600">
                                    Excede por ${{ number_format(abs($this->restoPagos), 2, ',', '.') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                @if($cajas->isEmpty())
                <div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-xs text-amber-700">
                    No hay cajas abiertas. Abrí tu caja diaria (o pedí una caja general) para poder descontar el anticipo.
                </div>
                @endif

                {{-- Observaciones --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Observaciones</label>
                    <textarea wire:model="observaciones" rows="2" maxlength="1000"
                              class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none resize-none"></textarea>
                </div>
            </div>

            <div class="flex gap-3 px-6 py-4 border-t border-[#DDE1E7]">
                <button wire:click="$set('showForm', false)"
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button wire:click="save"
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-700 transition-colors">
                    Guardar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal rechazo --}}
    @if($showRechazarModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 w-full max-w-sm">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-[#DDE1E7]">
                <div class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900">Rechazar anticipo</h3>
            </div>
            <div class="px-6 py-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">Motivo del rechazo <span class="text-red-500">*</span></label>
                <textarea wire:model="motivoRechazo" rows="3" maxlength="500"
                          placeholder="Indicar el motivo..."
                          class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none resize-none {{ $errors->has('motivoRechazo') ? 'border-red-300' : 'border-[#DDE1E7]' }}"></textarea>
                @error('motivoRechazo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-3 px-6 py-4 border-t border-[#DDE1E7]">
                <button wire:click="$set('showRechazarModal', false)"
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button wire:click="rechazar"
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                    Rechazar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal confirmación eliminar --}}
    @if($confirmingDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 p-6 max-w-sm w-full">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">¿Eliminar anticipo?</h3>
                    <p class="text-sm text-gray-500">Esta acción no se puede deshacer.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <button wire:click="cancelDelete" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button wire:click="delete" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                    Eliminar
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
