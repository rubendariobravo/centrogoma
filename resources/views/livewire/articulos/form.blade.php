<div
    x-data="{
        tiers: @entangle('tiers'),
        previewCantidad: 1,
        previewPrecio: 0,
        calcularPrecio() {
            const qty = parseFloat(this.previewCantidad) || 0;
            let precio = 0;
            const activos = this.tiers.filter(t => t.activo);
            for (const t of activos) {
                const desde = parseFloat(t.cantidad_desde) || 0;
                const hasta = t.cantidad_hasta ? parseFloat(t.cantidad_hasta) : Infinity;
                if (qty >= desde && qty <= hasta) {
                    precio = parseFloat(t.precio) || 0;
                    break;
                }
            }
            this.previewPrecio = precio;
        }
    }"
    x-init="$watch('tiers', () => calcularPrecio()); $watch('previewCantidad', () => calcularPrecio()); calcularPrecio()"
>

    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('articulos') }}" wire:navigate
               class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    {{ $articuloId ? 'Editar artículo' : 'Nuevo artículo' }}
                </h1>
                @if($articuloId)
                    <p class="text-sm text-gray-500 mt-0.5 font-mono">{{ $codigo }}</p>
                @endif
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('articulos') }}" wire:navigate
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-[#DDE1E7] rounded-lg hover:bg-[#E9ECF0] transition-colors">
                Cancelar
            </a>
            <button wire:click="save" wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-700 transition-colors disabled:opacity-50 flex items-center gap-2">
                <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Guardar
            </button>
        </div>
    </div>

    {{-- Errores de validación --}}
    @if($errors->any())
    <div class="mb-5 bg-red-50 border border-red-200 rounded-lg p-4">
        <ul class="text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $error)
                <li class="flex items-start gap-1.5"><span class="mt-0.5">•</span> {{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        {{-- Columna izquierda: Info básica + clasificación + stock --}}
        <div class="xl:col-span-1 space-y-5">

            {{-- Identificación --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Identificación</h2>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Código</label>
                        <input wire:model="codigo" type="text"
                               class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Descripción <span class="text-red-500">*</span></label>
                        <input wire:model="descripcion" type="text"
                               class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none @error('descripcion') border-red-300 @enderror">
                        @error('descripcion') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Presentación</label>
                        <input wire:model="presentacion" type="text" placeholder="Ej: Pollo entero x 2.5 kg"
                               class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Estado</label>
                        <select wire:model="estado"
                                class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                            <option value="A">Activo</option>
                            <option value="I">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Clasificación --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Clasificación</h2>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Rubro</label>
                        <select wire:model="rubro_id"
                                class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                            <option value="">— Sin rubro —</option>
                            @foreach($rubros as $rubro)
                                <option value="{{ $rubro->id }}">{{ $rubro->descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Marca</label>
                        <select wire:model="marca_id"
                                class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                            <option value="">— Sin marca —</option>
                            @foreach($marcas as $marca)
                                <option value="{{ $marca->id }}">{{ $marca->descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Presentación --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Presentación</h2>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Unidad de medida</label>
                        <select wire:model="unidad_medida"
                                class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                            <option value="unidad">Unidad</option>
                            <option value="juego">Juego</option>
                            <option value="par">Par</option>
                            <option value="kg">Kilogramo (kg)</option>
                            <option value="metro">Metro</option>
                            <option value="docena">Docena</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Cant. por caja</label>
                            <input wire:model="cantidad_caja" type="number" step="0.01" min="0"
                                   class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Presentación caja</label>
                            <input wire:model="presentacion_caja" type="text"
                                   class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none"
                                   placeholder="ej: Caja x 12">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stock --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Stock</h2>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Stock actual</label>
                            <input wire:model="stock" type="number" step="0.001"
                                   class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Stock mínimo</label>
                            <input wire:model="stock_minimo" type="number" step="0.001" min="0"
                                   class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                        </div>
                    </div>
                    <div class="space-y-2 pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model="modifica_stock" type="checkbox" class="rounded text-slate-900">
                            <span class="text-sm text-gray-700">Modifica stock al vender</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model="art_mayorista" type="checkbox" class="rounded text-slate-900">
                            <span class="text-sm text-gray-700">Es artículo mayorista</span>
                        </label>
                    </div>
                </div>
            </div>

        </div>

        {{-- Columna derecha: Precios por rango + costos --}}
        <div class="xl:col-span-2 space-y-5">

            {{-- Precios por rango de cantidad --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                            Precios por rango de cantidad
                            @if($this->listaDefault)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    Lista: {{ $this->listaDefault->nombre }}
                                </span>
                            @endif
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium border
                                {{ $verNetosTramos ? 'bg-slate-200 text-slate-700 border-slate-300' : 'bg-emerald-100 text-emerald-700 border-emerald-200' }}">
                                {{ $verNetosTramos ? 'Editando netos (sin IVA)' : 'Editando finales (con IVA)' }}
                            </span>
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Define el precio según la cantidad de unidades/cajas del pedido.
                            <a href="{{ route('listas-precios') }}" wire:navigate class="text-slate-700 hover:text-slate-900 underline ml-1">Gestionar otras listas</a>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="toggleVerNetosTramos"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-[#DDE1E7] rounded-lg hover:bg-[#E9ECF0] transition-colors"
                                title="Alternar entre precios finales (con IVA) y netos (sin IVA)">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                            {{ $verNetosTramos ? 'Ver finales' : 'Ver netos' }}
                        </button>
                        <button type="button" wire:click="addTier"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-900 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Agregar tramo
                        </button>
                    </div>
                </div>

                {{-- Tabla de tramos --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-[#E9ECF0] text-xs text-gray-500 border-y border-[#DDE1E7]">
                                <th class="px-3 py-2 text-left font-medium">Desde (unid)</th>
                                <th class="px-3 py-2 text-left font-medium">Hasta (unid)</th>
                                <th class="px-3 py-2 text-left font-medium">
                                    Precio {{ $verNetosTramos ? 'neto' : 'final' }}
                                </th>
                                <th class="px-3 py-2 text-left font-medium">
                                    {{ $verNetosTramos ? 'Final c/IVA' : 'Neto s/IVA' }}
                                </th>
                                <th class="px-3 py-2 text-center font-medium">Activo</th>
                                <th class="px-3 py-2 text-center font-medium">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E9ECF0]">
                            @foreach($tiers as $i => $tier)
                            <tr>
                                <td class="px-3 py-2">
                                    <input wire:model.live="tiers.{{ $i }}.cantidad_desde"
                                           type="number" step="0.001" min="0"
                                           class="w-24 px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none @error('tiers.'.$i.'.cantidad_desde') border-red-300 @enderror">
                                </td>
                                <td class="px-3 py-2">
                                    <input wire:model.live="tiers.{{ $i }}.cantidad_hasta"
                                           type="number" step="0.001" min="0"
                                           placeholder="Sin límite"
                                           class="w-28 px-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none text-gray-600 placeholder-gray-300">
                                </td>
                                <td class="px-3 py-2">
                                    <div class="relative">
                                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs">$</span>
                                        <input wire:model.live="tiers.{{ $i }}.precio"
                                               type="number" step="0.01" min="0"
                                               class="w-32 pl-5 pr-2 py-1.5 text-sm border border-[#DDE1E7] rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none @error('tiers.'.$i.'.precio') border-red-300 @enderror">
                                    </div>
                                    @error('tiers.'.$i.'.precio') <p class="text-xs text-red-600 mt-0.5">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-3 py-2 text-gray-500 text-xs whitespace-nowrap">
                                    @php
                                        $valorTier = (float) ($tier['precio'] ?? 0);
                                        $ivaArt    = (float) $alicuota_iva;
                                        $contrapartida = $verNetosTramos
                                            ? \App\Support\Pricing::final($valorTier, $ivaArt)
                                            : \App\Support\Pricing::neto($valorTier, $ivaArt);
                                    @endphp
                                    $ {{ number_format($contrapartida, 2, ',', '.') }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <input wire:model="tiers.{{ $i }}.activo" type="checkbox"
                                           class="rounded text-slate-900">
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <button type="button" wire:click="removeTier({{ $i }})"
                                            @if(count($tiers) <= 1) disabled @endif
                                            class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors disabled:opacity-30 disabled:cursor-not-allowed">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Preview: calculadora en tiempo real (Alpine, sin servidor) --}}
                <div class="mt-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
                    <p class="text-xs font-semibold text-slate-600 mb-2 uppercase tracking-wide">Calcular precio</p>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600">Cantidad:</span>
                            <input x-model.number="previewCantidad"
                                   type="number" step="0.001" min="0"
                                   class="w-24 px-2 py-1.5 text-sm border border-slate-200 rounded-md focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                            <span class="text-xs text-gray-400">unid</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                        <div>
                            <span class="text-sm text-gray-600">Precio {{ $verNetosTramos ? 'neto' : 'final' }}:</span>
                            <span class="ml-2 text-base font-bold text-slate-900" x-text="'$ ' + previewPrecio.toLocaleString('es-AR', {minimumFractionDigits:2, maximumFractionDigits:2})"></span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400">
                                {{ $verNetosTramos ? 'final con IVA' : 'neto sin IVA' }} ({{ $alicuota_iva }}%):
                            </span>
                            <span class="ml-1 text-sm font-semibold text-emerald-700"
                                  x-text="'$ ' + (previewPrecio @if($verNetosTramos) * {{ 1 + ($alicuota_iva / 100) }} @else / {{ 1 + ($alicuota_iva / 100) }} @endif).toLocaleString('es-AR', {minimumFractionDigits:2, maximumFractionDigits:2})"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Costos y precios base --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Costo y márgenes</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Precio costo</label>
                        <div class="relative">
                            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs">$</span>
                            <input wire:model="precio_costo" type="number" step="0.01" min="0"
                                   class="w-full pl-5 pr-2 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Margen %</label>
                        <input wire:model="margen" type="number" step="0.1" min="0"
                               class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bonificación %</label>
                        <input wire:model="bonificacion" type="number" step="0.1" min="0" max="100"
                               class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Alícuota IVA <span class="text-red-500">*</span></label>
                        <select wire:model.live="alicuota_iva"
                                class="w-full px-3 py-2 text-sm border border-[#DDE1E7] rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                            <option value="0">0% (Exento)</option>
                            <option value="10.5">10.5%</option>
                            <option value="21">21%</option>
                            <option value="27">27%</option>
                        </select>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Botones inferior --}}
    <div class="flex justify-end gap-2 mt-5 pt-5 border-t border-[#DDE1E7]">
        <a href="{{ route('articulos') }}" wire:navigate
           class="px-5 py-2 text-sm font-medium text-gray-700 bg-white border border-[#DDE1E7] rounded-lg hover:bg-[#E9ECF0] transition-colors">
            Cancelar
        </a>
        <button wire:click="save" wire:loading.attr="disabled"
                class="px-5 py-2 text-sm font-medium text-white bg-slate-900 rounded-lg hover:bg-slate-700 transition-colors disabled:opacity-50 flex items-center gap-2">
            <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Guardar artículo
        </button>
    </div>

</div>
