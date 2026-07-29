<x-layouts.marketing>
    <x-marketing.hero />
    <x-marketing.feature-grid />
    <x-marketing.how-it-works />

    {{-- Pricing teaser --}}
    <section class="max-w-6xl mx-auto px-6 py-24">
        <div class="text-center max-w-2xl mx-auto">
            <h2 class="text-3xl font-bold text-slate-900">Simple, transparent pricing</h2>
            <p class="mt-3 text-slate-500">Pick the plan that fits your portfolio. Upgrade anytime.</p>
        </div>

        @if ($packages->isEmpty())
            <p class="mt-10 text-center text-slate-500">Pricing is being finalized - contact us to get started.</p>
        @else
            <div class="mt-14 grid grid-cols-1 sm:grid-cols-{{ min($packages->count(), 3) }} gap-6">
                @foreach ($packages as $index => $package)
                    <x-marketing.pricing-card :package="$package" :featured="$packages->count() >= 3 && $index === 1" />
                @endforeach
            </div>
        @endif

        <p class="mt-8 text-center">
            <a href="{{ route('pricing') }}" class="text-emerald-700 font-semibold hover:underline">See full plan comparison &rarr;</a>
        </p>
    </section>

    {{-- Testimonials --}}
    <section class="bg-white border-y border-slate-200">
        <div class="max-w-6xl mx-auto px-6 py-24">
            <div class="text-center max-w-2xl mx-auto">
                <h2 class="text-3xl font-bold text-slate-900">Trusted by property managers across Nairobi</h2>
            </div>

            <div class="mt-14 grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-marketing.testimonial-card
                    quote="I used to chase rent on WhatsApp across three buildings. Now everything is in one place and tenants pay straight from their phones."
                    name="Grace W."
                    role="Property Manager"
                    building="Kilimani Heights" />
                <x-marketing.testimonial-card
                    quote="The tenant portal alone cut my support calls in half. People can see their own invoices and pay without calling me."
                    name="Brian O."
                    role="Landlord"
                    building="Lavington Court" />
                <x-marketing.testimonial-card
                    quote="Maintenance requests used to get lost. Now every issue is tracked from report to resolution."
                    name="Faith N."
                    role="Caretaker"
                    building="Westlands Vista Apartments" />
            </div>
        </div>
    </section>

    <x-marketing.cta-band />
</x-layouts.marketing>
