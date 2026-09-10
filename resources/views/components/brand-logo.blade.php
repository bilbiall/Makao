@props(['imgClass' => 'h-8', 'textClass' => 'text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100'])
@php
    $payload = \App\Models\Setting::forLandlord(null)->payload ?? [];
    $logoPath = $payload['logo_path'] ?? null;
    $logoDarkPath = $payload['logo_path_dark'] ?? null;
    $lightUrl = $logoPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath) : null;
    $darkUrl = $logoDarkPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoDarkPath) : null;
    $hasBothVariants = $lightUrl && $darkUrl;
@endphp
@if ($lightUrl || $darkUrl)
    <img src="{{ $lightUrl ?? $darkUrl }}" alt="{{ config('app.name', 'Renty') }}"
        {{ $attributes->merge(['class' => "{$imgClass} w-auto object-contain" . ($hasBothVariants ? ' dark:hidden' : '')]) }}>
    @if ($hasBothVariants)
        <img src="{{ $darkUrl }}" alt="{{ config('app.name', 'Renty') }}"
            {{ $attributes->merge(['class' => "{$imgClass} w-auto object-contain hidden dark:block"]) }}>
    @endif
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
        <span class="grid {{ $imgClass }} aspect-square place-items-center rounded-lg bg-emerald-600 text-sm font-bold text-white">R</span>
        <span class="{{ $textClass }}">{{ config('app.name', 'Renty') }}</span>
    </span>
@endif
