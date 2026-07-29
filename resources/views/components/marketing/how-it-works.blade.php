@php
$steps = [
    ['n' => '1', 'title' => 'Sign up and pick a plan', 'body' => 'Create your account and start a free trial - no card required.'],
    ['n' => '2', 'title' => 'Add your properties and units', 'body' => 'Organize locations and houses the way you actually manage them.'],
    ['n' => '3', 'title' => 'Admit tenants', 'body' => 'They get portal access instantly - invoices, payments, and issues in one place.'],
    ['n' => '4', 'title' => 'Rent gets collected automatically', 'body' => 'Invoices, M-Pesa payments, and SMS receipts all flow without manual work.'],
];
@endphp

<section class="bg-white border-y border-slate-200">
    <div class="max-w-6xl mx-auto px-6 py-24">
        <div class="text-center max-w-2xl mx-auto">
            <h2 class="text-3xl font-bold text-slate-900">How it works</h2>
        </div>

        <div class="mt-14 grid grid-cols-1 md:grid-cols-4 gap-8">
            @foreach ($steps as $step)
                <div class="text-center md:text-left">
                    <div class="mx-auto md:mx-0 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-600 text-white font-semibold">
                        {{ $step['n'] }}
                    </div>
                    <h3 class="mt-4 font-semibold text-slate-900">{{ $step['title'] }}</h3>
                    <p class="mt-1.5 text-sm text-slate-500">{{ $step['body'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
