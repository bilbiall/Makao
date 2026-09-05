<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Tenants - {{ now()->format('d M Y') }}</title>
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
    <h1>Tenants</h1>
    <p class="meta">Generated {{ now()->format('d M Y, H:i') }} &middot; {{ $tenants->count() }} tenant(s)</p>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>House</th>
                <th>Admitted</th>
                <th class="amt">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tenants as $tenant)
                <tr>
                    <td>{{ $tenant->tenant_name }}</td>
                    <td>{{ $tenant->phone_number }}</td>
                    <td>{{ $tenant->house?->house_name ?? 'No house' }}</td>
                    <td>{{ \Carbon\Carbon::parse($tenant->date_admitted)->format('d M Y') }}</td>
                    <td class="amt">KES {{ number_format($tenant->balance) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No tenants found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
