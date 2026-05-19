<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('proveedores') }}" wire:navigate class="hover:text-gray-700">Proveedores</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-900">{{ $proveedorId ? 'Editar proveedor' : 'Nuevo proveedor' }}</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900">
                {{ $proveedorId ? ($razon_social ?: 'Editar proveedor') : 'Nuevo proveedor' }}
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('proveedores') }}" wire:navigate
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-[#DDE1E7] rounded-lg hover:bg-[#E9ECF0] transition-colors">
                Cancelar
            </a>
            <button wire:click="save"
                    class="px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-700 transition-colors">
                Guardar
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Columna principal --}}
        <div class="xl:col-span-2 space-y-6">

            {{-- Identificación --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Identificación</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Razón social <span class="text-red-500">*</span></label>
                        <input wire:model="razon_social" type="text" maxlength="200"
                               class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('razon_social') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                        @error('razon_social') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nombre de fantasía</label>
                        <input wire:model="nombre_fantasia" type="text" maxlength="200"
                               class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">CUIT</label>
                        <input wire:model="cuit" type="text" maxlength="20" placeholder="XX-XXXXXXXX-X"
                               class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Persona de contacto</label>
                        <input wire:model="contacto" type="text" maxlength="100"
                               class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    </div>
                </div>
            </div>

            {{-- Contacto --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Contacto</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Teléfono</label>
                        <input wire:model="telefono" type="text" maxlength="30"
                               class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Celular</label>
                        <input wire:model="celular" type="text" maxlength="30"
                               class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                        <input wire:model="email" type="email" maxlength="100"
                               class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('email') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Domicilio --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Domicilio</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Calle y número</label>
                        <input wire:model="domicilio" type="text" maxlength="200"
                               class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Localidad</label>
                        <input wire:model="localidad" type="text" maxlength="100"
                               class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Provincia</label>
                        <input wire:model="provincia" type="text" maxlength="100"
                               class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Código postal</label>
                        <input wire:model="codigo_postal" type="text" maxlength="10"
                               class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    </div>
                </div>
            </div>

            {{-- Observación --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Observaciones</h2>
                <textarea wire:model="observacion" rows="3" maxlength="500"
                          class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none resize-none"
                          placeholder="Notas internas..."></textarea>
            </div>
        </div>

        {{-- Columna derecha --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Estado</h2>
                <select wire:model="estado"
                        class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                    <option value="A">Activo</option>
                    <option value="I">Inactivo</option>
                </select>
            </div>
        </div>
    </div>
</div>
