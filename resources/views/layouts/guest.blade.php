<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — Acceso</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased h-screen overflow-hidden">

<div class="flex h-full">

    {{-- ===== Panel izquierdo: branding ===== --}}
    <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 relative flex-col justify-between p-10 overflow-hidden"
         style="background: linear-gradient(150deg, #142B4F 0%, #0f2040 50%, #091628 100%)">

        {{-- Patrón de puntos decorativo --}}
        <div class="absolute inset-0 opacity-[0.04]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 32px 32px;"></div>

        {{-- Círculos decorativos de fondo --}}
        <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full opacity-10" style="background: radial-gradient(circle, #77848C, transparent)"></div>
        <div class="absolute -bottom-24 -left-24 w-80 h-80 rounded-full opacity-10" style="background: radial-gradient(circle, #142B4F, transparent)"></div>

        {{-- TOP: Logo RDB --}}
        <div class="relative z-10 flex items-center gap-3">
            <img src="{{ asset('images/logo-rdb-blanco.png') }}" alt="RDB Tecnologías Informáticas" class="h-9">
            <div class="h-6 border-l border-white/20"></div>
            <div>
                <p class="text-white/50 text-xs leading-none">www.rdb-ti.com</p>
            </div>
        </div>

        {{-- CENTRO: Contenido principal --}}
        <div class="relative z-10 max-w-lg">

            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 mb-6 px-3.5 py-1.5 rounded-full border border-white/20 bg-white/10 backdrop-blur-sm">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse flex-shrink-0"></span>
                <span class="text-white/80 text-xs font-medium tracking-wide">Sistema de Gestión Comercial</span>
            </div>

            {{-- Heading --}}
            <h1 class="text-3xl xl:text-4xl font-bold text-white leading-tight mb-4">
                Gestión comercial de <span class="text-amber-400">{{ config('app.name') }}</span>
            </h1>

            <p class="text-white/55 text-sm xl:text-base leading-relaxed mb-8">
                Plataforma ERP para la administración integral del negocio:
                ventas, presupuestos, compras, stock, cheques y finanzas en un solo lugar.
            </p>

            {{-- Feature cards --}}
            <div class="grid grid-cols-2 gap-3">

                <div class="rounded-xl p-4 border border-white/10 bg-white/5 backdrop-blur-sm hover:bg-white/8 transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-amber-400/20 flex items-center justify-center mb-3">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <p class="text-white text-sm font-semibold leading-tight">Ventas & Presupuestos</p>
                    <p class="text-white/45 text-xs mt-0.5">Mayorista y minorista</p>
                </div>

                <div class="rounded-xl p-4 border border-white/10 bg-white/5 backdrop-blur-sm hover:bg-white/8 transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-emerald-400/20 flex items-center justify-center mb-3">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <p class="text-white text-sm font-semibold leading-tight">Stock & Precios</p>
                    <p class="text-white/45 text-xs mt-0.5">Listas y precios por cantidad</p>
                </div>

                <div class="rounded-xl p-4 border border-white/10 bg-white/5 backdrop-blur-sm hover:bg-white/8 transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-blue-400/20 flex items-center justify-center mb-3">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <p class="text-white text-sm font-semibold leading-tight">Clientes & Proveedores</p>
                    <p class="text-white/45 text-xs mt-0.5">Cuentas corrientes</p>
                </div>

                <div class="rounded-xl p-4 border border-white/10 bg-white/5 backdrop-blur-sm hover:bg-white/8 transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-purple-400/20 flex items-center justify-center mb-3">
                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <p class="text-white text-sm font-semibold leading-tight">Caja & Cheques</p>
                    <p class="text-white/45 text-xs mt-0.5">Caja diaria, cartera y bancos</p>
                </div>

            </div>
        </div>

        {{-- BOTTOM: footer --}}
        <div class="relative z-10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-3.5 h-3.5 text-white/30" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-white/30 text-xs">Salta, Argentina</span>
            </div>
            <p class="text-white/25 text-xs">© {{ date('Y') }} RDB Tecnologías Informáticas</p>
        </div>

    </div>

    {{-- ===== Panel derecho: formulario ===== --}}
    <div class="flex-1 flex flex-col items-center justify-center bg-[#F4F5F7] px-6 sm:px-12 overflow-y-auto">

        {{-- Logo móvil --}}
        <div class="lg:hidden mb-8 text-center">
            <img src="{{ asset('images/logo-rdb-azul.png') }}" alt="RDB" class="h-8 mx-auto mb-2">
            <p class="text-gray-500 text-sm">{{ config('app.name') }} — Gestión Comercial</p>
        </div>

        <div class="w-full max-w-sm">

            {{-- Heading --}}
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900">Iniciar sesión</h2>
                <p class="text-sm text-gray-500 mt-1">Ingresá tus credenciales para acceder al sistema</p>
            </div>

            {{-- Slot del formulario --}}
            {{ $slot }}

            {{-- Footer del panel --}}
            <div class="mt-8 pt-6 border-t border-[#DDE1E7] text-center">
                <p class="text-xs text-gray-400">
                    Desarrollado por
                    <a href="https://www.rdb-ti.com" target="_blank" rel="noopener" class="text-[#142B4F] hover:underline font-medium">RDB Tecnologías Informáticas</a>
                </p>
                <p class="text-xs text-gray-400 mt-0.5">Sistemas críticos para operaciones que no pueden fallar</p>
            </div>

        </div>
    </div>

</div>

</body>
</html>
