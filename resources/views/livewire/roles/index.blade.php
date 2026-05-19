<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Roles y permisos</h1>
            <p class="text-sm text-gray-500 mt-0.5">Configuración de accesos por rol</p>
        </div>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo rol
        </button>
    </div>

    {{-- Flash --}}
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

    {{-- Lista de roles --}}
    <div class="space-y-3">
        @foreach($roles as $role)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                        {{ $role->name === 'admin' ? 'bg-red-100 text-red-700' : '' }}
                        {{ $role->name === 'supervisor' ? 'bg-purple-100 text-purple-700' : '' }}
                        {{ $role->name === 'cajero' ? 'bg-blue-100 text-blue-700' : '' }}
                        {{ $role->name === 'vendedor' ? 'bg-emerald-100 text-emerald-700' : '' }}
                        {{ $role->name === 'almacen' ? 'bg-orange-100 text-orange-700' : '' }}
                        {{ !in_array($role->name, ['admin','supervisor','cajero','vendedor','almacen']) ? 'bg-gray-100 text-gray-600' : '' }}
                    ">
                        {{ ucfirst($role->name) }}
                    </span>
                    <span class="text-sm text-gray-500">
                        {{ $role->users_count ?? 0 }} usuario{{ ($role->users_count ?? 0) !== 1 ? 's' : '' }}
                        · {{ $role->permissions->count() }} permiso{{ $role->permissions->count() !== 1 ? 's' : '' }}
                    </span>
                </div>
                <div class="flex items-center gap-1">
                    <button wire:click="openEdit({{ $role->id }})"
                            class="p-1.5 text-gray-400 hover:text-slate-900 hover:bg-gray-100 rounded-md transition-colors" title="Editar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    @if(!in_array($role->name, ['admin', 'supervisor']))
                    <button wire:click="confirmDelete({{ $role->id }})"
                            class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Eliminar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                    @endif
                </div>
            </div>

            @if($role->permissions->count() > 0)
            <div class="px-4 pb-3 flex flex-wrap gap-1.5">
                @foreach($role->permissions as $perm)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600 font-mono">
                    {{ $perm->name }}
                </span>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Modal formulario --}}
    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[88vh] flex flex-col ring-1 ring-black/5">

            {{-- Header fijo --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#DDE1E7] shrink-0">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">
                        {{ $roleId ? 'Editar rol' : 'Nuevo rol' }}
                    </h2>
                    <p class="text-xs text-gray-400 mt-0.5">Asignación de permisos de acceso</p>
                </div>
                <button wire:click="$set('showForm', false)"
                        class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Contenido scrollable --}}
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                {{-- Nombre --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">Nombre del rol *</label>
                    <input wire:model="roleName" type="text"
                           class="w-full text-sm border {{ $errors->has('roleName') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    @error('roleName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Permisos agrupados por módulo --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="text-xs font-medium text-gray-500">Permisos por módulo</label>
                        <span class="text-xs text-gray-400">{{ count($selectedPermissions) }} seleccionados</span>
                    </div>

                    @php
                        // Agrupar por módulo: "ver_articulos" → módulo "articulos"
                        $permsByGroup = $permissions->groupBy(function($p) {
                            $parts = explode('_', $p->name, 2);
                            return $parts[1] ?? $p->name;
                        })->sortKeys();

                        $moduloLabels = [
                            'articulos'      => 'Artículos',
                            'cajas'          => 'Cajas',
                            'clientes'       => 'Clientes',
                            'compras'        => 'Compras',
                            'config'         => 'Configuración',
                            'dashboard'      => 'Dashboard',
                            'descartes'      => 'Descartes',
                            'empleados'      => 'Empleados',
                            'marcas'         => 'Marcas',
                            'pesadas'        => 'Pesadas',
                            'producciones'   => 'Producción',
                            'proveedores'    => 'Proveedores',
                            'recibos'        => 'Recibos',
                            'remitos'        => 'Remitos',
                            'roles'          => 'Roles',
                            'rubros'         => 'Rubros',
                            'usuarios'       => 'Usuarios',
                            'ventas'         => 'Ventas',
                            'cc_clientes'    => 'CC Clientes',
                            'cc_proveedores' => 'CC Proveedores',
                        ];

                        $verboLabels = [
                            'ver'      => 'Ver',
                            'crear'    => 'Crear',
                            'editar'   => 'Editar',
                            'eliminar' => 'Eliminar',
                        ];
                    @endphp

                    <div class="rounded-xl shadow-sm overflow-hidden">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-[#E9ECF0] border-b border-[#DDE1E7]">
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500">Módulo</th>
                                    @foreach($verboLabels as $verbo => $label)
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-500 w-20">{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E9ECF0]">
                                @foreach($permsByGroup as $modulo => $perms)
                                @php
                                    $moduloLabel = $moduloLabels[$modulo] ?? ucfirst(str_replace('_', ' ', $modulo));
                                    $permsByVerb = $perms->keyBy(fn($p) => explode('_', $p->name, 2)[0] ?? $p->name);
                                @endphp
                                <tr class="hover:bg-[#E9ECF0]/70 transition-colors">
                                    <td class="px-4 py-2.5 text-sm font-medium text-gray-700">{{ $moduloLabel }}</td>
                                    @foreach($verboLabels as $verbo => $label)
                                    <td class="px-3 py-2.5 text-center">
                                        @if($permsByVerb->has($verbo))
                                        <label class="inline-flex items-center justify-center cursor-pointer">
                                            <input type="checkbox"
                                                   wire:model="selectedPermissions"
                                                   value="{{ $permsByVerb[$verbo]->name }}"
                                                   class="rounded border-gray-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0 w-4 h-4">
                                        </label>
                                        @else
                                        <span class="text-gray-200">—</span>
                                        @endif
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Footer fijo --}}
            <div class="flex items-center justify-between px-6 py-4 border-t border-[#DDE1E7] shrink-0 bg-[#E9ECF0]/50 rounded-b-2xl">
                <p class="text-xs text-gray-400">
                    Los cambios aplican inmediatamente a todos los usuarios del rol
                </p>
                <div class="flex gap-2">
                    <button wire:click="$set('showForm', false)"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button wire:click="save"
                            class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors">
                        {{ $roleId ? 'Guardar cambios' : 'Crear rol' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal confirmación eliminación --}}
    @if($confirmingDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 ring-1 ring-black/5">
            <div class="flex items-start gap-3 mb-4">
                <div class="flex-shrink-0 w-9 h-9 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">¿Eliminar rol?</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Los usuarios con este rol perderán todos sus permisos asignados.</p>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button wire:click="$set('confirmingDelete', null)"
                        class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    Cancelar
                </button>
                <button wire:click="delete"
                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                    Eliminar rol
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
