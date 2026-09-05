<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @include('partials.favicon')
    <title>Reset Password – Renty</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative min-h-screen bg-cover bg-center bg-no-repeat"
      style="background-image: url('{{ asset('images/background.jpg') }}');">
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>

    <div class="relative z-10 flex items-center justify-center min-h-screen px-4">
        <form action="{{ route('password.update') }}" method="POST"
              class="w-full max-w-md space-y-6 bg-gray-900/70 backdrop-blur-md rounded-2xl p-8
                     text-gray-200 shadow-2xl ring-1 ring-white/10">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="text-center space-y-2">
                <h2 class="text-xl font-semibold">Create a new password</h2>
                <p class="text-sm text-gray-300">Enter the email for your account and choose a new password.</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input name="email" type="email" required value="{{ old('email', $email) }}"
                       class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700
                              focus:ring-2 focus:ring-blue-500 focus:outline-none placeholder-gray-400" />
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">New password</label>
                <input name="password" type="password" required
                       class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700
                              focus:ring-2 focus:ring-blue-500 focus:outline-none placeholder-gray-400" />
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Confirm password</label>
                <input name="password_confirmation" type="password" required
                       class="w-full px-3 py-2 rounded-lg bg-gray-800 border border-gray-700
                              focus:ring-2 focus:ring-blue-500 focus:outline-none placeholder-gray-400" />
            </div>

            @if ($errors->any())
                <div class="space-y-1 text-sm text-red-400">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <button type="submit"
                    class="w-full py-2 rounded-lg font-semibold bg-blue-600 hover:bg-blue-700
                           transition-colors shadow-md">
                Reset password
            </button>

            <div class="text-center text-sm">
                <a href="{{ route('generic.login') }}" class="text-blue-400 hover:text-blue-200">Back to login</a>
            </div>
        </form>
    </div>

</body>
</html>
