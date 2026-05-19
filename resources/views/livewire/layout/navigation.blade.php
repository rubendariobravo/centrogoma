<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $isActive = fn(string $route) => request()->routeIs($route) ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white';
    $isGroup  = fn(array $routes) => collect($routes)->some(fn($r) => request()->routeIs($r));
@endphp

<div class="flex flex-col h-full overflow-y-auto">

    {{-- Logo --}}
    <div class="flex items-center gap-3 px-4 py-4 bg-slate-950 border-b border-slate-800 flex-shrink-0">
        <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-black text-sm">CG</div>
        <span class="font-semibold text-white text-sm leading-tight">Centro Goma</span>
    </div>

    {{-- Navegación --}}
    <nav class="flex-1 px-2 py-3 space-y-0.5 text-sm">

        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}" wire:navigate
           class="flex items-center gap-2.5 px-3 py-2 rounded-md font-medium transition-colors {{ $isActive('dashboard') }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Dashboard
        </a>

        {{-- VENTAS --}}
        <div x-data="{ open: {{ $isGroup(['ventas*','clientes*','recibos*','presupuestos*']) ? 'true' : 'true' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-md text-slate-400 hover:text-white transition-colors">
                <span class="flex items-center gap-2.5 font-medium text-xs uppercase tracking-wider">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Ventas
                </span>
                <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" class="mt-0.5 space-y-0.5 pl-4">
                <a href="{{ route('ventas') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('ventas*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Ventas
                </a>
                <a href="{{ route('presupuestos') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('presupuestos*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Presupuestos
                </a>
                <a href="{{ route('clientes') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('clientes*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Clientes
                </a>
                <a href="{{ route('recibos') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('recibos*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Recibos
                </a>
            </div>
        </div>

        {{-- COMPRAS --}}
        <div x-data="{ open: {{ $isGroup(['compras*','proveedores*','ordenes-pago*']) ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-md text-slate-400 hover:text-white transition-colors">
                <span class="flex items-center gap-2.5 font-medium text-xs uppercase tracking-wider">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Compras
                </span>
                <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" class="mt-0.5 space-y-0.5 pl-4">
                <a href="{{ route('compras') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('compras*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Compras
                </a>
                <a href="{{ route('proveedores') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('proveedores*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Proveedores
                </a>
                <a href="{{ route('ordenes-pago') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('ordenes-pago*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Órdenes de Pago
                </a>
            </div>
        </div>

        {{-- ARTÍCULOS --}}
        <div x-data="{ open: {{ $isGroup(['articulos*','rubros*','marcas*','listas-precios*']) ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-md text-slate-400 hover:text-white transition-colors">
                <span class="flex items-center gap-2.5 font-medium text-xs uppercase tracking-wider">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    Artículos
                </span>
                <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" class="mt-0.5 space-y-0.5 pl-4">
                <a href="{{ route('articulos') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('articulos*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Artículos
                </a>
                <a href="{{ route('rubros') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('rubros*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Rubros
                </a>
                <a href="{{ route('marcas') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('marcas*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Marcas
                </a>
                <a href="{{ route('listas-precios') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('listas-precios*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Listas de precios
                </a>
            </div>
        </div>

        {{-- CAJA & FINANZAS --}}
        <div x-data="{ open: {{ $isGroup(['cajas*','cc-clientes*','cc-proveedores*','cheques*']) ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-md text-slate-400 hover:text-white transition-colors">
                <span class="flex items-center gap-2.5 font-medium text-xs uppercase tracking-wider">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Caja y Finanzas
                </span>
                <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" class="mt-0.5 space-y-0.5 pl-4">
                <a href="{{ route('cajas') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('cajas*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Cajas
                </a>
                <a href="{{ route('cc-clientes') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('cc-clientes*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> CC Clientes
                </a>
                <a href="{{ route('cc-proveedores') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('cc-proveedores*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> CC Proveedores
                </a>
                <a href="{{ route('cheques') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('cheques*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Cheques
                </a>
            </div>
        </div>

        {{-- LOGÍSTICA --}}
        <div x-data="{ open: {{ $isGroup(['remitos*','empleados*']) ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-md text-slate-400 hover:text-white transition-colors">
                <span class="flex items-center gap-2.5 font-medium text-xs uppercase tracking-wider">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 17H5a2 2 0 01-2-2V5a2 2 0 012-2h11a2 2 0 012 2v3m-7 14l4-4m0 0l4-4m-4 4V10"/>
                    </svg>
                    Logística
                </span>
                <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" class="mt-0.5 space-y-0.5 pl-4">
                <a href="{{ route('remitos') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('remitos*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Remitos
                </a>
                <a href="{{ route('empleados') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('empleados*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Empleados
                </a>
            </div>
        </div>

        {{-- ADMINISTRACIÓN --}}
        <div x-data="{ open: {{ $isGroup(['usuarios*','roles*','config*','auditorias*','ayuda*']) ? 'true' : 'false' }} }">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-md text-slate-400 hover:text-white transition-colors">
                <span class="flex items-center gap-2.5 font-medium text-xs uppercase tracking-wider">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Administración
                </span>
                <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" class="mt-0.5 space-y-0.5 pl-4">
                <a href="{{ route('usuarios') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('usuarios*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Usuarios
                </a>
                <a href="{{ route('roles') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('roles*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Roles
                </a>
                <a href="{{ route('config') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('config*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Configuración
                </a>
                @can('ver_auditorias')
                <a href="{{ route('auditorias') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('auditorias*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Auditorías
                </a>
                @endcan
                <a href="{{ route('ayuda') }}" wire:navigate class="flex items-center gap-2 px-3 py-1.5 rounded-md transition-colors {{ $isActive('ayuda*') }}">
                    <span class="w-1 h-1 rounded-full bg-current opacity-60"></span> Centro de Ayuda
                </a>
            </div>
        </div>

    </nav>

    {{-- Footer sidebar: usuario + logout --}}
    <div class="flex-shrink-0 border-t border-slate-800 p-3 bg-slate-950">
        <div class="flex items-center gap-2.5 mb-2 px-1">
            <div class="w-7 h-7 rounded-full bg-amber-500 text-slate-900 flex items-center justify-center text-xs font-bold uppercase flex-shrink-0">
                {{ substr(auth()->user()?->name ?? 'U', 0, 1) }}
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-white truncate">{{ auth()->user()?->name }}</p>
                <p class="text-xs text-slate-500 truncate">{{ auth()->user()?->email }}</p>
            </div>
        </div>
        <div class="flex gap-1.5">
            <a href="{{ route('profile') }}" wire:navigate
               class="flex-1 flex items-center justify-center gap-1.5 text-xs text-slate-400 hover:text-white hover:bg-slate-800 rounded px-2 py-1.5 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Perfil
            </a>
            <button wire:click="logout"
                    class="flex-1 flex items-center justify-center gap-1.5 text-xs text-slate-400 hover:text-red-400 hover:bg-slate-800 rounded px-2 py-1.5 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Salir
            </button>
        </div>
    </div>

</div>
