<!DOCTYPE html>
@php $brandPalette = \App\Models\Setting::forLandlord(null)->payload['brand_palette'] ?? 'green'; @endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-palette="{{ $brandPalette }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    @include('partials.theme-init-script')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#133a2f">
    @php
        // $title is the page's own name; the homepage passes none and gets the full
        // brand headline instead of "Hizihome - Hizihome". $noindex keeps transactional
        // pages (bookings, account creation, unavailable listings) out of search.
        $siteName = 'Hizihome';
        $pageTitle = ! empty($title) ? $title . ' | ' . $siteName : $siteName . ' | Rentals and BnB stays in Kenya';
        $pageDescription = $description ?? 'Hizihome helps Kenyans find rentals and BnB stays they can trust, and helps landlords and property managers collect rent by M-Pesa, manage tenants and run their buildings from one dashboard.';
        $pageImage = $image ?? asset('images/brand/hizihome-og.png');
        $usingDefaultImage = empty($image);
        $canonicalUrl = url()->current();
    @endphp
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if (! empty($noindex))
        <meta name="robots" content="noindex, follow">
    @endif

    {{-- Rich link previews (WhatsApp/Facebook/Twitter/etc) - $image is the only prop
         a page needs to pass beyond the existing :title/:description to get one; falls
         back to the branded share card everywhere else so a share is never a bare link. --}}
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="en_KE">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $pageImage }}">
    @if ($usingDefaultImage)
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $pageImage }}">

    @if (request()->routeIs('home'))
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'Organization',
                        '@id' => url('/') . '#organization',
                        'name' => $siteName,
                        'url' => url('/'),
                        'logo' => asset('images/brand/hizihome-icon.png'),
                        'areaServed' => 'KE',
                    ],
                    [
                        '@type' => 'WebSite',
                        '@id' => url('/') . '#website',
                        'url' => url('/'),
                        'name' => $siteName,
                        'inLanguage' => 'en-KE',
                        'publisher' => ['@id' => url('/') . '#organization'],
                        'potentialAction' => [
                            '@type' => 'SearchAction',
                            'target' => url('/houses') . '?area={search_term_string}',
                            'query-input' => 'required name=search_term_string',
                        ],
                    ],
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endif
    @stack('head')

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
