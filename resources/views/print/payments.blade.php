<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Payments - {{ now()->format('d M Y') }}</title>
<style>
    body { font-family: -apple-system, "Segoe UI", Arial, sans-serif; color: #1e293b; margin: 24px; }
    h1 { font-size: 18px; margin-bottom: 2px; }
    p.meta { color: #64748b; font-size: 12px; margin-top: 0; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
    th { background: #f8fafc; }
    .amt { text-align: right; }
    .print-btn { margin-bottom: 16px; padding: 8px 14px; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>
    <button class="no-print print-btn" onclick="window.print()">Print</button>
    <h1>Payments</h1>
    <p class="meta">Generated {{ now()->format('d M Y, H:i') }} &middot; {{ $payments->count() }} payment(s)</p>
    <table>
        <thead>
            <tr>
                <th>Tenant</th>
                <th>Invoice #</th>
                <th>Date</th>
                <th>Method</th>
                <th>Reference</th>
                <th class="amt">Amount paid</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td>{{ $payment->tenant?->tenant_name ?? 'Unknown' }}</td>
                    <td>{{ $payment->invoice?->invoice_number }}</td>
                    <td>{{ \Carbon\Carbon::parse($payment->payment_date ?? $payment->created_at)->format('d M Y') }}</td>
                    <td>{{ ucfirst($payment->payment_method ?? '-') }}</td>
                    <td>{{ $payment->payment_reference }}</td>
                    <td class="amt">KES {{ number_format($payment->amount_paid) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No payments found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
