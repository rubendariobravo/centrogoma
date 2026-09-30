@props(['titulo', 'descripcion'])

{{-- Modal de anulación de comprobante. Espera en el componente Livewire: motivoAnulacion, anular(), cancelarAnular(). --}}
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:keydown.escape.window="cancelarAnular">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-base font-semibold text-gray-900">{{ $titulo }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ $descripcion }}</p>

        {{ $slot }}

        <label class="block mt-4 text-xs font-medium text-gray-600 mb-1">Motivo</label>
        <textarea wire:model="motivoAnulacion" rows="2"
                  class="w-full px-3 py-2 text-sm border {{ $errors->has('motivoAnulacion') ? 'border-red-400' : 'border-[#DDE1E7]' }} rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-transparent outline-none"></textarea>
        @error('motivoAnulacion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

        <div class="mt-5 flex justify-end gap-2">
            <button type="button" wire:click="cancelarAnular"
                    class="px-4 py-2 text-sm text-gray-700 bg-white border border-[#DDE1E7] rounded-lg hover:bg-gray-50">Cancelar</button>
            <button type="button" wire:click="anular" wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Anular</button>
        </div>
    </div>
</div>
