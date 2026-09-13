<?php

namespace App\Services\Reports;

use Carbon\Carbon;

/**
 * Shared month-bucketing helper for every report that trends data across a
 * from/to range (Financial Summary, BnB Performance, Income Statement) - keeps
 * the "walk month-by-month between two dates" logic in one place instead of
 * copy-pasted per report the way the old Filament/AdminApp Reports pages did.
 */
class ReportPeriod
{
    /** @return array{0: Carbon, 1: Carbon} */
    public static function bounds(?string $from, ?string $to): array
    {
        $to = $to ? Carbon::parse($to) : Carbon::now();
        $from = $from ? Carbon::parse($from) : (clone $to)->subMonths(5)->startOfMonth();

        return [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
    }

    /** One entry per calendar month between $start and $end, inclusive. */
    public static function months(Carbon $start, Carbon $end): array
    {
        $months = [];
        $cursor = $start->copy()->startOfMonth();
        $last = $end->copy()->startOfMonth();

        while ($cursor->lte($last)) {
            $months[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        return $months;
    }

    public static function monthLabel(string $ym): string
    {
        return Carbon::parse($ym . '-01')->format('M Y');
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public static function monthRange(string $ym): array
    {
        $start = Carbon::parse($ym . '-01')->startOfMonth();

        return [$start, $start->copy()->endOfMonth()];
    }
}
