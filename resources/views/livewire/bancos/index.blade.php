<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Bancos</h1>
            <p class="text-sm text-gray-500 mt-0.5">Catálogo de bancos para la gestión de cheques</p>
        </div>
        <button wire:click="openCreate"
                class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo banco
        </button>
    </div>

    @if(session('success'))
    <div class="mb-4 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg px-4 py-3">
        {{ session('success') }}
    </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="sm:col-span-2">
            <label class="block text-xs font-medium text-gray-500 mb-1">Buscar</label>
            <input wire:model.live.debounce.400ms="search" type="text" placeholder="Nombre del banco"
                   class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Estado</label>
            <select wire:model.live="filterEstado" class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
                <option value="">Todos</option>
            </select>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Banco</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">CUIT</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Cheques</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9ECF0]">
                    @forelse($bancos as $banco)
                    <tr class="hover:bg-[#E9ECF0] transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $banco->descripcion }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $banco->cuit ?: '—' }}</td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ $banco->cheques_count }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($banco->activo)
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Activo</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button wire:click="openEdit({{ $banco->id }})"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-slate-700 hover:bg-gray-100 transition-colors" title="Editar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button wire:click="confirmDelete({{ $banco->id }})"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Eliminar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-gray-400 text-sm">No hay bancos cargados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($bancos->hasPages())
        <div class="px-4 py-3 border-t border-[#DDE1E7]">{{ $bancos->links() }}</div>
        @endif
    </div>

    {{-- Modal crear / editar --}}
    @if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-semibold text-gray-900">{{ $bancoId ? 'Editar banco' : 'Nuevo banco' }}</h3>
                <button wire:click="resetForm" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input wire:model="descripcion" type="text" maxlength="100"
                           class="w-full px-3 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-slate-900 {{ $errors->has('descripcion') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                    @error('descripcion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CUIT (opcional)</label>
                    <input wire:model="cuit" type="text" maxlength="13" placeholder="30-12345678-9"
                           class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg outline-none focus:ring-2 focus:ring-slate-900">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input wire:model="activo" type="checkbox" class="rounded border-gray-300 text-slate-900 focus:ring-slate-900">
                    Activo
                </label>
            </div>

            <div class="flex gap-3 mt-6">
                <button wire:click="resetForm"
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Cancelar</button>
                <button wire:click="save"
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-800 transition-colors">Guardar</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal confirmación de borrado --}}
    @if($confirmingDelete)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 p-6 w-full max-w-sm">
            <h3 class="font-semibold text-gray-900 mb-2">Eliminar banco</h3>
            <p class="text-sm text-gray-500 mb-6">Si tiene cheques asociados se desactivará en lugar de eliminarse. ¿Confirma?</p>
            <div class="flex gap-3">
                <button wire:click="$set('confirmingDelete', null)"
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Cancelar</button>
                <button wire:click="delete"
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">Eliminar</button>
            </div>
        </div>
    </div>
    @endif
</div>
