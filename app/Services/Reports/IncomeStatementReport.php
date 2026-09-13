<?php

namespace App\Services\Reports;

use App\Models\BookingPayment;
use App\Models\Expense;
use App\Models\Payment;
use App\Support\StaffScope;

/**
 * Simple monthly P&L: rent payments received + completed BnB booking payments,
 * against landlord-wide operating costs (Expense). Expense has no location_id
 * (it's tracked landlord-wide, not per-property - see Expense's docblock), so
 * unlike the other reports this one has no location filter. Didn't exist
 * anywhere in the app before this build.
 */
class IncomeStatementReport
{
    public static function build(?string $from, ?string $to): array
    {
        [$start, $end] = ReportPeriod::bounds($from, $to);
        $months = ReportPeriod::months($start, $end);

        $rows = [];

        foreach ($months as $m) {
            [$periodStart, $periodEnd] = ReportPeriod::monthRange($m);

            $rentQuery = Payment::whereBetween('payment_date', [$periodStart, $periodEnd]);
            StaffScope::onTenantChild($rentQuery);
            $rentRevenue = (float) $rentQuery->sum('amount_paid');

            $bnbRevenue = (float) BookingPayment::where('status', 'completed')
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->sum('amount');

            $expense = Expense::whereBetween('expense_month', [$periodStart, $periodEnd])->first();

            $electricity = (float) ($expense->electricity ?? 0);
            $water = (float) ($expense->water ?? 0);
            $internet = (float) ($expense->internet ?? 0);
            $maintenance = (float) ($expense->maintenance ?? 0);
            $other = (float) ($expense->other ?? 0);
            $totalExpense = $electricity + $water + $internet + $maintenance + $other;
            $totalRevenue = $rentRevenue + $bnbRevenue;

            $rows[] = [
                'month' => ReportPeriod::monthLabel($m),
                'rent_revenue' => $rentRevenue,
                'bnb_revenue' => $bnbRevenue,
                'total_revenue' => $totalRevenue,
                'electricity' => $electricity,
                'water' => $water,
                'internet' => $internet,
                'maintenance' => $maintenance,
                'other' => $other,
                'total_expense' => $totalExpense,
                'net' => $totalRevenue - $totalExpense,
            ];
        }

        $totals = collect($rows)->reduce(function ($carry, $row) {
            foreach ($carry as $key => $value) {
                $carry[$key] = $value + $row[$key];
            }

            return $carry;
        }, [
            'rent_revenue' => 0, 'bnb_revenue' => 0, 'total_revenue' => 0,
            'electricity' => 0, 'water' => 0, 'internet' => 0, 'maintenance' => 0, 'other' => 0,
            'total_expense' => 0, 'net' => 0,
        ]);

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
}
