<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('reports.pdf._styles')
</head>
<body>
    @include('reports.pdf._header', ['title' => 'Financial Summary', 'range' => $data['from']->format('d M Y') . ' - ' . $data['to']->format('d M Y')])

    <table class="kpis">
        <tr>
            <td><div class="label">Invoiced</div><div class="value">KES {{ number_format($data['summary']['total_invoiced']) }}</div></td>
            <td><div class="label">Paid</div><div class="value">KES {{ number_format($data['summary']['total_paid']) }}</div></td>
            <td><div class="label">Outstanding</div><div class="value">KES {{ number_format($data['summary']['outstanding']) }}</div></td>
            <td><div class="label">Collection rate</div><div class="value">{{ $data['summary']['collection_rate'] }}%</div></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Tenant</th>
                <th>Invoice #</th>
                <th>Date</th>
                <th class="text-right">Amount</th>
                <th class="text-right">Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['by_property'] as $property)
                <tr class="group-header"><td colspan="6">{{ $property['location_name'] }}</td></tr>
                @foreach ($property['invoices'] as $invoice)
                    <tr>
                        <td>{{ $invoice->tenant?->tenant_name ?? 'Unknown tenant' }}</td>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</td>
                        <td class="text-right">{{ number_format($invoice->amount) }}</td>
                        <td class="text-right">{{ number_format($invoice->balance) }}</td>
                        <td>{{ ucfirst($invoice->status) }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal">
                    <td colspan="3">Subtotal</td>
                    <td class="text-right">{{ number_format($property['subtotal_invoiced']) }}</td>
                    <td class="text-right">{{ number_format($property['subtotal_balance']) }}</td>
                    <td></td>
                </tr>
            @empty
                <tr><td colspan="6">No invoices in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Renty &middot; Confidential report generated for {{ $landlord }}</div>
</body>
</html>
