<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Invoices - {{ now()->format('d M Y') }}</title>
<style>
    body { font-family: -apple-system, "Segoe UI", Arial, sans-serif; color: #1e293b; margin: 24px; }
    h1 { font-size: 18px; margin-bottom: 2px; }
    p.meta { color: #64748b; font-size: 12px; margin-top: 0; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
    th { background: #f8fafc; }
    .amt { text-align: right; }
    .status-paid { color: #059669; }
    .status-partial { color: #d97706; }
    .status-unpaid { color: #e11d48; }
    .print-btn { margin-bottom: 16px; padding: 8px 14px; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>
    <button class="no-print print-btn" onclick="window.print()">Print</button>
    <h1>Invoices</h1>
    <p class="meta">Generated {{ now()->format('d M Y, H:i') }} &middot; {{ $invoices->count() }} invoice(s)</p>
    <table>
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Tenant</th>
                <th>Date</th>
                <th class="amt">Amount</th>
                <th class="amt">Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->tenant?->tenant_name ?? 'Unknown' }}</td>
                    <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</td>
                    <td class="amt">KES {{ number_format($invoice->amount) }}</td>
                    <td class="amt">KES {{ number_format($invoice->balance) }}</td>
                    <td class="status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No invoices found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
