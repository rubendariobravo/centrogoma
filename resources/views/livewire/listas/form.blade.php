<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('listas-precios') }}" wire:navigate class="hover:text-gray-700">Listas de precios</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-900">{{ $listaId ? 'Editar' : 'Nueva' }}</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900">{{ $listaId ? 'Editar lista' : 'Nueva lista' }}</h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('listas-precios') }}" wire:navigate
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-[#DDE1E7] rounded-lg hover:bg-[#E9ECF0] transition-colors">Cancelar</a>
            <button wire:click="save"
                    class="px-5 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-700 transition-colors">Guardar</button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Identificación</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Código <span class="text-red-500">*</span></label>
                    <input wire:model="codigo" type="text" maxlength="30"
                           class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none uppercase {{ $errors->has('codigo') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                    @error('codigo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input wire:model="nombre" type="text" maxlength="100"
                           class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('nombre') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                    @error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Descripción</label>
                    <textarea wire:model="descripcion" rows="2" maxlength="250"
                              class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                    <select wire:model="tipo"
                            class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                        <option value="minorista">Minorista</option>
                        <option value="mayorista">Mayorista</option>
                        <option value="especial">Especial</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Reglas comerciales</h2>
            <div class="space-y-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input wire:model="es_default" type="checkbox" class="mt-0.5 rounded border-[#DDE1E7] text-slate-900 focus:ring-slate-900">
                    <span class="text-sm">
                        <span class="font-medium text-gray-900">Lista default del sistema</span>
                        <span class="block text-xs text-gray-500">Se usa cuando un cliente no tiene lista asignada. Solo una lista puede ser default.</span>
                    </span>
                </label>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Recargo por pago no-efectivo (%)</label>
                    <div class="relative">
                        <input wire:model="recargo_no_efectivo" type="number" min="0" max="100" step="0.01"
                               class="w-full pr-8 px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('recargo_no_efectivo') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Se aplica al precio cuando la forma de pago no es efectivo.</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Descuento por pago en efectivo (%)</label>
                    <div class="relative">
                        <input wire:model="descuento_efectivo" type="number" min="0" max="100" step="0.01"
                               class="w-full pr-8 px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('descuento_efectivo') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Orden</label>
                    <input wire:model="orden" type="number" min="0" step="1"
                           class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none {{ $errors->has('orden') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                    <p class="text-xs text-gray-400 mt-1">Posición en el listado y en el selector de cliente.</p>
                </div>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input wire:model="activo" type="checkbox" class="mt-0.5 rounded border-[#DDE1E7] text-slate-900 focus:ring-slate-900">
                    <span class="text-sm font-medium text-gray-900">Activa</span>
                </label>
            </div>
        </div>
    </div>
</div>
