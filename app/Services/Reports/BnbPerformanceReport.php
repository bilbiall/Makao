<?php

namespace App\Services\Reports;

use App\Models\Booking;
use App\Models\House;
use App\Models\HouseInquiry;
use App\Support\StaffScope;

/**
 * BnB performance over a date range: revenue, nights, ADR and occupancy, the
 * views->inquiry->booking funnel (same counts as AdminApp\Analytics, but with
 * a date range and real charts instead of a permanent all-time snapshot), and
 * a per-property breakdown. Scoped the same way as Analytics/Bookings: agent
 * sees only their assigned houses, manager/caretaker their assigned
 * properties, admin/landlord everything.
 */
class BnbPerformanceReport
{
    public static function build(?string $from, ?string $to, ?int $locationId = null): array
    {
        [$start, $end] = ReportPeriod::bounds($from, $to);

        $houseQuery = House::where('listing_mode', 'short_term')->with('location');
        StaffScope::onHouse($houseQuery);

        if (StaffScope::isAgent()) {
            $houseQuery->whereIn('id', StaffScope::houseIds());
        }

        if ($locationId) {
            $houseQuery->where('location_id', $locationId);
        }

        $houses = $houseQuery->get();
        $houseIds = $houses->pluck('id');

        $bookings = Booking::whereIn('house_id', $houseIds)
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->whereBetween('check_in', [$start, $end])
            ->get();

        $inquiryCounts = HouseInquiry::whereIn('house_id', $houseIds)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('house_id, count(*) as count')
            ->groupBy('house_id')
            ->pluck('count', 'house_id');

        $bookingsByHouse = $bookings->groupBy('house_id');

        $revenue = (float) $bookings->sum('total_amount');
        $nights = (int) $bookings->sum('nights');
        $bookingCount = $bookings->count();
        $periodNights = max(1, $start->diffInDays($end) + 1);
        $roomNights = max(1, $periodNights * max(1, $houses->count()));

        $months = ReportPeriod::months($start, $end);
        $labels = [];
        $revenueByMonth = [];
        foreach ($months as $m) {
            [$periodStart, $periodEnd] = ReportPeriod::monthRange($m);
            $labels[] = ReportPeriod::monthLabel($m);
            $revenueByMonth[] = (float) $bookings->filter(
                fn (Booking $b) => $b->check_in->between($periodStart, $periodEnd)
            )->sum('total_amount');
        }

        $properties = $houses->map(function (House $house) use ($bookingsByHouse, $inquiryCounts) {
            $houseBookings = $bookingsByHouse->get($house->id, collect());

            return [
                'house_name' => $house->display_name ?: $house->house_name,
                'location_name' => $house->location?->location_name ?? 'Unassigned',
                'views' => $house->views_count,
                'inquiries' => $inquiryCounts[$house->id] ?? 0,
                'bookings' => $houseBookings->count(),
                'revenue' => (float) $houseBookings->sum('total_amount'),
            ];
        })->sortByDesc('revenue')->values();

        return [
            'from' => $start,
            'to' => $end,
            'summary' => [
                'views' => $houses->sum('views_count'),
                'inquiries' => $inquiryCounts->sum(),
                'bookings' => $bookingCount,
                'revenue' => $revenue,
                'nights' => $nights,
                'adr' => $nights > 0 ? round($revenue / $nights, 2) : 0,
                'occupancy_rate' => round(($nights / $roomNights) * 100, 1),
            ],
            'labels' => $labels,
            'revenue_by_month' => $revenueByMonth,
            'properties' => $properties,
        ];
    }
}
