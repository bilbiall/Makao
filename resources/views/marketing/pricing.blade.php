<x-layouts.marketing :title="'Pricing'">
    <section class="max-w-6xl mx-auto px-6 py-20">
        <div class="text-center max-w-2xl mx-auto">
            <h1 class="text-4xl font-bold text-slate-900">Plans that scale with your portfolio</h1>
            <p class="mt-3 text-slate-500">Start on a free trial, no card required. Upgrade or downgrade anytime.</p>
        </div>

        @if ($packages->isEmpty())
            <div class="mt-14 text-center rounded-2xl border border-slate-200 bg-white p-12">
                <p class="text-slate-500">Pricing is being finalized. Contact us to get started.</p>
            </div>
        @else
            <div class="mt-14 grid grid-cols-1 sm:grid-cols-{{ min($packages->count(), 3) }} gap-6">
                @foreach ($packages as $index => $package)
                    <x-marketing.pricing-card :package="$package" :featured="$packages->count() >= 3 && $index === (int) floor($packages->count() / 2)" />
                @endforeach
            </div>
        @endif
    </section>

    <section class="bg-white border-t border-slate-200">
        <div class="max-w-3xl mx-auto px-6 py-20">
            <h2 class="text-2xl font-bold text-slate-900 text-center">Frequently asked questions</h2>

            <div x-data="{ open: null }" class="mt-10 divide-y divide-slate-200 border-y border-slate-200">
                @foreach ([
                    ['q' => 'Can I switch plans later?', 'a' => 'Yes - contact us and we\'ll move you to a new plan; your data stays exactly as it is.'],
                    ['q' => 'Is there a setup fee?', 'a' => 'No setup fees. You only pay the plan price.'],
                    ['q' => 'How do I pay my subscription?', 'a' => 'Subscriptions are currently handled directly with our team after signup - our team will reach out to arrange payment (M-Pesa or bank transfer).'],
                    ['q' => 'What happens after my trial ends?', 'a' => 'We will reach out to arrange payment before your trial expires. Your data is never deleted.'],
                ] as $index => $faq)
                    <div class="py-4">
                        <button @click="open = open === {{ $index }} ? null : {{ $index }}" class="w-full flex items-center justify-between text-left">
                            <span class="font-medium text-slate-900">{{ $faq['q'] }}</span>
                            <span x-text="open === {{ $index }} ? '−' : '+'" class="text-slate-400 text-xl leading-none"></span>
                        </button>
                        <p x-show="open === {{ $index }}" x-transition class="mt-2 text-sm text-slate-500">{{ $faq['a'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <x-marketing.cta-band />
</x-layouts.marketing>
