<?php

namespace App\Filament\Resources\AdminResource\Widgets;

use Filament\Widgets\ChartWidget;


use App\Models\Payment;

class PaymentsChart extends ChartWidget
{
    //protected static ?string $heading = 'Chart';
    protected static ?string $heading = 'Monthly Payments';
    protected static string $color = 'primary';

    protected function getData(): array
    {
        $payments = Payment::selectRaw('MONTHNAME(payment_date) as month, SUM(amount_paid) as total')
            ->groupBy('month')
            ->orderByRaw('MONTH(payment_date)')
            ->pluck('total', 'month');
        return [
            //
            'datasets' => [
                [
                    'label' => 'Total Payments',
                    'data' => $payments->values(),
                ],
            ],
            'labels' => $payments->keys(),

        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
