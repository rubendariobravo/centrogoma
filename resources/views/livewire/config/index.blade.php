<div>
    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Configuración</h1>
            <p class="text-sm text-gray-500 mt-0.5">Parámetros generales del sistema</p>
        </div>
    </div>

    {{-- Flash --}}
    @if($saved)
    <div class="mb-4 flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg px-4 py-3">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Configuración guardada correctamente.
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form wire:submit="save">
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

        {{-- Datos de la empresa --}}
        <div class="bg-white rounded-xl shadow-sm p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 border-b border-[#DDE1E7] pb-2">Datos de la empresa</h2>

            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Nombre comercial *</label>
                    <input wire:model="empresa_nombre" type="text"
                           class="w-full text-sm border {{ $errors->has('empresa_nombre') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Razón social *</label>
                    <input wire:model="empresa_razon_social" type="text"
                           class="w-full text-sm border {{ $errors->has('empresa_razon_social') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">CUIT *</label>
                    <input wire:model="empresa_cuit" type="text" placeholder="20-12345678-9"
                           class="w-full text-sm border {{ $errors->has('empresa_cuit') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Teléfono</label>
                    <input wire:model="empresa_telefono" type="text"
                           class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Domicilio</label>
                    <input wire:model="empresa_domicilio" type="text"
                           class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Localidad</label>
                    <input wire:model="empresa_localidad" type="text"
                           class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Email</label>
                    <input wire:model="empresa_email" type="email"
                           class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
                {{-- Logo --}}
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-2">Logo de la empresa</label>

                    <div class="flex items-start gap-4">
                        {{-- Preview --}}
                        <div class="flex-shrink-0 w-24 h-24 rounded-lg border border-[#DDE1E7] bg-[#F8F9FA] flex items-center justify-center overflow-hidden">
                            @if($logoFile)
                                <img src="{{ $logoFile->temporaryUrl() }}" alt="Preview" class="w-full h-full object-contain p-1">
                            @elseif($empresa_logo_url)
                                <img src="{{ $empresa_logo_url }}" alt="Logo" class="w-full h-full object-contain p-1"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <div class="hidden w-full h-full items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 3l18 18M3 12.75V18a.75.75 0 00.75.75h16.5A.75.75 0 0021 18v-5.25"/>
                                    </svg>
                                </div>
                            @else
                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 9.75V18a.75.75 0 00.75.75h16.5A.75.75 0 0021 18V9.75M3 9.75A2.25 2.25 0 015.25 7.5h13.5A2.25 2.25 0 0121 9.75"/>
                                </svg>
                            @endif
                        </div>

                        {{-- Controles --}}
                        <div class="flex-1 space-y-2">
                            <div>
                                <label class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-[#DDE1E7] rounded-lg text-sm text-gray-700 cursor-pointer hover:bg-[#F8F9FA] transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                                    </svg>
                                    Subir imagen
                                    <input type="file" wire:model="logoFile" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml" class="sr-only">
                                </label>
                                @error('logoFile') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                @if($logoFile)
                                    <span class="ml-2 text-xs text-emerald-600">✓ Imagen seleccionada — se guardará al confirmar</span>
                                @endif
                            </div>

                            <p class="text-xs text-gray-400">PNG, JPG, WebP o SVG. Máximo 2 MB.</p>

                            @if($empresa_logo_url || $logoFile)
                            <button type="button" wire:click="removeLogo"
                                    class="text-xs text-red-500 hover:text-red-700 inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Quitar logo
                            </button>
                            @endif

                            {{-- URL manual como alternativa --}}
                            <div class="pt-1">
                                <label class="block text-xs text-gray-400 mb-1">O ingresá una URL externa</label>
                                <input wire:model="empresa_logo_url" type="url" placeholder="https://..."
                                       class="w-full text-xs border border-[#DDE1E7] rounded-lg px-2.5 py-1.5 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none text-gray-600">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            {{-- Facturación --}}
            <div class="bg-white rounded-xl shadow-sm p-5 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700 border-b border-[#DDE1E7] pb-2">Facturación AFIP</h2>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Punto de venta *</label>
                        <input wire:model="punto_venta" type="text" placeholder="0001" maxlength="4"
                               class="w-full text-sm border {{ $errors->has('punto_venta') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">IIBB Nº</label>
                        <input wire:model="iibb_numero" type="text"
                               class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Régimen IIBB</label>
                        <select wire:model="iibb_tipo"
                                class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
                            <option value="multilateral">Convenio multilateral</option>
                            <option value="local">Local</option>
                            <option value="directo">Contribuyente directo</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Stock --}}
            <div class="bg-white rounded-xl shadow-sm p-5 space-y-3">
                <h2 class="text-sm font-semibold text-gray-700 border-b border-[#DDE1E7] pb-2">Stock y ventas</h2>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-700">Permitir stock negativo</p>
                        <p class="text-xs text-gray-400">Permite vender sin stock disponible</p>
                    </div>
                    <button type="button" wire:click="$toggle('stock_negativo')"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                                {{ $stock_negativo ? 'bg-slate-900' : 'bg-gray-200' }}">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                            {{ $stock_negativo ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </button>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-700">Ajuste automático de precio costo</p>
                        <p class="text-xs text-gray-400">Actualiza precio costo al registrar una compra</p>
                    </div>
                    <button type="button" wire:click="$toggle('ajuste_automatico')"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                                {{ $ajuste_automatico ? 'bg-slate-900' : 'bg-gray-200' }}">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                            {{ $ajuste_automatico ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </button>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-700">Pedir vendedor en ventas</p>
                        <p class="text-xs text-gray-400">Requerir selección de vendedor/empleado</p>
                    </div>
                    <button type="button" wire:click="$toggle('pide_vendedor')"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                                {{ $pide_vendedor ? 'bg-slate-900' : 'bg-gray-200' }}">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                            {{ $pide_vendedor ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </button>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Número de listas de precio (1–6)</label>
                    <input wire:model="listas_precio" type="number" min="1" max="6"
                           class="w-24 text-sm border {{ $errors->has('listas_precio') ? 'border-red-300' : 'border-[#DDE1E7]' }} rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                </div>
            </div>
        </div>

        {{-- Impresión --}}
        <div class="bg-white rounded-xl shadow-sm p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 border-b border-[#DDE1E7] pb-2">Impresión</h2>

            {{-- Formato --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-2">Formato predeterminado</label>
                <div class="flex gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" wire:model="impresion_formato" value="ticket"
                               class="accent-slate-900">
                        <div>
                            <span class="text-sm text-gray-700 font-medium">Ticket</span>
                            <p class="text-xs text-gray-400">Papel térmico 72mm</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" wire:model="impresion_formato" value="pdf"
                               class="accent-slate-900">
                        <div>
                            <span class="text-sm text-gray-700 font-medium">PDF A4</span>
                            <p class="text-xs text-gray-400">Hoja A4 estándar</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Auto-imprimir --}}
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">Imprimir automáticamente al finalizar venta</p>
                    <p class="text-xs text-gray-400">Abre el diálogo de impresión al registrar cada venta</p>
                </div>
                <button type="button" wire:click="$toggle('impresion_auto_venta')"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                            {{ $impresion_auto_venta ? 'bg-slate-900' : 'bg-gray-200' }}">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                        {{ $impresion_auto_venta ? 'translate-x-6' : 'translate-x-1' }}"></span>
                </button>
            </div>

            {{-- Nombre de impresora --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Impresora predeterminada</label>
                <input wire:model="impresora_nombre" type="text"
                       placeholder="Ej: HP LaserJet Pro M404n"
                       class="w-full text-sm border border-[#DDE1E7] rounded-lg px-3 py-2 focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none">
                <p class="text-xs text-gray-400 mt-1.5 leading-relaxed">
                    Los navegadores no permiten seleccionar impresoras directamente desde una página web.
                    Para usar una impresora específica, configúrela como
                    <span class="font-medium text-gray-600">predeterminada en Windows</span>
                    (Panel de control → Dispositivos e impresoras) antes de imprimir.
                    El nombre ingresado es solo referencia.
                    <a href="#" onclick="window.open('{{ route('ventas.imprimir', ['venta' => 0]) }}'.replace('/0/', '/{{ \App\Models\Venta::latest()->value('id') ?? 0 }}/'), '_blank'); return false;"
                       class="text-slate-700 underline hover:text-slate-900 ml-1">
                        Probar impresión →
                    </a>
                </p>
            </div>
        </div>
    </div>

    <div class="flex justify-end mt-4">
        <button type="submit"
                class="px-6 py-2.5 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition-colors">
            Guardar configuración
        </button>
    </div>
    </form>
</div>
