<x-layouts.marketing :title="'Create your account'">
    <div class="max-w-2xl mx-auto px-6 py-16">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-slate-900">Find your next home</h1>
            <p class="mt-2 text-slate-500">Create an account to save favourites and request viewings on verified houses.</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm p-4">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('user-signup.store') }}" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Phone Number</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}" placeholder="2547XXXXXXXX"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <button type="submit"
                class="w-full rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                Create My Account
            </button>

            <p class="text-center text-sm text-slate-500">
                Already have an account? <a href="{{ route('generic.login') }}" class="underline text-emerald-700">Log in</a>
            </p>
        </form>
    </div>
</x-layouts.marketing>
