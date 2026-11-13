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
    <img src="{{ $lightUrl ?? $darkUrl }}" alt="{{ config('app.name', 'Hizihome') }}"
        {{ $attributes->merge(['class' => "{$imgClass} w-auto object-contain" . ($hasBothVariants ? ' dark:hidden' : '')]) }}>
    @if ($hasBothVariants)
        <img src="{{ $darkUrl }}" alt="{{ config('app.name', 'Hizihome') }}"
            {{ $attributes->merge(['class' => "{$imgClass} w-auto object-contain hidden dark:block"]) }}>
    @endif
@else
    <img src="{{ asset('images/brand/hizihome-logo.png') }}" alt="{{ config('app.name', 'Hizihome') }}"
        {{ $attributes->merge(['class' => "{$imgClass} w-auto object-contain dark:hidden"]) }}>
    <img src="{{ asset('images/brand/hizihome-logo-white.png') }}" alt="{{ config('app.name', 'Hizihome') }}"
        {{ $attributes->merge(['class' => "{$imgClass} w-auto object-contain hidden dark:block"]) }}>
@endif
