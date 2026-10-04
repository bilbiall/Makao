@php $active = request()->routeIs($item['route']); @endphp
<a href="{{ route($item['route']) }}" class="flex flex-col items-center gap-2 rounded-xl border py-3.5 text-xs font-medium hover:bg-slate-50 dark:hover:bg-slate-800 {{ $active ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300' : 'border-slate-100 text-slate-700 dark:border-slate-800 dark:text-slate-300' }}">
    <span class="relative">
        @svg($item['icon'], 'w-6 h-6 text-emerald-600 dark:text-emerald-400')
        @if ($item['label'] === 'Chat')
            <span class="absolute -top-1 -right-1.5"><livewire:chat-unread-badge :key="'chat-badge-more'" /></span>
        @elseif ($item['label'] === 'Viewing Requests')
            <span class="absolute -top-1 -right-1.5"><livewire:viewing-requests-badge :key="'viewing-requests-badge-more'" /></span>
        @endif
    </span>
    <span class="text-center leading-tight">{{ $item['label'] }}</span>
</a>
