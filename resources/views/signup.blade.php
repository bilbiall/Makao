<x-layouts.marketing :title="'Start your free trial'">
    <div class="max-w-2xl mx-auto px-6 py-16">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Start your free trial</h1>
            <p class="mt-2 text-slate-500 dark:text-slate-400">No card required. Set up your account and add your first property in minutes.</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm p-4 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('signup.store') }}" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 space-y-6 dark:bg-slate-900 dark:border-slate-800">
            @csrf
            @php $inputClass = 'w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100'; @endphp
            @php $labelClass = 'block text-sm font-medium text-slate-700 mb-1 dark:text-slate-300'; @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="{{ $labelClass }}">Business / Property Name</label>
                    <input type="text" name="business_name" value="{{ old('business_name') }}" required
                        class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Your Name</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name') }}" required
                        class="{{ $inputClass }}">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="{{ $labelClass }}">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Phone Number</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}" placeholder="2547XXXXXXXX"
                        class="{{ $inputClass }}">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="{{ $labelClass }}">Password</label>
                    <input type="password" name="password" required
                        class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                        class="{{ $inputClass }}">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Choose a Plan</label>
                @if ($packages->isEmpty())
                    <p class="text-sm text-slate-500 rounded-lg border border-slate-200 p-4 dark:text-slate-400 dark:border-slate-800">Pricing is being finalized - contact us to get started.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-{{ min($packages->count(), 3) }} gap-3">
                        @foreach ($packages as $index => $package)
                            <label class="flex flex-col rounded-lg border-2 p-4 cursor-pointer transition has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50 border-slate-200 dark:border-slate-700 dark:has-[:checked]:bg-emerald-500/10">
                                <span class="flex items-center justify-between">
                                    <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $package->name }}</span>
                                    <input type="radio" name="package_id" value="{{ $package->id }}"
                                        @checked(old('package_id', request('package', $packages->first()->id)) == $package->id) required
                                        class="text-emerald-600 focus:ring-emerald-500">
                                </span>
                                <span class="mt-1 text-sm text-slate-500 dark:text-slate-400">KES {{ number_format($package->price) }} / {{ $package->billing_interval }}</span>
                                <span class="mt-1 text-xs text-emerald-700 dark:text-emerald-400">{{ $package->trial_days }}-day free trial</span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex items-start gap-2">
                <input type="checkbox" name="terms" id="terms" required class="mt-1 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <label for="terms" class="text-sm text-slate-600 dark:text-slate-400">
                    I agree to the <a href="{{ route('terms') }}" class="underline">Terms of Service</a> and <a href="{{ route('privacy') }}" class="underline">Privacy Policy</a>.
                </label>
            </div>

            <button type="submit" @if($packages->isEmpty()) disabled @endif
                class="w-full rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                Create My Account
            </button>

            <p class="text-center text-sm text-slate-500 dark:text-slate-400">
                Already have an account? <a href="{{ route('generic.login') }}" class="underline text-emerald-700 dark:text-emerald-400">Log in</a>
            </p>
        </form>
    </div>
</x-layouts.marketing>
