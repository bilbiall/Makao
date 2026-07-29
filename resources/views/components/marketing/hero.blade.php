<section class="relative isolate overflow-hidden bg-slate-900">
    {{-- Royalty-free Nairobi skyline photo (Unsplash License - free for commercial
         use, no attribution required). A dark scrim sits over it so the light-
         colored headline/copy stay readable at any point on the image. --}}
    <img src="{{ asset('images/nairobi-skyline-hero.jpg') }}" alt="" aria-hidden="true"
         class="absolute inset-0 -z-20 h-full w-full object-cover">
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-slate-900/80 via-slate-900/45 to-slate-900/10"></div>

    <div class="max-w-6xl mx-auto px-6 pt-20 pb-24 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <div>
            <span class="inline-block rounded-full bg-emerald-400/20 text-emerald-300 text-xs font-semibold px-3 py-1 ring-1 ring-emerald-300/30">
                Built for Kenyan landlords &amp; property managers
            </span>
            <h1 class="mt-6 text-4xl sm:text-5xl font-bold tracking-tight text-white">
                Run every building you own from one dashboard.
            </h1>
            <p class="mt-6 text-lg text-slate-200">
                Renty collects rent via M-Pesa and Pesapal, keeps every tenant in the loop by SMS and email,
                and gives you one place to track invoices, bills, maintenance, and move-outs - across as many
                properties as you manage.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row gap-4">
                <a href="{{ route('signup') }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                    Start Free Trial
                </a>
                <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300/40 px-6 py-3 text-sm font-semibold text-white hover:border-slate-300/70 transition">
                    See Pricing
                </a>
            </div>
            <p class="mt-4 text-xs text-slate-300">No card required to start.</p>
        </div>

        <div class="relative">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden">
                <div class="flex items-center gap-1.5 px-4 py-3 border-b border-slate-100 bg-slate-50">
                    <span class="h-2.5 w-2.5 rounded-full bg-rose-300"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-300"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-lg bg-emerald-50 p-3">
                            <p class="text-xs text-emerald-700">Occupancy</p>
                            <p class="text-xl font-bold text-emerald-800">92%</p>
                        </div>
                        <div class="rounded-lg bg-amber-50 p-3">
                            <p class="text-xs text-amber-700">Due This Month</p>
                            <p class="text-xl font-bold text-amber-800">KES 1.2M</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3">
                            <p class="text-xs text-slate-600">Properties</p>
                            <p class="text-xl font-bold text-slate-800">8</p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-100 divide-y divide-slate-100">
                        @foreach ([['Kilimani Heights - 2B', 'Paid', 'success'], ['Lavington Court - 1A', 'Partial', 'warning'], ['Westlands Vista - 3C', 'Unpaid', 'danger']] as [$label, $status, $color])
                            <div class="flex items-center justify-between px-4 py-3 text-sm">
                                <span class="text-slate-700">{{ $label }}</span>
                                <span @class([
                                    'rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    'bg-emerald-100 text-emerald-700' => $color === 'success',
                                    'bg-amber-100 text-amber-700' => $color === 'warning',
                                    'bg-rose-100 text-rose-700' => $color === 'danger',
                                ])>{{ $status }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
