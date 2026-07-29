@php
$features = [
    ['icon' => 'heroicon-o-building-office-2', 'title' => 'Multi-property management', 'body' => 'Organize every building, block, and unit under one account.'],
    ['icon' => 'heroicon-o-user-group', 'title' => 'Tenant portal', 'body' => 'Tenants log in to view invoices, pay rent, and raise issues themselves.'],
    ['icon' => 'heroicon-o-credit-card', 'title' => 'M-Pesa & card payments', 'body' => 'Tenants pay by M-Pesa STK push or card - payments reconcile automatically against invoices.'],
    ['icon' => 'heroicon-o-chat-bubble-left-right', 'title' => 'SMS & email notifications', 'body' => 'Automatic alerts for new invoices, payment confirmations, and bill updates.'],
    ['icon' => 'heroicon-o-wrench-screwdriver', 'title' => 'Maintenance tracking', 'body' => 'Tenants report issues; you track them to resolution from one queue.'],
    ['icon' => 'heroicon-o-document-text', 'title' => 'Notice-to-vacate workflow', 'body' => 'A structured move-out process with dates, reasons, and approvals.'],
    ['icon' => 'heroicon-o-clipboard-document-list', 'title' => 'Activity audit log', 'body' => 'Every action is logged and reviewable - who created a tenant, who recorded a payment.'],
    ['icon' => 'heroicon-o-chat-bubble-oval-left-ellipsis', 'title' => 'Tenant-landlord chat', 'body' => 'Built-in messaging tied to a house or an issue thread, so conversations do not get lost.'],
];
@endphp

<section id="features" class="max-w-6xl mx-auto px-6 py-24">
    <div class="text-center max-w-2xl mx-auto">
        <h2 class="text-3xl font-bold text-slate-900">Everything you need to run your rentals</h2>
        <p class="mt-3 text-slate-500">No spreadsheets, no lost WhatsApp messages, no chasing down receipts.</p>
    </div>

    <div class="mt-14 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach ($features as $feature)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md transition">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    @svg($feature['icon'], 'w-5 h-5')
                </div>
                <h3 class="mt-4 font-semibold text-slate-900">{{ $feature['title'] }}</h3>
                <p class="mt-1.5 text-sm text-slate-500">{{ $feature['body'] }}</p>
            </div>
        @endforeach
    </div>
</section>
