@props([
    'vacio'    => 'Buscar cliente…',   // qué significa "sin cliente" (ej. Consumidor final, Todos los clientes)
    'disabled' => false,
    'error'    => null,                // clave de error de validación a mostrar (ej. cliente_id)
])

{{-- Buscador de clientes server-side. Requiere el trait App\Livewire\Concerns\BuscaCliente en el componente. --}}
@php
    $seleccionado = $this->clienteSeleccionado();
    $resultados   = $this->clientesEncontrados();
    $buscando     = mb_strlen(trim($this->clienteBuscar)) >= 2;
@endphp

@if($disabled)
    <p class="w-full px-3 py-2 text-sm text-gray-700 bg-gray-50 border border-[#DDE1E7] rounded-lg">
        {{ $seleccionado?->razon_social ?? $vacio }}
    </p>
@else
<div class="relative" x-data="{ abierto: false, i: 0 }" @click.outside="abierto = false" @keydown.escape="abierto = false">
    <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
        </svg>
        <input type="text" autocomplete="off"
               wire:model.live.debounce.300ms="clienteBuscar"
               @focus="abierto = true" @input="abierto = true; i = 0"
               @keydown.arrow-down.prevent="i = Math.min(i + 1, $refs.lista ? $refs.lista.children.length - 1 : 0)"
               @keydown.arrow-up.prevent="i = Math.max(i - 1, 0)"
               @keydown.enter.prevent="$refs.lista && $refs.lista.children[i] && $refs.lista.children[i].click()"
               placeholder="{{ $seleccionado ? $seleccionado->razon_social : $vacio }}"
               class="w-full pl-9 {{ $seleccionado ? 'pr-8 placeholder:text-gray-900' : 'pr-3 placeholder:text-gray-400' }} py-2 text-sm border {{ $error && $errors->has($error) ? 'border-red-400' : 'border-[#DDE1E7]' }} rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none bg-white">
        @if($seleccionado)
            <button type="button" wire:click="seleccionarCliente(null)" title="Quitar cliente"
                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-700 rounded">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        @endif
    </div>

    @if($buscando)
    <div x-show="abierto" x-cloak
         class="absolute z-30 mt-1 w-full bg-white border border-[#DDE1E7] rounded-lg shadow-lg max-h-72 overflow-y-auto">
        @if($resultados->isEmpty())
            <p class="px-3 py-3 text-sm text-gray-400">Sin resultados para “{{ $this->clienteBuscar }}”</p>
        @else
            <ul x-ref="lista">
                @foreach($resultados as $idx => $c)
                <li wire:key="bc-{{ $c->id }}"
                    wire:click="seleccionarCliente({{ $c->id }})" @click="abierto = false"
                    @mouseenter="i = {{ $idx }}"
                    :class="i === {{ $idx }} ? 'bg-slate-100' : ''"
                    class="px-3 py-2 cursor-pointer">
                    <p class="text-sm text-gray-900">{{ $c->razon_social }}</p>
                    <p class="text-xs text-gray-400">
                        {{ collect([$c->cuit ? 'CUIT ' . $c->cuit : ($c->dni ? 'DNI ' . $c->dni : null), $c->nombre_fantasia, $c->localidad])->filter()->implode(' · ') }}
                    </p>
                </li>
                @endforeach
            </ul>
        @endif
    </div>
    @endif
</div>
@endif

@if($error)
    @error($error) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
@endif
