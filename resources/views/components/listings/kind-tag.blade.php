@props(['mode'])
@php $isStay = $mode === 'short_term'; @endphp
<span @class([
    'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium',
    'bg-slate-900/85 text-white' => $isStay,
    'bg-white/95 text-slate-700' => !$isStay,
])>{{ $isStay ? 'Stay' : 'Home' }}</span>
