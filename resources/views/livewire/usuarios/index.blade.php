<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Usuarios</h1>
            <p class="text-sm text-gray-500 mt-0.5">Gestión de accesos al sistema</p>
        </div>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo usuario
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

    {{-- Buscador --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <div class="relative max-w-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="Buscar por nombre o email..."
                   class="w-full pl-9 pr-4 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Nombre</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Rol</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#E9ECF0]">
                @forelse($usuarios as $usuario)
                <tr class="hover:bg-[#E9ECF0] transition-colors group">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $usuario->name }}</div>
                        @if($usuario->id === auth()->id())
                            <span class="text-xs text-blue-500">(tú)</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $usuario->email }}</td>
                    <td class="px-4 py-3">
                        @foreach($usuario->roles as $rol)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $rol->name === 'admin' ? 'bg-red-100 text-red-700' : '' }}
                            {{ $rol->name === 'supervisor' ? 'bg-purple-100 text-purple-700' : '' }}
                            {{ $rol->name === 'cajero' ? 'bg-blue-100 text-blue-700' : '' }}
                            {{ $rol->name === 'vendedor' ? 'bg-emerald-100 text-emerald-700' : '' }}
                            {{ $rol->name === 'almacen' ? 'bg-orange-100 text-orange-700' : '' }}
                            {{ !in_array($rol->name, ['admin','supervisor','cajero','vendedor','almacen']) ? 'bg-gray-100 text-gray-600' : '' }}
                        ">
                            {{ ucfirst($rol->name) }}
                        </span>
                        @endforeach
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button wire:click="openEdit({{ $usuario->id }})"
                                    class="p-1.5 text-gray-400 hover:text-slate-900 hover:bg-gray-100 rounded-md transition-colors" title="Editar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            @if($usuario->id !== auth()->id())
                            <button wire:click="confirmDelete({{ $usuario->id }})"
                                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Eliminar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-16 text-center text-gray-400 text-sm">No hay usuarios</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($usuarios->hasPages())
        <div class="px-4 py-3 border-t border-[#DDE1E7]">
            {{ $usuarios->links() }}
        </div>
        @endif
    </div>

    {{-- Modal formulario --}}
    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 w-full max-w-md">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[#DDE1E7]">
                <h2 class="text-base font-semibold text-gray-900">
                    {{ $userId ? 'Editar usuario' : 'Nuevo usuario' }}
                </h2>
                <button wire:click="resetForm" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Nombre *</label>
                    <input wire:model="name" type="text"
                           class="w-full text-sm border {{ $errors->has('name') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Email *</label>
                    <input wire:model="email" type="email"
                           class="w-full text-sm border {{ $errors->has('email') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">
                        Contraseña {{ $userId ? '(dejar vacío para no cambiar)' : '*' }}
                    </label>
                    <input wire:model="password" type="password" autocomplete="new-password"
                           class="w-full text-sm border {{ $errors->has('password') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Rol *</label>
                    <select wire:model="roleName"
                            class="w-full text-sm border {{ $errors->has('roleName') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                        <option value="">— Seleccionar —</option>
                        @foreach($roles as $rol)
                            <option value="{{ $rol->name }}">{{ ucfirst($rol->name) }}</option>
                        @endforeach
                    </select>
                    @error('roleName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2 px-5 py-4 border-t border-[#DDE1E7]">
                <button wire:click="resetForm"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                    Cancelar
                </button>
                <button wire:click="save"
                        class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors">
                    {{ $userId ? 'Actualizar' : 'Crear usuario' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal confirmación eliminación --}}
    @if($confirmingDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 w-full max-w-sm p-6">
            <div class="flex items-start gap-3 mb-4">
                <div class="flex-shrink-0 w-9 h-9 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900">¿Eliminar usuario?</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Esta acción no se puede deshacer.</p>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button wire:click="$set('confirmingDelete', null)"
                        class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    Cancelar
                </button>
                <button wire:click="delete"
                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                    Eliminar
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
