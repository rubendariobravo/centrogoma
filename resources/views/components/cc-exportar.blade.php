@props(['tipo', 'id'])
{{-- Resumen de cuenta imprimible / exportable, con período opcional (sin fechas = todos los movimientos). --}}
<div x-data="{ desde: '', hasta: '',
               url(f) { const p = new URLSearchParams({ formato: f }); if (this.desde) p.set('desde', this.desde); if (this.hasta) p.set('hasta', this.hasta);
                        return '{{ route('cc.resumen', ['tipo' => $tipo, 'id' => $id]) }}?' + p } }"
     class="px-4 py-2.5 border-b border-[#DDE1E7] bg-[#F7F8FA] flex flex-wrap items-center gap-2 text-xs">
    <span class="text-gray-500 font-medium">Resumen de cuenta</span>
    <label class="text-gray-400">desde</label>
    <input type="date" x-model="desde" class="px-2 py-1 border border-[#DDE1E7] rounded-md text-xs">
    <label class="text-gray-400">hasta</label>
    <input type="date" x-model="hasta" class="px-2 py-1 border border-[#DDE1E7] rounded-md text-xs">
    <a :href="url('pdf')" target="_blank" class="ml-auto px-2.5 py-1 rounded-md bg-slate-800 text-white hover:bg-slate-700">Imprimir PDF</a>
    <a :href="url('xlsx')" class="px-2.5 py-1 rounded-md border border-[#DDE1E7] bg-white text-gray-700 hover:bg-gray-50">Excel</a>
</div>
