<div class="p-6 space-y-6 max-w-5xl mx-auto">

    {{-- Header --}}
    <div class="text-center pb-2">
        <div class="w-14 h-14 rounded-2xl bg-amber-500 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Centro de Ayuda</h1>
        <p class="text-slate-500 mt-1 text-sm">Encontrá información sobre todos los módulos del sistema</p>
    </div>

    {{-- Buscador --}}
    <div class="relative">
        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input wire:model.live.debounce.300ms="search"
               type="text"
               placeholder="Buscar en toda la ayuda..."
               class="w-full pl-11 pr-4 py-3 text-sm border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent bg-white shadow-sm">
        @if($search)
            <button wire:click="$set('search', '')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        @endif
    </div>

    {{-- Resultados de búsqueda --}}
    @if($search && strlen($search) >= 2)
        <div class="space-y-3">
            <h2 class="text-sm font-semibold text-slate-700">
                {{ count($resultados) }} resultado{{ count($resultados) !== 1 ? 's' : '' }} para "{{ $search }}"
            </h2>
            @forelse($resultados as $r)
                <div class="bg-white rounded-xl border border-slate-200 p-4 hover:border-amber-300 transition-colors">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800 text-sm">{{ $r['titulo'] }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $r['desc'] }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-400">
                    <svg class="w-8 h-8 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <p class="text-sm">No se encontraron resultados para "{{ $search }}"</p>
                </div>
            @endforelse
        </div>

    @else

        {{-- Tabs --}}
        <div class="flex gap-1 bg-slate-100 rounded-xl p-1">
            <button wire:click="$set('tabActiva', 'modulos')"
                    @class(['flex-1 py-2 text-sm font-medium rounded-lg transition-all duration-150', 'bg-white shadow text-slate-800' => $tabActiva === 'modulos', 'text-slate-500 hover:text-slate-700' => $tabActiva !== 'modulos'])>
                Por Módulo
            </button>
            <button wire:click="$set('tabActiva', 'guias')"
                    @class(['flex-1 py-2 text-sm font-medium rounded-lg transition-all duration-150', 'bg-white shadow text-slate-800' => $tabActiva === 'guias', 'text-slate-500 hover:text-slate-700' => $tabActiva !== 'guias'])>
                Guías paso a paso
            </button>
        </div>

        {{-- Tab: Por Módulo --}}
        @if($tabActiva === 'modulos')
            <div class="space-y-5">
                @foreach($moduloGroups as $grupNombre => $keys)
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-3 bg-slate-50 border-b border-slate-200">
                            <h2 class="font-semibold text-slate-700 text-sm uppercase tracking-wider">{{ $grupNombre }}</h2>
                        </div>
                        <div class="divide-y divide-slate-100" x-data="{ open: null }">
                            @foreach($keys as $key)
                                @php $page = $pages[$key] ?? null; @endphp
                                @if($page)
                                    <div>
                                        <button @click="open === '{{ $key }}' ? open = null : open = '{{ $key }}'"
                                                class="w-full flex items-center justify-between px-5 py-3.5 text-left hover:bg-slate-50 transition-colors group">
                                            <div>
                                                <p class="text-sm font-medium text-slate-800">{{ $page['titulo'] }}</p>
                                                <p class="text-xs text-slate-500 mt-0.5">{{ $page['descripcion'] ?? '' }}</p>
                                            </div>
                                            <div class="flex items-center gap-3 flex-shrink-0 ml-4">
                                                @if(!empty($page['tips']))
                                                    <span class="text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">{{ count($page['tips']) }} tips</span>
                                                @endif
                                                @if(!empty($page['faq']))
                                                    <span class="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">{{ count($page['faq']) }} FAQ</span>
                                                @endif
                                                <svg class="w-4 h-4 text-slate-400 transition-transform duration-150"
                                                     :class="open === '{{ $key }}' ? 'rotate-180' : ''"
                                                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </div>
                                        </button>

                                        <div x-show="open === '{{ $key }}'"
                                             x-transition:enter="transition ease-out duration-150"
                                             x-transition:enter-start="opacity-0"
                                             x-transition:enter-end="opacity-100"
                                             class="border-t border-slate-100 bg-slate-50">
                                            <div class="px-5 py-4 grid md:grid-cols-2 gap-6">

                                                {{-- Tips --}}
                                                @if(!empty($page['tips']))
                                                    <div>
                                                        <h4 class="text-xs font-semibold text-amber-700 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.001z"/></svg>
                                                            Tips
                                                        </h4>
                                                        <ul class="space-y-2">
                                                            @foreach($page['tips'] as $tip)
                                                                <li class="flex gap-2 text-xs text-slate-600">
                                                                    <span class="text-amber-400 flex-shrink-0 mt-0.5">▸</span>
                                                                    {{ $tip }}
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif

                                                {{-- FAQ --}}
                                                @if(!empty($page['faq']))
                                                    <div>
                                                        <h4 class="text-xs font-semibold text-blue-700 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                            Preguntas frecuentes
                                                        </h4>
                                                        <div class="space-y-3" x-data="{ faqOpen: null }">
                                                            @foreach($page['faq'] as $fi => $item)
                                                                <div>
                                                                    <button @click="faqOpen === {{ $fi }} ? faqOpen = null : faqOpen = {{ $fi }}"
                                                                            class="text-xs font-medium text-slate-700 hover:text-slate-900 text-left flex items-start gap-1.5 w-full">
                                                                        <span class="text-blue-400 flex-shrink-0">?</span>
                                                                        {{ $item['q'] }}
                                                                    </button>
                                                                    <div x-show="faqOpen === {{ $fi }}"
                                                                         x-transition:enter="transition ease-out duration-100"
                                                                         x-transition:enter-start="opacity-0"
                                                                         x-transition:enter-end="opacity-100"
                                                                         class="ml-4 mt-1 text-xs text-slate-500">
                                                                        {{ $item['a'] }}
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Tab: Guías --}}
        @if($tabActiva === 'guias')
            <div class="grid md:grid-cols-2 gap-4">
                @foreach($guides as $gi => $guide)
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center text-sm font-bold flex-shrink-0">
                                    {{ $gi + 1 }}
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-800 text-sm">{{ $guide['titulo'] }}</p>
                                    <p class="text-xs text-slate-500">{{ count($guide['pasos']) }} pasos</p>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-150 flex-shrink-0"
                                 :class="open ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             class="border-t border-slate-100">
                            <ol class="px-5 py-4 space-y-3">
                                @foreach($guide['pasos'] as $pi => $paso)
                                    <li class="flex gap-3 text-sm text-slate-600">
                                        <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 text-xs flex items-center justify-center font-semibold flex-shrink-0">{{ $pi + 1 }}</span>
                                        {{ $paso }}
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    @endif

</div>
