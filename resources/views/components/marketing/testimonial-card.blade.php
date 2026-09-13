@props(['quote', 'name', 'role', 'building'])

{{-- PLACEHOLDER TESTIMONIAL - replace with real customer quotes once available --}}
<div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="flex gap-1 text-amber-400">
        @for ($i = 0; $i < 5; $i++)
            @svg('heroicon-s-star', 'w-4 h-4')
        @endfor
    </div>
    <p class="mt-4 text-slate-700 leading-relaxed dark:text-slate-300">&ldquo;{{ $quote }}&rdquo;</p>
    <div class="mt-6 flex items-center gap-3">
        <div class="h-10 w-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-semibold dark:bg-emerald-500/10 dark:text-emerald-400">
            {{ strtoupper(substr($name, 0, 1)) }}
        </div>
        <div>
            <p class="font-semibold text-slate-900 text-sm dark:text-slate-100">{{ $name }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $role }}, {{ $building }}</p>
        </div>
    </div>
</div>
