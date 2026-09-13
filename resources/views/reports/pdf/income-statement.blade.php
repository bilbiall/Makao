<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('reports.pdf._styles')
</head>
<body>
    @include('reports.pdf._header', ['title' => 'Income Statement'])

    <table class="kpis">
        <tr>
            <td><div class="label">Total revenue</div><div class="value">KES {{ number_format($data['totals']['total_revenue']) }}</div></td>
            <td><div class="label">Total expenses</div><div class="value">KES {{ number_format($data['totals']['total_expense']) }}</div></td>
            <td><div class="label">Net income</div><div class="value">KES {{ number_format($data['totals']['net']) }}</div></td>
            <td></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Month</th>
                <th class="text-right">Rent</th>
                <th class="text-right">BnB</th>
                <th class="text-right">Revenue</th>
                <th class="text-right">Electricity</th>
                <th class="text-right">Water</th>
                <th class="text-right">Internet</th>
                <th class="text-right">Maintenance</th>
                <th class="text-right">Other</th>
                <th class="text-right">Expenses</th>
                <th class="text-right">Net</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['rows'] as $row)
                <tr>
                    <td>{{ $row['month'] }}</td>
                    <td class="text-right">{{ number_format($row['rent_revenue']) }}</td>
                    <td class="text-right">{{ number_format($row['bnb_revenue']) }}</td>
                    <td class="text-right">{{ number_format($row['total_revenue']) }}</td>
                    <td class="text-right">{{ number_format($row['electricity']) }}</td>
                    <td class="text-right">{{ number_format($row['water']) }}</td>
                    <td class="text-right">{{ number_format($row['internet']) }}</td>
                    <td class="text-right">{{ number_format($row['maintenance']) }}</td>
                    <td class="text-right">{{ number_format($row['other']) }}</td>
                    <td class="text-right">{{ number_format($row['total_expense']) }}</td>
                    <td class="text-right">{{ number_format($row['net']) }}</td>
                </tr>
            @empty
                <tr><td colspan="11">No data in this period.</td></tr>
            @endforelse
            @if (count($data['rows']))
                <tr class="subtotal">
                    <td>Total</td>
                    <td class="text-right">{{ number_format($data['totals']['rent_revenue']) }}</td>
                    <td class="text-right">{{ number_format($data['totals']['bnb_revenue']) }}</td>
                    <td class="text-right">{{ number_format($data['totals']['total_revenue']) }}</td>
                    <td class="text-right">{{ number_format($data['totals']['electricity']) }}</td>
                    <td class="text-right">{{ number_format($data['totals']['water']) }}</td>
                    <td class="text-right">{{ number_format($data['totals']['internet']) }}</td>
                    <td class="text-right">{{ number_format($data['totals']['maintenance']) }}</td>
                    <td class="text-right">{{ number_format($data['totals']['other']) }}</td>
                    <td class="text-right">{{ number_format($data['totals']['total_expense']) }}</td>
                    <td class="text-right">{{ number_format($data['totals']['net']) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">Renty &middot; Confidential report generated for {{ $landlord }}</div>
</body>
</html>
