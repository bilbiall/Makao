<!DOCTYPE html>
@php $brandPalette = \App\Models\Setting::forLandlord(null)->payload['brand_palette'] ?? 'green'; @endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-palette="{{ $brandPalette }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    @include('partials.theme-init-script')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#047857">
    <title>{{ $title ?? config('app.name', 'Renty') }} - Renty</title>
    <meta name="description" content="{{ $description ?? 'Renty is an all-in-one rental management platform for Kenyan landlords and property managers - M-Pesa rent collection, tenant portal, maintenance tracking, and more.' }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>body { font-feature-settings: "cv11"; }</style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Google Analytics (GA4) - platform-wide, configured by the superadmin under
         Platform Settings. Only loads on the public marketing site, never on the
         authenticated landlord/tenant/superadmin panels. --}}
    @php $gaId = \App\Models\Setting::forLandlord(null)->payload['google_analytics_id'] ?? null; @endphp
    @if ($gaId)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($gaId));
        </script>
    @endif
</head>
<body data-has-livewire="true" class="bg-stone-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <x-marketing.nav />

    <main class="pb-16 md:pb-0">
        {{ $slot }}
    </main>

    <x-marketing.footer />
    <x-marketing.bottom-tabs />

    <livewire:chat-assistant />

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(() => {});
            });
        }
    </script>
</body>
</html>
