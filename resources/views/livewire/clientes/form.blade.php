<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('clientes') }}" wire:navigate class="hover:text-gray-700">Clientes</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-900">{{ $clienteId ? 'Editar cliente' : 'Nuevo cliente' }}</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900">
                {{ $clienteId ? ($razon_social ?: 'Editar cliente') : 'Nuevo cliente' }}
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('clientes') }}" wire:navigate
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

        {{-- Columna izquierda --}}
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
                        @error('cuit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">DNI</label>
                        <input wire:model="dni" type="text" maxlength="15"
                               class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tipo de IVA <span class="text-red-500">*</span></label>
                        <select wire:model="tipoiva_id"
                                class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white {{ $errors->has('tipoiva_id') ? 'border-red-300' : 'border-[#DDE1E7]' }}">
                            <option value="">Seleccionar...</option>
                            @foreach($tiposIva as $tipo)
                                <option value="{{ $tipo->id }}">{{ $tipo->descripcion }}</option>
                            @endforeach
                        </select>
                        @error('tipoiva_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Vendedor asignado</label>
                        <select wire:model="empleado_id"
                                class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                            <option value="">Sin vendedor</option>
                            @foreach($vendedores as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->nombre_completo }}</option>
                            @endforeach
                        </select>
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

            {{-- Estado y categoría --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Clasificación</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Estado</label>
                        <select wire:model="estado"
                                class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                            <option value="A">Activo</option>
                            <option value="I">Inactivo</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between py-2 border-t border-gray-50">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Cliente mayorista</p>
                            <p class="text-xs text-gray-400">Accede a precios mayoristas</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input wire:model="es_mayorista" type="checkbox" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-slate-900 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Condiciones comerciales --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Condiciones comerciales</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Lista de precios</label>
                        <select wire:model="lista_precio_id"
                                class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                            <option value="">— Lista por defecto del sistema —</option>
                            @foreach($listasPrecio as $lp)
                                <option value="{{ $lp->id }}">{{ $lp->nombre }}@if($lp->es_default) (default)@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bonificación general (%)</label>
                        <div class="relative">
                            <input wire:model="bonificacion" type="number" min="0" max="100" step="0.01"
                                   class="w-full pr-8 px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Límite de crédito ($)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                            <input wire:model="limite_credito" type="number" min="0" step="0.01"
                                   class="w-full pl-7 pr-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Impuestos --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Impuestos</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Percepción IIBB (%)</label>
                        <div class="relative">
                            <input wire:model="porcentaje_iibb" type="number" min="0" max="100" step="0.01"
                                   class="w-full pr-8 px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">0% = sin percepción IIBB</p>
                    </div>

                    <div class="flex items-center justify-between py-2 border-t border-gray-50">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Percepción IVA</p>
                            <p class="text-xs text-gray-400">Aplica percepción de IVA</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input wire:model="percepcion_iva" type="checkbox" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-slate-900 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Overrides de precios (cliente_precios) --}}
    <div class="bg-white rounded-xl shadow-sm p-5 mt-4">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Precios especiales y overrides</h2>
                <p class="text-xs text-gray-500 mt-0.5">Reglas que se aplican antes de la lista del cliente: precios fijos por artículo (finales, con IVA), lista distinta para un rubro, o bonificaciones.</p>
            </div>
            <button wire:click="addOverride" type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 bg-slate-100 rounded-md hover:bg-slate-200 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Agregar regla
            </button>
        </div>

        @if(count($overrides) === 0)
            <p class="text-xs text-gray-400 italic py-3">Sin reglas. El cliente usará la lista asignada arriba.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[#DDE1E7] bg-[#E9ECF0]">
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-44">Tipo</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Artículo / Rubro</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-48">Valor</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide w-16">Activo</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E9ECF0]">
                        @foreach($overrides as $i => $ov)
                        <tr>
                            <td class="px-3 py-2">
                                <select wire:model.live="overrides.{{ $i }}.tipo"
                                        class="w-full px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                                    <option value="precio_articulo">Precio fijo por artículo</option>
                                    <option value="bonif_articulo">Bonificación por artículo</option>
                                    <option value="lista_rubro">Lista distinta por rubro</option>
                                    <option value="bonif_rubro">Bonificación por rubro</option>
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                @if(in_array($ov['tipo'], ['precio_articulo', 'bonif_articulo']))
                                    <select wire:model="overrides.{{ $i }}.articulo_id"
                                            class="w-full px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                                        <option value="">— Seleccionar artículo —</option>
                                        @foreach($articulos as $art)
                                            <option value="{{ $art->id }}">{{ $art->codigo }} — {{ $art->descripcion }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <select wire:model="overrides.{{ $i }}.rubro_id"
                                            class="w-full px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                                        <option value="">— Seleccionar rubro —</option>
                                        @foreach($rubros as $rb)
                                            <option value="{{ $rb->id }}">{{ $rb->descripcion }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if($ov['tipo'] === 'precio_articulo')
                                    <div class="relative">
                                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                                        <input wire:model="overrides.{{ $i }}.precio_especial" type="number" min="0" step="0.01" placeholder="0.00"
                                               title="Precio final con IVA"
                                               class="w-full pl-6 pr-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-0.5">Final con IVA</p>
                                @elseif($ov['tipo'] === 'lista_rubro')
                                    <select wire:model="overrides.{{ $i }}.lista_precio_id"
                                            class="w-full px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                                        <option value="">— Lista —</option>
                                        @foreach($listasPrecio as $lp)
                                            <option value="{{ $lp->id }}">{{ $lp->nombre }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    {{-- bonificacion (% por articulo o rubro) --}}
                                    <div class="relative">
                                        <input wire:model="overrides.{{ $i }}.bonificacion" type="number" min="0" max="100" step="0.01" placeholder="0"
                                               class="w-full pr-7 px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center">
                                <input wire:model="overrides.{{ $i }}.activo" type="checkbox" class="rounded border-[#DDE1E7] text-slate-900 focus:ring-slate-900">
                            </td>
                            <td class="px-3 py-2 text-right">
                                <button wire:click="removeOverride({{ $i }})" type="button"
                                        class="p-1.5 text-gray-300 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Eliminar regla">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
