@props(['name' => 'password', 'inputClass' => ''])
<div x-data="{ show: false }" class="relative">
    <input
        :type="show ? 'text' : 'password'"
        name="{{ $name }}"
        {{ $attributes->merge(['class' => $inputClass . ' pr-11']) }}
    >
    <button
        type="button"
        @click="show = !show"
        tabindex="-1"
        class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300"
    >
        <svg x-show="!show" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        <svg x-show="show" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.967 9.967 0 012.148-3.482M9.88 9.88a3 3 0 104.24 4.24M6.1 6.1l11.8 11.8" />
        </svg>
    </button>
</div>
