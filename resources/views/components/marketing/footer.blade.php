<footer class="border-t border-slate-200 bg-white">
    <div class="max-w-6xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
        <div>
            <div class="text-xl font-bold text-slate-900">Renty</div>
            <p class="mt-2 text-sm text-slate-500">Rental management for Kenyan landlords and property managers.</p>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-900">Product</h3>
            <ul class="mt-3 space-y-2 text-sm text-slate-500">
                <li><a href="{{ url('/#features') }}" class="hover:text-slate-900">Features</a></li>
                <li><a href="{{ route('pricing') }}" class="hover:text-slate-900">Pricing</a></li>
                <li><a href="{{ route('signup') }}" class="hover:text-slate-900">Start Free Trial</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-900">Account</h3>
            <ul class="mt-3 space-y-2 text-sm text-slate-500">
                <li><a href="{{ route('generic.login') }}" class="hover:text-slate-900">Log in</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-900">Legal</h3>
            <ul class="mt-3 space-y-2 text-sm text-slate-500">
                <li><a href="{{ route('privacy') }}" class="hover:text-slate-900">Privacy Policy</a></li>
                <li><a href="{{ route('terms') }}" class="hover:text-slate-900">Terms of Service</a></li>
            </ul>
        </div>
    </div>

    <div class="border-t border-slate-200">
        <div class="max-w-6xl mx-auto px-6 py-6 flex flex-col md:flex-row items-center justify-between gap-2 text-xs text-slate-400">
            <p>&copy; {{ date('Y') }} Renty. All rights reserved.</p>
            <p>Powered by Vumaa Digital</p>
        </div>
    </div>
</footer>
