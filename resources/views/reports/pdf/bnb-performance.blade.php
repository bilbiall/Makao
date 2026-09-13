<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('reports.pdf._styles')
</head>
<body>
    @include('reports.pdf._header', ['title' => 'BnB Performance', 'range' => $data['from']->format('d M Y') . ' - ' . $data['to']->format('d M Y')])

    <table class="kpis">
        <tr>
            <td><div class="label">Revenue</div><div class="value">KES {{ number_format($data['summary']['revenue']) }}</div></td>
            <td><div class="label">Bookings</div><div class="value">{{ $data['summary']['bookings'] }}</div></td>
            <td><div class="label">Occupancy</div><div class="value">{{ $data['summary']['occupancy_rate'] }}%</div></td>
            <td><div class="label">ADR</div><div class="value">KES {{ number_format($data['summary']['adr']) }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Views</div><div class="value">{{ $data['summary']['views'] }}</div></td>
            <td><div class="label">Inquiries</div><div class="value">{{ $data['summary']['inquiries'] }}</div></td>
            <td><div class="label">Nights booked</div><div class="value">{{ $data['summary']['nights'] }}</div></td>
            <td></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Property</th>
                <th class="text-right">Views</th>
                <th class="text-right">Inquiries</th>
                <th class="text-right">Bookings</th>
                <th class="text-right">Revenue</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['properties'] as $property)
                <tr>
                    <td>{{ $property['house_name'] }} <span class="muted">({{ $property['location_name'] }})</span></td>
                    <td class="text-right">{{ $property['views'] }}</td>
                    <td class="text-right">{{ $property['inquiries'] }}</td>
                    <td class="text-right">{{ $property['bookings'] }}</td>
                    <td class="text-right">{{ number_format($property['revenue']) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No short-stay properties yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Renty &middot; Confidential report generated for {{ $landlord }}</div>
</body>
</html>
