<div class="fixed bottom-20 right-4 z-50 md:bottom-6 md:right-6" x-data="{ open: @entangle('open') }">
    {{-- Launcher --}}
    <button
        type="button"
        wire:click="toggle"
        aria-label="{{ $open ? 'Close chat' : 'Find a home with chat' }}"
        class="grid h-14 w-14 place-items-center rounded-full bg-emerald-600 text-white shadow-lg shadow-emerald-600/30 transition-transform hover:scale-105 hover:bg-emerald-700"
    >
        @if ($open)
            @svg('heroicon-o-x-mark', 'w-6 h-6')
        @else
            @svg('heroicon-o-chat-bubble-left-right', 'w-6 h-6')
        @endif
    </button>

    {{-- Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-on:chat-assistant-message-sent.window="$wire.reply()"
        class="absolute bottom-[4.5rem] right-0 flex h-[70vh] max-h-[560px] w-[92vw] max-w-sm flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-800 dark:bg-slate-900"
        style="display: none;"
    >
        <div class="flex items-center gap-2 border-b border-slate-200 bg-emerald-600 px-4 py-3 text-white dark:border-slate-800">
            @if ($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="" class="h-5 w-5 rounded-full object-cover">
            @else
                @svg('heroicon-o-sparkles', 'w-5 h-5')
            @endif
            <p class="text-sm font-semibold">Find a place</p>
        </div>

        <div
            x-init="
                const observer = new MutationObserver(() => { $refs.scrollBox.scrollTop = $refs.scrollBox.scrollHeight; });
                observer.observe($refs.scrollBox, { childList: true, subtree: true });
            "
            x-ref="scrollBox"
            class="flex-1 space-y-3 overflow-y-auto px-3 py-4"
        >
            @foreach ($messages as $message)
                <div class="flex items-end gap-2 {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                    @if ($message['role'] === 'assistant')
                        <div class="grid h-6 w-6 shrink-0 place-items-center overflow-hidden rounded-full bg-emerald-100 dark:bg-emerald-900">
                            @if ($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="" class="h-full w-full object-cover">
                            @else
                                @svg('heroicon-o-sparkles', 'w-3.5 h-3.5 text-emerald-700 dark:text-emerald-300')
                            @endif
                        </div>
                    @endif
                    <div class="max-w-[80%] rounded-2xl px-3 py-2 text-sm {{ $message['role'] === 'user'
                        ? 'bg-emerald-600 text-white'
                        : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100' }}">
                        {{ $message['text'] }}
                    </div>
                </div>

                @if (! empty($message['cards']))
                    <div class="space-y-2">
                        @foreach ($message['cards'] as $card)
                            <a
                                href="{{ $card['url'] }}"
                                class="group flex gap-3 overflow-hidden rounded-xl border border-slate-200 bg-white p-2 shadow-sm transition-shadow hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                            >
                                <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800">
                                    @if ($card['image'])
                                        <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" loading="lazy" class="h-full w-full object-cover transition-transform group-hover:scale-105">
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1 py-0.5">
                                    <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $card['title'] }}</p>
                                    <p class="mt-0.5 flex items-center gap-1 truncate text-xs text-slate-500 dark:text-slate-400">
                                        @svg('heroicon-o-map-pin', 'w-3.5 h-3.5 shrink-0')
                                        {{ $card['area'] ?? 'Location TBC' }} &middot; {{ $card['type'] }}
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                                        @if ($card['price'])
                                            KES {{ number_format($card['price']) }}<span class="font-normal text-slate-500 dark:text-slate-400">/{{ $card['price_unit'] }}</span>
                                        @else
                                            Price on request
                                        @endif
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach

            <div wire:loading wire:target="reply" class="flex justify-start">
                <div class="flex items-center gap-1 rounded-2xl bg-slate-100 px-3 py-2 dark:bg-slate-800">
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400 [animation-delay:-0.3s]"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400 [animation-delay:-0.15s]"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400"></span>
                </div>
            </div>
        </div>

        <form wire:submit.prevent="send" class="flex items-center gap-2 border-t border-slate-200 p-3 dark:border-slate-800">
            <input
                type="text"
                wire:model="input"
                maxlength="500"
                placeholder="e.g. 1 bedroom in Kasarani under 20k"
                autocomplete="off"
                wire:loading.attr="disabled"
                wire:target="send,reply"
                class="flex-1 rounded-full border border-slate-300 bg-white px-4 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 disabled:opacity-60 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            >
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="send,reply"
                aria-label="Send"
                class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-600 text-white transition-colors hover:bg-emerald-700 disabled:opacity-60"
            >
                @svg('heroicon-o-paper-airplane', 'w-4 h-4')
            </button>
        </form>
    </div>
</div>
