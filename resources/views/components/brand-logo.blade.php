@props(['imgClass' => 'h-8', 'textClass' => 'text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100'])
@php
    $logoPath = \App\Models\Setting::forLandlord(null)->payload['logo_path'] ?? null;
    $logoUrl = $logoPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath) : null;
@endphp
@if ($logoUrl)
    <img src="{{ $logoUrl }}" alt="{{ config('app.name', 'Renty') }}" {{ $attributes->merge(['class' => "{$imgClass} w-auto object-contain"]) }}>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
        <span class="grid {{ $imgClass }} aspect-square place-items-center rounded-lg bg-emerald-600 text-sm font-bold text-white">R</span>
        <span class="{{ $textClass }}">{{ config('app.name', 'Renty') }}</span>
    </span>
@endif
