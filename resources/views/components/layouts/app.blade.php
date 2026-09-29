<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="/images/logo-rdb-azul.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-[#F4F5F7] text-gray-900">

<div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false, helpOpen: false }" data-route="{{ request()->route()?->getName() ?? '' }}">

    {{-- Overlay móvil --}}
    <div
        x-show="sidebarOpen"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-20 bg-black/60 lg:hidden"
        x-cloak
    ></div>

    {{-- Sidebar --}}
    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-30 w-64 flex-shrink-0 bg-slate-900 transition-transform duration-200 ease-in-out lg:static lg:translate-x-0"
    >
        <livewire:layout.navigation />
    </aside>

    {{-- Área de contenido --}}
    <div class="flex flex-1 flex-col min-w-0 overflow-hidden">

        {{-- Barra superior --}}
        <header class="flex items-center gap-3 bg-white border-b border-[#DDE1E7] px-4 h-14 flex-shrink-0">
            {{-- Hamburger --}}
            <button @click="sidebarOpen = true" class="lg:hidden p-1.5 rounded-md text-gray-500 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Título de página --}}
            <div class="flex-1 min-w-0">
                @isset($header)
                    {{ $header }}
                @endisset
            </div>

            {{-- Indicador de caja abierta --}}
            @php
                $cajaActual = auth()->check()
                    ? \App\Models\Caja::where('estado', 'abierta')->where('tipo', 'diaria')->where('user_id', auth()->id())->latest()->first()
                    : null;
            @endphp
            @if($cajaActual)
                <a href="{{ route('cajas') }}" wire:navigate
                   class="hidden sm:flex items-center gap-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-3 py-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Caja abierta
                </a>
            @else
                <a href="{{ route('cajas') }}" wire:navigate
                   class="hidden sm:flex items-center gap-1.5 text-xs font-medium text-gray-500 bg-gray-100 rounded-full px-3 py-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                    Sin caja
                </a>
            @endif

            {{-- Usuario --}}
            <div class="flex items-center gap-2 text-sm text-gray-700">
                <span class="hidden sm:block font-medium">{{ auth()->user()?->name }}</span>
                <div class="w-8 h-8 rounded-full bg-slate-700 text-white flex items-center justify-center text-xs font-bold uppercase">
                    {{ substr(auth()->user()?->name ?? 'U', 0, 1) }}
                </div>
            </div>
        </header>

        {{-- Contenido principal --}}
        <main class="flex-1 overflow-y-auto p-6">
            {{ $slot }}
        </main>

    </div>

    {{-- Overlay que cierra el drawer al hacer clic fuera --}}
    <div
        x-show="helpOpen"
        @click="helpOpen = false"
        class="fixed inset-0 z-40 bg-transparent"
        x-cloak
    ></div>

    {{-- Botón flotante de ayuda --}}
    <button
        @click.stop="helpOpen = !helpOpen"
        class="fixed bottom-6 right-6 z-50 w-12 h-12 rounded-full shadow-lg hover:shadow-xl transition-all duration-150 flex items-center justify-center"
        :class="helpOpen ? 'bg-slate-700 hover:bg-slate-800' : 'bg-amber-500 hover:bg-amber-600'"
        title="Ayuda contextual"
    >
        <svg x-show="!helpOpen" class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" x-cloak>
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <svg x-show="helpOpen" class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" x-cloak>
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    {{-- Drawer de ayuda contextual (Livewire: se re-renderiza en cada navegación) --}}
    <livewire:layout.help-drawer />

</div>

@livewireScripts
</body>
</html>
