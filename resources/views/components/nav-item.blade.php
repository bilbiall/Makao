@props(['item'])
@php $active = request()->routeIs($item['route']); @endphp
<a href="{{ route($item['route']) }}"
   @class([
       'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
       'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => $active,
       'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100' => !$active,
   ])>
    @svg($item['icon'], 'w-5 h-5 flex-shrink-0')
    <span class="flex-1">{{ $item['label'] }}</span>
    @if ($item['label'] === 'Chat')
        <livewire:chat-unread-badge :key="'chat-badge-sidebar-'.$item['route']" />
    @elseif ($item['label'] === 'Viewing Requests')
        <livewire:viewing-requests-badge :key="'viewing-requests-badge-sidebar'" />
    @endif
</a>
