{{--
    First-visit "which are you" prompt on the homepage - dismissed permanently (per
    browser) the moment a visitor picks either option or closes it, via the same
    localStorage-flag + try/catch convention used by the chat-assistant's first-visit
    nudge (see resources/views/livewire/chat-assistant.blade.php). No cookie/session
    fallback - if storage is blocked, the popup just doesn't persist "seen", which is
    an acceptable degrade (matches the chat-assistant precedent).
--}}
<div
    x-data="{
        open: false,
        init() {
            let seen = false;
            try { seen = !!localStorage.getItem('renty_intent_seen'); } catch (e) {}
            if (! seen) {
                setTimeout(() => { this.open = true }, 700);
            }
        },
        dismiss() {
            this.open = false;
            try { localStorage.setItem('renty_intent_seen', '1'); } catch (e) {}
        },
    }"
    x-show="open"
    x-cloak
    x-transition.opacity
    @keydown.escape.window="dismiss()"
    @click.self="dismiss()"
    class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 px-4 pb-4 sm:items-center sm:p-4"
>
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        class="w-full space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900 sm:max-w-md"
        @click.stop
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">Welcome to Renty 👋</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">What brings you here today?</p>
            </div>
            <button type="button" @click="dismiss()" class="flex-shrink-0 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300" aria-label="Close">
                @svg('heroicon-o-x-mark', 'w-5 h-5')
            </button>
        </div>

        <div class="space-y-3">
            <button type="button" @click="dismiss()" class="flex w-full items-center gap-3 rounded-xl border border-slate-200 p-4 text-left transition hover:border-emerald-400 hover:bg-emerald-50/50 dark:border-slate-700 dark:hover:border-emerald-500/40 dark:hover:bg-emerald-500/5">
                <span class="grid h-11 w-11 flex-shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                    @svg('heroicon-o-home', 'w-5 h-5')
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-slate-900 dark:text-slate-100">I'm looking for a house</span>
                    <span class="block text-xs text-slate-500 dark:text-slate-400">Browse long-term rentals and furnished stays</span>
                </span>
            </button>

            <a href="{{ route('for-landlords') }}" @click="dismiss()" class="flex w-full items-center gap-3 rounded-xl border border-slate-200 p-4 text-left transition hover:border-emerald-400 hover:bg-emerald-50/50 dark:border-slate-700 dark:hover:border-emerald-500/40 dark:hover:bg-emerald-500/5">
                <span class="grid h-11 w-11 flex-shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                    @svg('heroicon-o-building-office-2', 'w-5 h-5')
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-slate-900 dark:text-slate-100">I list and manage properties</span>
                    <span class="block text-xs text-slate-500 dark:text-slate-400">See how Renty runs your rentals for you</span>
                </span>
            </a>
        </div>
    </div>
</div>
