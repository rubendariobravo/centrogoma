@props(['text', 'position' => 'top'])

<span x-data="{ show: false }" class="relative inline-flex items-center">
    <button
        type="button"
        @mouseenter="show = true"
        @mouseleave="show = false"
        @focus="show = true"
        @blur="show = false"
        class="ml-1 w-4 h-4 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-500 hover:text-slate-700 flex items-center justify-center transition-colors flex-shrink-0"
        tabindex="-1"
    >
        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
        </svg>
    </button>

    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @class([
            'absolute z-50 w-56 bg-slate-800 text-white text-xs rounded-lg px-3 py-2 shadow-xl pointer-events-none',
            'bottom-full left-1/2 -translate-x-1/2 mb-2' => $position === 'top',
            'top-full left-1/2 -translate-x-1/2 mt-2'   => $position === 'bottom',
            'right-full top-1/2 -translate-y-1/2 mr-2'  => $position === 'left',
            'left-full top-1/2 -translate-y-1/2 ml-2'   => $position === 'right',
        ])
        x-cloak
    >
        {{ $text }}
    </div>
</span>
