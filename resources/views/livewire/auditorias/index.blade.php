<div class="p-6 space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Auditorías</h1>
            <p class="text-sm text-slate-500 mt-0.5">Registro de todas las operaciones realizadas en el sistema</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-3">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="lg:col-span-2">
                <input wire:model.live.debounce.400ms="search" type="text"
                       placeholder="Buscar en cambios..."
                       class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent">
            </div>

            <div>
                <select wire:model.live="filterEvento"
                        class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <option value="">Todos los eventos</option>
                    <option value="created">Creación</option>
                    <option value="updated">Modificación</option>
                    <option value="deleted">Eliminación</option>
                    <option value="restored">Restauración</option>
                </select>
            </div>

            <div>
                <select wire:model.live="filterModulo"
                        class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <option value="">Todos los módulos</option>
                    @foreach($moduloLabels as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="filterUser"
                        class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <option value="">Todos los usuarios</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <input wire:model.live="filterDesde" type="date"
                       class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
            </div>
        </div>

        <div class="flex items-center gap-3">
            <input wire:model.live="filterHasta" type="date"
                   class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">

            @if($search || $filterEvento || $filterModulo || $filterUser || $filterDesde || $filterHasta)
                <button wire:click="clearFilters"
                        class="text-sm text-slate-500 hover:text-slate-800 flex items-center gap-1 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Limpiar filtros
                </button>
            @endif
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Fecha / Hora</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Usuario</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Evento</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Módulo</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Entidad</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Cambios</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($audits as $audit)
                    @php
                        $modelInfo = $modelMap[$audit->auditable_type] ?? ['label' => class_basename($audit->auditable_type), 'modulo' => ''];
                        $moduloLabel = $moduloLabels[$modelInfo['modulo']] ?? $modelInfo['modulo'];
                        $changesCount = max(count($audit->old_values ?? []), count($audit->new_values ?? []));
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                            <span class="font-medium text-slate-800">{{ $audit->created_at->format('d/m/Y') }}</span>
                            <span class="text-slate-400 ml-1">{{ $audit->created_at->format('H:i') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-amber-500 text-slate-900 flex items-center justify-center text-xs font-bold uppercase flex-shrink-0">
                                    {{ substr($audit->user?->name ?? 'S', 0, 1) }}
                                </div>
                                <span class="text-slate-700">{{ $audit->user?->name ?? 'Sistema' }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if($audit->event === 'created')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    Creación
                                </span>
                            @elseif($audit->event === 'updated')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Modificación
                                </span>
                            @elseif($audit->event === 'deleted')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Eliminación
                                </span>
                            @elseif($audit->event === 'restored')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Restauración
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $moduloLabel }}</td>
                        <td class="px-4 py-3">
                            <span class="font-medium text-slate-800">{{ $modelInfo['label'] }}</span>
                            <span class="text-slate-400 ml-1">#{{ $audit->auditable_id }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">
                            @if($changesCount > 0)
                                <span class="inline-flex items-center gap-1 text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">
                                    {{ $changesCount }} {{ $changesCount === 1 ? 'campo' : 'campos' }}
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="verDetalle({{ $audit->id }})"
                                    class="text-xs text-amber-600 hover:text-amber-800 font-medium transition-colors">
                                Ver detalle
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                            <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            No hay registros de auditoría
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($audits->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $audits->links() }}
            </div>
        @endif
    </div>

    {{-- Total registros --}}
    @if($audits->total() > 0)
        <p class="text-xs text-slate-400 text-right">
            {{ number_format($audits->total()) }} registros en total
        </p>
    @endif

</div>

{{-- Modal Detalle --}}
@if($auditDetalle)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-data
         x-init="$el.querySelector('[data-overlay]').style.opacity = 0; setTimeout(() => $el.querySelector('[data-overlay]').style.opacity = 1, 0)">

        <div data-overlay
             class="absolute inset-0 bg-slate-900/50 transition-opacity duration-200"
             wire:click="cerrarDetalle"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">

            {{-- Header modal --}}
            @php
                $evColors = [
                    'created'  => 'from-green-500 to-green-600',
                    'updated'  => 'from-blue-500 to-blue-600',
                    'deleted'  => 'from-red-500 to-red-600',
                    'restored' => 'from-amber-500 to-amber-600',
                ];
                $evIcons = [
                    'created'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>',
                    'updated'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
                    'deleted'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
                    'restored' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>',
                ];
                $evLabels = ['created' => 'Creación', 'updated' => 'Modificación', 'deleted' => 'Eliminación', 'restored' => 'Restauración'];
                $bgColor = $evColors[$auditDetalle['evento']] ?? 'from-slate-500 to-slate-600';
                $iconPath = $evIcons[$auditDetalle['evento']] ?? '';
                $evLabel = $evLabels[$auditDetalle['evento']] ?? $auditDetalle['evento'];
            @endphp

            <div class="bg-gradient-to-r {{ $bgColor }} px-6 py-4 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            {!! $iconPath !!}
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-white text-base">{{ $evLabel }} — {{ $auditDetalle['modelo'] }}</h2>
                        <p class="text-white/70 text-xs">{{ $auditDetalle['modulo'] }} · #{{ $auditDetalle['entidad_id'] }}</p>
                    </div>
                </div>
                <button wire:click="cerrarDetalle" class="text-white/70 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-200">

                    {{-- Columna izquierda: diff de campos --}}
                    <div class="md:col-span-2 p-5">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Cambios</h3>

                        @if(count($auditDetalle['all_keys']) > 0)
                            <div class="space-y-1">
                                @if($auditDetalle['evento'] === 'updated')
                                    <div class="grid grid-cols-3 gap-2 text-xs font-semibold text-slate-500 px-2 py-1">
                                        <span>Campo</span>
                                        <span class="text-red-500">Antes</span>
                                        <span class="text-green-600">Después</span>
                                    </div>
                                @endif

                                @foreach($auditDetalle['all_keys'] as $key)
                                    @php
                                        $old = $auditDetalle['old_values'][$key] ?? null;
                                        $new = $auditDetalle['new_values'][$key] ?? null;
                                        $fieldLabel = $old['label'] ?? $new['label'] ?? $key;
                                        $oldVal = $old['value'] ?? '—';
                                        $newVal = $new['value'] ?? '—';
                                    @endphp
                                    <div class="grid grid-cols-3 gap-2 text-sm px-2 py-1.5 rounded-lg hover:bg-slate-50">
                                        <span class="text-slate-600 font-medium truncate" title="{{ $fieldLabel }}">{{ $fieldLabel }}</span>
                                        @if($auditDetalle['evento'] === 'updated')
                                            <span class="text-red-600 truncate" title="{{ $oldVal }}">{{ $oldVal }}</span>
                                            <span class="text-green-700 font-medium truncate" title="{{ $newVal }}">{{ $newVal }}</span>
                                        @elseif($auditDetalle['evento'] === 'created')
                                            <span class="col-span-2 text-green-700 font-medium truncate" title="{{ $newVal }}">{{ $newVal }}</span>
                                        @elseif($auditDetalle['evento'] === 'deleted')
                                            <span class="col-span-2 text-red-600 line-through truncate" title="{{ $oldVal }}">{{ $oldVal }}</span>
                                        @else
                                            <span class="col-span-2 text-slate-700 truncate" title="{{ $newVal }}">{{ $newVal }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-slate-400">Sin campos registrados.</p>
                        @endif
                    </div>

                    {{-- Columna derecha: metadata --}}
                    <div class="p-5">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Metadatos</h3>
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-xs text-slate-400">Fecha y hora</dt>
                                <dd class="font-medium text-slate-800 mt-0.5">{{ $auditDetalle['fecha'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-400">Usuario</dt>
                                <dd class="font-medium text-slate-800 mt-0.5">{{ $auditDetalle['usuario'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-400">Dirección IP</dt>
                                <dd class="font-mono text-xs text-slate-600 mt-0.5">{{ $auditDetalle['ip'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-400">URL</dt>
                                <dd class="font-mono text-xs text-slate-500 mt-0.5 break-all">{{ $auditDetalle['url'] }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="flex-shrink-0 px-5 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
                <button wire:click="cerrarDetalle"
                        class="px-4 py-2 text-sm bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg transition-colors">
                    Cerrar
                </button>
            </div>

        </div>
    </div>
@endif
