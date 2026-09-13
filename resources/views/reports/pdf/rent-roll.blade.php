<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('reports.pdf._styles')
</head>
<body>
    @include('reports.pdf._header', ['title' => 'Rent Roll'])

    <table class="kpis">
        <tr>
            <td><div class="label">Tenants</div><div class="value">{{ $data['totals']['tenant_count'] }}</div></td>
            <td><div class="label">Total rent</div><div class="value">KES {{ number_format($data['totals']['total_rent']) }}</div></td>
            <td><div class="label">Balances owed</div><div class="value">KES {{ number_format($data['totals']['total_balance']) }}</div></td>
            <td></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Tenant</th>
                <th>Unit</th>
                <th>Phone</th>
                <th class="text-right">Rent</th>
                <th class="text-right">Balance</th>
                <th>Admitted</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['properties'] as $property)
                <tr class="group-header"><td colspan="6">{{ $property['location_name'] }}</td></tr>
                @foreach ($property['tenants'] as $tenant)
                    <tr>
                        <td>{{ $tenant['tenant_name'] }}</td>
                        <td>{{ $tenant['unit'] }}</td>
                        <td>{{ $tenant['phone_number'] }}</td>
                        <td class="text-right">{{ number_format($tenant['rent_amount']) }}</td>
                        <td class="text-right">{{ number_format($tenant['balance']) }}</td>
                        <td>{{ $tenant['date_admitted'] ? \Carbon\Carbon::parse($tenant['date_admitted'])->format('d M Y') : '' }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal">
                    <td colspan="3">Subtotal</td>
                    <td class="text-right">{{ number_format($property['subtotal_rent']) }}</td>
                    <td class="text-right">{{ number_format($property['subtotal_balance']) }}</td>
                    <td></td>
                </tr>
            @empty
                <tr><td colspan="6">No admitted tenants yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Renty &middot; Confidential report generated for {{ $landlord }}</div>
</body>
</html>
