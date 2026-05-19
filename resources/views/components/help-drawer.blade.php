@php
    $routeName  = request()->route()?->getName() ?? '';
    $routeMap   = config('help.route_map', []);
    $pageKey    = $routeMap[$routeName] ?? $routeName;
    $page       = config("help.pages.{$pageKey}");
    $guides     = collect(config('help.guides', []))
                    ->filter(fn($g) => in_array($pageKey, $g['modulos'] ?? []))
                    ->values();
@endphp

<div
    x-show="helpOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-x-4"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-4"
    class="fixed inset-y-0 right-0 z-40 w-80 bg-white shadow-2xl flex flex-col border-l border-slate-200"
    x-cloak
    @click.outside="helpOpen = false"
>
    {{-- Header --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 bg-slate-50 flex-shrink-0">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-amber-500 flex items-center justify-center">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-800">Centro de Ayuda</h2>
                @if($page)
                    <p class="text-xs text-slate-500">{{ $page['titulo'] }}</p>
                @endif
            </div>
        </div>
        <button @click="helpOpen = false" class="text-slate-400 hover:text-slate-700 transition-colors p-1 rounded">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Tabs --}}
    <div x-data="{ tab: 'tips' }" class="flex flex-col flex-1 min-h-0">

        <div class="flex border-b border-slate-200 flex-shrink-0">
            <button @click="tab = 'tips'"
                    :class="tab === 'tips' ? 'border-b-2 border-amber-500 text-amber-600 font-semibold' : 'text-slate-500 hover:text-slate-700'"
                    class="flex-1 text-xs py-2.5 transition-colors">
                Tips
            </button>
            <button @click="tab = 'faq'"
                    :class="tab === 'faq' ? 'border-b-2 border-amber-500 text-amber-600 font-semibold' : 'text-slate-500 hover:text-slate-700'"
                    class="flex-1 text-xs py-2.5 transition-colors">
                Preguntas
            </button>
            @if($guides->isNotEmpty())
            <button @click="tab = 'guides'"
                    :class="tab === 'guides' ? 'border-b-2 border-amber-500 text-amber-600 font-semibold' : 'text-slate-500 hover:text-slate-700'"
                    class="flex-1 text-xs py-2.5 transition-colors">
                Guías
            </button>
            @endif
        </div>

        <div class="flex-1 overflow-y-auto">

            {{-- Tab Tips --}}
            <div x-show="tab === 'tips'" class="p-4 space-y-3">
                @if($page && !empty($page['tips']))
                    <p class="text-xs text-slate-500">{{ $page['descripcion'] ?? '' }}</p>
                    @foreach($page['tips'] as $tip)
                        <div class="flex gap-2.5 text-sm text-slate-700 bg-amber-50 rounded-lg p-3 border border-amber-100">
                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.001z"/>
                            </svg>
                            <span>{{ $tip }}</span>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-8 text-slate-400">
                        <svg class="w-8 h-8 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <p class="text-sm">No hay tips para esta página.</p>
                        <a href="{{ route('ayuda') }}" wire:navigate class="text-xs text-amber-600 hover:underline mt-1 block">Ver el centro de ayuda completo</a>
                    </div>
                @endif
            </div>

            {{-- Tab FAQ --}}
            <div x-show="tab === 'faq'" x-data="{ open: null }" class="p-4 space-y-2">
                @if($page && !empty($page['faq']))
                    @foreach($page['faq'] as $i => $item)
                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                            <button @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                                    class="w-full flex items-start justify-between gap-2 px-3 py-2.5 text-left text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                                <span>{{ $item['q'] }}</span>
                                <svg class="w-4 h-4 flex-shrink-0 text-slate-400 transition-transform duration-150 mt-0.5"
                                     :class="open === {{ $i }} ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open === {{ $i }}"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="px-3 pb-3 text-sm text-slate-600 bg-slate-50 border-t border-slate-200">
                                <p class="pt-2">{{ $item['a'] }}</p>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-8 text-slate-400">
                        <p class="text-sm">No hay preguntas frecuentes para esta página.</p>
                        <a href="{{ route('ayuda') }}" wire:navigate class="text-xs text-amber-600 hover:underline mt-1 block">Ver el centro de ayuda completo</a>
                    </div>
                @endif
            </div>

            {{-- Tab Guías --}}
            @if($guides->isNotEmpty())
            <div x-show="tab === 'guides'" x-data="{ open: null }" class="p-4 space-y-2">
                @foreach($guides as $gi => $guide)
                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <button @click="open === {{ $gi }} ? open = null : open = {{ $gi }}"
                                class="w-full flex items-center justify-between gap-2 px-3 py-2.5 text-left text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                            <span class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-amber-500 text-white text-xs flex items-center justify-center font-bold flex-shrink-0">{{ $gi + 1 }}</span>
                                {{ $guide['titulo'] }}
                            </span>
                            <svg class="w-4 h-4 flex-shrink-0 text-slate-400 transition-transform duration-150"
                                 :class="open === {{ $gi }} ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open === {{ $gi }}"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             class="border-t border-slate-200 bg-slate-50">
                            <ol class="px-4 py-3 space-y-2">
                                @foreach($guide['pasos'] as $pi => $paso)
                                    <li class="flex gap-2.5 text-sm text-slate-600">
                                        <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-600 text-xs flex items-center justify-center font-semibold flex-shrink-0">{{ $pi + 1 }}</span>
                                        {{ $paso }}
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif

        </div>
    </div>

    {{-- Footer --}}
    <div class="flex-shrink-0 border-t border-slate-200 px-4 py-3 bg-slate-50">
        <a href="{{ route('ayuda') }}" wire:navigate
           @click="helpOpen = false"
           class="flex items-center justify-center gap-2 w-full text-xs text-amber-700 hover:text-amber-900 font-medium transition-colors py-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            Centro de ayuda completo
        </a>
    </div>

</div>
