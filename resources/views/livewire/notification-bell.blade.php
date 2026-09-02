<div class="relative" wire:poll.20s x-on:click.outside="$wire.open = false">
    <button wire:click="toggle" class="relative p-2 rounded-full text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 transition" aria-label="Notifications">
        @svg('heroicon-o-bell', 'w-5 h-5')
        @if ($unreadCount > 0)
            <span class="absolute top-0.5 right-0.5 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-rose-600 text-white text-[10px] font-semibold leading-[1.1rem] text-center">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    @if ($open)
        <div class="absolute right-0 mt-2 w-80 max-w-[90vw] bg-white rounded-xl border border-slate-200 shadow-lg z-50 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">Notifications</span>
                @if ($unreadCount > 0)
                    <button wire:click="markAllRead" class="text-xs font-medium text-emerald-700 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300">Mark all read</button>
                @endif
            </div>

            <div class="max-h-96 overflow-y-auto divide-y divide-slate-50 dark:divide-slate-800">
                @forelse ($notifications as $n)
                    <button type="button" wire:click="openNotification('{{ $n->id }}')"
                        class="w-full text-left px-4 py-3 hover:bg-stone-50 dark:hover:bg-slate-800 transition {{ $n->read_at ? '' : 'bg-emerald-50/50 dark:bg-emerald-500/10' }}">
                        <div class="flex items-start gap-2">
                            @if (!$n->read_at)
                                <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-emerald-600 flex-shrink-0"></span>
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">{{ $n->data['title'] ?? 'Notification' }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2">{{ $n->data['message'] ?? '' }}</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">{{ $n->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </button>
                @empty
                    <p class="text-sm text-slate-400 dark:text-slate-500 text-center py-8">No notifications yet.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
