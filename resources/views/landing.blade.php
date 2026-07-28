<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Management Portal</title>

    <!-- Primary SEO -->
<meta name="description"
      content="Renty – an all‑in‑one portal for landlords and tenants to manage rentals, track invoices & payments, raise maintenance issues, and receive real‑time SMS updates." />
<meta name="keywords"
      content="tenant management, rental portal, invoices, payments, maintenance issues, SMS notifications, Renty" />
<meta name="author" content="Vumaa Digital" />

<!-- Open Graph / Facebook -->
<meta property="og:type"        content="website" />
<meta property="og:url"         content="{{ url('/') }}" />
<meta property="og:title"       content="Renty – Tenant Management Made Simple" />
<meta property="og:description" content="Pay rent, download invoices, report issues, and stay informed via instant SMS alerts – all from one sleek dashboard." />
<meta property="og:image"       content="{{ asset('images/og-cover.jpg') }}" />

<!-- Twitter Card -->
<meta name="twitter:card"        content="summary_large_image" />
<meta name="twitter:title"       content="Renty – Tenant Management Made Simple" />
<meta name="twitter:description" content="Manage rentals, bills, and maintenance while enjoying seamless SMS communication between landlords and tenants." />
<meta name="twitter:image"       content="{{ asset('images/og-cover.jpg') }}" />

<!-- App / theme colours -->
<meta name="theme-color"  content="#0f172a" />
<meta name="msapplication-TileColor" content="#0f172a" />




    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body class="relative min-h-screen bg-cover bg-center bg-no-repeat text-white"
      style="background-image: url('{{ asset('images/background1.jpg') }}');">

    <div class="absolute inset-0 bg-black bg-opacity-60"></div>



    <!--<div class="relative z-10 max-w-3xl mx-auto p-10 text-center fade-in-up">
     <div class="relative z-10 max-w-3xl mx-auto p-10 text-center fade-in-up">-->
    <div class="relative z-10 max-w-3xl mx-auto p-10 text-center fade-in-up
             flex flex-col items-center justify-center min-h-screen">

        <!-- ► change start: small home SVG logo + brand text ◄ -->
<div class="flex items-center justify-center gap-3 mb-6">
    <svg xmlns="http://www.w3.org/2000/svg"
         class="w-16 h-16 text-blue-400 drop-shadow-lg"
         fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 3.172 2.929 11.1a1 1 0 0 0 .672 1.758H5v6.5A1.642 1.642 0 0 0 6.643 21h3.857a.5.5 0 0 0 .5-.5V16a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4.5a.5.5 0 0 0 .5.5h3.857A1.642 1.642 0 0 0 19 19.357V12.86h1.4a1 1 0 0 0 .672-1.758L12 3.172Z"/>
    </svg>

    <span class="text-3xl sm:text-4xl font-extrabold tracking-wide drop-shadow-lg">
        Renty
    </span>
</div>
<!-- ◄ change end -->

        <h1 class="text-5xl font-extrabold mb-6 drop-shadow-lg">
            Welcome to <span class="text-blue-400">Renty</span>
        </h1>
        <p class="text-gray-200 text-lg mb-10">
            Manage rentals, raise issues, and handle bills – all in one place.
        </p>
        <a href="{{ route('generic.login') }}"
                class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg transform hover:scale-105 transition">
                Login
            </a>


        <!--<div class="grid grid-cols-1 sm:grid-cols-2 gap-6 justify-center">-->
            <!--<a href="{{ route('filament.tenant.auth.login') }}"
               class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg transform hover:scale-105 transition">
                Tenant Login
            </a>

            <a href="{{ route('filament.admin.auth.login') }}"
               class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-xl shadow-lg transform hover:scale-105 transition">
                Admin Login
            </a>-->


        </div>


    </div>

    {{-- centered footer --}}
    <div class="absolute bottom-6 inset-x-0 text-center">
        <p class="text-sm text-gray-300 drop-shadow-md">
            Powered by <span class="text-white font-medium">Vumaa</span> Digital
        </p>
    </div>


</body>
</html>
