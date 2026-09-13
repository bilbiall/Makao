<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('reports.pdf._styles')
</head>
<body>
    @include('reports.pdf._header', ['title' => 'Arrears / Aging Report'])

    <table class="kpis">
        @foreach ($data['buckets']->chunk(4) as $chunk)
            <tr>
                @foreach ($chunk as $bucket)
                    <td><div class="label">{{ $bucket['label'] }}</div><div class="value">KES {{ number_format($bucket['total']) }}</div></td>
                @endforeach
            </tr>
        @endforeach
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Tenant</th>
                <th>Property</th>
                <th>Unit</th>
                <th>Invoice #</th>
                <th>Due date</th>
                <th class="text-right">Days overdue</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['rows'] as $row)
                <tr>
                    <td>{{ $row['tenant_name'] }}</td>
                    <td>{{ $row['location_name'] }}</td>
                    <td>{{ $row['unit'] }}</td>
                    <td>{{ $row['invoice_number'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($row['due_date'])->format('d M Y') }}</td>
                    <td class="text-right">{{ $row['days_overdue'] }}</td>
                    <td class="text-right">{{ number_format($row['balance']) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No overdue invoices. Everyone is current.</td></tr>
            @endforelse
            @if ($data['rows']->count())
                <tr class="subtotal">
                    <td colspan="6">Grand total</td>
                    <td class="text-right">{{ number_format($data['grand_total']) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">Renty &middot; Confidential report generated for {{ $landlord }}</div>
</body>
</html>
