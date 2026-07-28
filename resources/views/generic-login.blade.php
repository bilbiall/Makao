<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login – Renty</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="relative min-h-screen bg-cover bg-center bg-no-repeat"
      style="background-image: url('{{ asset('images/background.jpg') }}');">

    {{-- dark overlay for readability --}}
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>

    {{-- login card --}}
    <div class="relative z-10 flex items-center justify-center min-h-screen px-4">
        <form action="{{ route('generic.login.attempt') }}" method="POST"
              class="w-full max-w-md space-y-6 bg-gray-900/70 backdrop-blur-md rounded-2xl p-8
                     text-gray-200 shadow-2xl ring-1 ring-white/10">
            @csrf

            <div class="text-center space-y-2">
                <div class="flex items-center justify-center gap-3">
                    {{-- mini logo --}}
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-10 h-10 text-blue-400 drop-shadow" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 3.172 2.929 11.1a1 1 0 0 0 .672 1.758H5v6.5A1.642 1.642 0 0 0 6.643 21h3.857a.5.5 0 0 0 .5-.5V16a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4.5a.5.5 0 0 0 .5.5h3.857A1.642 1.642 0 0 0 19 19.357V12.86h1.4a1 1 0 0 0 .672-1.758L12 3.172Z"/>
                    </svg>
                    <span class="text-2xl font-extrabold tracking-wide">Renty</span>
                </div>
                <h2 class="text-xl font-semibold">Sign in to your account</h2>
            </div>

            {{-- email --}}
            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input name="email" type="email" required autofocus
                       class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700
                              focus:ring-2 focus:ring-blue-500 focus:outline-none placeholder-gray-400" />
            </div>

            {{-- password --}}
            <!--<div>
                <label class="block text-sm font-medium mb-1">Password</label>
                <input name="password" type="password" required
                       class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700
                              focus:ring-2 focus:ring-blue-500 focus:outline-none placeholder-gray-400" />
            </div>-->
            <!--passwordwith  eye for lookup-->
            {{-- password --}}
<label class="block text-sm font-medium mb-1">Password</label>

<div x-data="{ show: false }" class="relative">

    {{-- input --}}
    <input :type="show ? 'text' : 'password'"
           name="password" required
           class="peer w-full px-3 py-2 pr-11 rounded-lg bg-gray-800 border border-gray-700
                  focus:ring-2 focus:ring-blue-500 focus:outline-none placeholder-gray-400" />

    {{-- toggle button --}}
    <button type="button"
            @click="show = !show"
            class="absolute inset-y-0 right-3 flex items-center
                   text-gray-400 hover:text-gray-200 focus:outline-none">

        {{-- eye (closed) --}}
        <svg x-show="!show" xmlns="http://www.w3.org/2000/svg"
             class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>

        {{-- eye-off (open) --}}
        <svg x-show="show" xmlns="http://www.w3.org/2000/svg"
             class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                  d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.967 9.967 0 012.148-3.482M9.88 9.88a3 3 0 104.24 4.24M6.1 6.1l11.8 11.8" />
        </svg>
    </button>
</div>


            <button type="submit"
                    class="w-full py-2 rounded-lg font-semibold bg-blue-600 hover:bg-blue-700
                           transition-colors shadow-md">
                Login
            </button>

            <div class="text-center text-sm">
                <a href="{{ route('password.request') }}" class="text-blue-400 hover:text-blue-200">Forgot your password?</a>
            </div>

            @if ($errors->any())
                <p class="text-red-400 text-sm text-center">Invalid credentials</p>
            @endif
        </form>

    </div>
    {{-- centered footer --}}
    <div class="absolute bottom-6 inset-x-0 text-center">
        <p class="text-sm text-gray-300 drop-shadow-md">
            Powered by <span class="text-white font-medium">Vumaa</span> Digital
        </p>
    </div>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>


</body>
</html>
