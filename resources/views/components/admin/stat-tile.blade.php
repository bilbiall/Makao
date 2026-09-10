@props(['label', 'value', 'color' => 'slate'])

@php
$colors = [
    'emerald' => 'bg-emerald-50 border-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400',
    'amber' => 'bg-amber-50 border-amber-100 text-amber-700 dark:bg-amber-500/10 dark:border-amber-500/20 dark:text-amber-400',
    'rose' => 'bg-rose-50 border-rose-100 text-rose-700 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400',
    'sky' => 'bg-sky-50 border-sky-100 text-sky-700 dark:bg-sky-500/10 dark:border-sky-500/20 dark:text-sky-400',
    'slate' => 'bg-slate-50 border-slate-200 text-slate-700 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300',
][$color] ?? $colors['slate'] ?? '';

$valueColors = [
    'emerald' => 'text-emerald-800 dark:text-emerald-300',
    'amber' => 'text-amber-800 dark:text-amber-300',
    'rose' => 'text-rose-800 dark:text-rose-300',
    'sky' => 'text-sky-800 dark:text-sky-300',
    'slate' => 'text-slate-900 dark:text-slate-100',
][$color] ?? 'text-slate-900 dark:text-slate-100';
@endphp

<div {{ $attributes->merge(['class' => "rounded-xl border p-2.5 $colors"]) }}>
    <p class="text-[11px] leading-tight">{{ $label }}</p>
    <p class="mt-0.5 text-sm font-bold {{ $valueColors }}">{{ $value }}</p>
</div>
