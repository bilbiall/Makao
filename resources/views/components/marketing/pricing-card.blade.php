@props(['package', 'featured' => false])

<div @class([
    'rounded-2xl border p-8 flex flex-col',
    'border-emerald-600 bg-emerald-50 shadow-lg scale-105' => $featured,
    'border-slate-200 bg-white' => !$featured,
])>
    @if ($featured)
        <span class="self-start rounded-full bg-emerald-600 text-white text-xs font-semibold px-3 py-1 mb-4">Most Popular</span>
    @endif

    <h3 class="text-lg font-semibold text-slate-900">{{ $package->name }}</h3>

    <p class="mt-4">
        <span class="text-3xl font-bold text-slate-900">KES {{ number_format($package->price) }}</span>
        <span class="text-slate-500 text-sm">/ {{ $package->billing_interval }}</span>
    </p>

    @if ($package->trial_days > 0)
        <span class="mt-2 inline-block w-fit rounded-full bg-amber-100 text-amber-700 text-xs font-medium px-2.5 py-1">
            {{ $package->trial_days }}-day free trial
        </span>
    @endif

    <ul class="mt-6 space-y-3 text-sm text-slate-600 flex-1">
        <li class="flex items-center gap-2">
            @svg('heroicon-o-check-circle', 'w-4 h-4 text-emerald-600 flex-shrink-0')
            {{ $package->max_locations ? 'Up to ' . $package->max_locations . ' properties' : 'Unlimited properties' }}
        </li>
        <li class="flex items-center gap-2">
            @svg('heroicon-o-check-circle', 'w-4 h-4 text-emerald-600 flex-shrink-0')
            {{ $package->max_houses ? 'Up to ' . $package->max_houses . ' units' : 'Unlimited units' }}
        </li>
        <li class="flex items-center gap-2">
            @svg('heroicon-o-check-circle', 'w-4 h-4 text-emerald-600 flex-shrink-0')
            {{ $package->max_tenants ? 'Up to ' . $package->max_tenants . ' tenants' : 'Unlimited tenants' }}
        </li>
        @if (data_get($package->features, 'sms_notifications'))
            <li class="flex items-center gap-2">@svg('heroicon-o-check-circle', 'w-4 h-4 text-emerald-600 flex-shrink-0') SMS notifications</li>
        @endif
        @if (data_get($package->features, 'mpesa_payments'))
            <li class="flex items-center gap-2">@svg('heroicon-o-check-circle', 'w-4 h-4 text-emerald-600 flex-shrink-0') M-Pesa payments</li>
        @endif
        @if (data_get($package->features, 'reports'))
            <li class="flex items-center gap-2">@svg('heroicon-o-check-circle', 'w-4 h-4 text-emerald-600 flex-shrink-0') Reports &amp; analytics</li>
        @endif
    </ul>

    <a href="{{ route('signup') }}?package={{ $package->id }}"
        @class([
            'mt-8 inline-flex items-center justify-center rounded-lg px-6 py-3 text-sm font-semibold transition',
            'bg-emerald-600 text-white hover:bg-emerald-700' => $featured,
            'border border-slate-300 text-slate-700 hover:border-slate-400' => !$featured,
        ])>
        Get Started
    </a>
</div>
