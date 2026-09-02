@props(['class' => ''])
<button
    type="button"
    x-data
    @click="
        const html = document.documentElement;
        const nowDark = !html.classList.contains('dark');
        html.classList.toggle('dark', nowDark);
        try { localStorage.setItem('theme', nowDark ? 'dark' : 'light'); } catch (e) {}
    "
    aria-label="Toggle dark mode"
    title="Toggle dark mode"
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-full transition-colors {$class}"]) }}
>
    <span class="dark:hidden">@svg('heroicon-o-moon', 'w-5 h-5')</span>
    <span class="hidden dark:inline">@svg('heroicon-o-sun', 'w-5 h-5')</span>
</button>
