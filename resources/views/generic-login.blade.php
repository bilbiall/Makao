<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    @include('partials.theme-init-script')
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Log in - Renty</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-stone-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100" style="font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;">

    <div class="flex items-center justify-center min-h-screen px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-8 flex items-center justify-center gap-2">
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-emerald-600 text-sm font-bold text-white">M</span>
                <span class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Renty</span>
                <x-theme-toggle class="ml-2 h-9 w-9 text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800" />
            </div>

            <form action="{{ route('generic.login.attempt') }}" method="POST"
                  class="space-y-5 bg-white rounded-2xl border border-slate-200 shadow-sm p-8 dark:bg-slate-900 dark:border-slate-800">
                @csrf

                <div class="text-center">
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Sign in to your account</h1>
                </div>

                @if ($errors->any())
                    <div class="rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3 text-center dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
                        Invalid credentials
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">Email</label>
                    <input name="email" type="email" required autofocus value="{{ old('email') }}"
                           class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                </div>

                <div x-data="{ show: false }">
                    <label class="block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300">Password</label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" name="password" required
                               class="w-full rounded-lg border border-slate-300 px-4 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300">
                            <svg x-show="!show" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="show" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.967 9.967 0 012.148-3.482M9.88 9.88a3 3 0 104.24 4.24M6.1 6.1l11.8 11.8" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                    Log in
                </button>

                <div class="text-center text-sm">
                    <a href="{{ route('password.request') }}" class="text-emerald-700 font-medium hover:underline dark:text-emerald-400">Forgot your password?</a>
                </div>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
                New here? <a href="{{ route('get-started') }}" class="text-emerald-700 font-medium hover:underline dark:text-emerald-400">Get started</a>
            </p>
        </div>
    </div>

</body>
</html>
