<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Renty') }} - Renty</title>
    <meta name="description" content="{{ $description ?? 'Renty is an all-in-one rental management platform for Kenyan landlords and property managers - M-Pesa rent collection, tenant portal, maintenance tracking, and more.' }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-50 text-slate-900 antialiased">
    <x-marketing.nav />

    <main>
        {{ $slot }}
    </main>

    <x-marketing.footer />
</body>
</html>
