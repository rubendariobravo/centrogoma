@props(['tipo'])
{{-- Listado de saldos con los comprobantes pendientes de cada cuenta. --}}
<div x-data="{ open: false }" class="relative">
    <button type="button" x-on:click="open = !open" x-on:click.outside="open = false"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-[#DDE1E7] text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Listado de saldos
    </button>
    <div x-show="open" x-cloak x-transition
         class="absolute right-0 mt-1 w-64 bg-white border border-[#DDE1E7] rounded-lg shadow-lg z-20 py-1 text-sm">
        @foreach(['deudores' => $tipo === 'clientes' ? 'Con deuda' : 'Les debemos', 'a_favor' => 'Saldos a favor', 'todos' => 'Todos con saldo'] as $filtro => $label)
            <div class="flex items-center justify-between px-3 py-1.5 hover:bg-gray-50">
                <span class="text-gray-700">{{ $label }}</span>
                <span class="flex gap-1 text-xs">
                    <a href="{{ route('cc.saldos', ['tipo' => $tipo, 'filtro' => $filtro, 'formato' => 'pdf']) }}" target="_blank" class="px-2 py-0.5 rounded bg-slate-800 text-white hover:bg-slate-700">PDF</a>
                    <a href="{{ route('cc.saldos', ['tipo' => $tipo, 'filtro' => $filtro, 'formato' => 'xlsx']) }}" class="px-2 py-0.5 rounded border border-[#DDE1E7] text-gray-700 hover:bg-white">Excel</a>
                </span>
            </div>
        @endforeach
    </div>
</div>
