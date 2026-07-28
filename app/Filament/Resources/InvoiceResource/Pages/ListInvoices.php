<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use Filament\Pages\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\Bill;
//use helper for sending sms
use App\Helpers\SmsHelper;
use App\Helpers\SmsTemplateHelper;
use Carbon\Carbon;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            //mass invoice
            Action::make('Send Mass Invoices')
                ->color('primary')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->label('Send Mass Invoices')
                ->action(function () {
                    $tenants = Tenant::all();
                    $today = now();
                    $month = $today->format('m');
                    $year = $today->format('Y');
                    $count = 0;

                    foreach ($tenants as $tenant) {
                        // Skip if already invoiced this month
                        $alreadyInvoiced = Invoice::where('tenant_id', $tenant->id)
                            ->whereMonth('invoice_date', $month)
                            ->whereYear('invoice_date', $year)
                            ->exists();

                        if ($alreadyInvoiced) continue;

                        $house = $tenant->house;
                        if (!$house) continue;

                        $rent = $house->rent_amount ?? 0;

                        $bills = Bill::where('tenant_id', $tenant->id)
                            ->whereMonth('bill_month', $month)
                            ->whereYear('bill_month', $year)
                            ->first();

                        $billTotal = $bills
                            ? ($bills->water + $bills->electricity + $bills->trash + $bills->internet)
                            : 0;

                        $total = $rent + $billTotal;
                        $nextInvoiceNumber = 'INV-' . (Invoice::max('id') + 1);

                        $invoice = Invoice::create([
                            'tenant_id' => $tenant->id,
                            'invoice_number' => $nextInvoiceNumber,
                            'invoice_date' => $today,
                            'due_date' => $today->copy()->addDays(10),
                            'amount' => $total,
                            'comment' => 'Mass-generated invoice',
                            'status' => 'unpaid',
                        ]);

                        try {
                            SmsHelper::sendSms($tenant->phone_number, "Hello {$tenant->tenant_name}, your invoice ({$invoice->invoice_number}) of KES {$total} is due by {$invoice->due_date->format('M d')}.");
                        } catch (\Throwable $e) {
                            // ignore SMS failures (e.g. gateway not configured)
                        }

                        $count++;
                    }

                    Notification::make()
                        ->title("Mass invoice sent to {$count} tenants.")
                        ->success()
                        ->send();
                }),

            // mass reminder for invoices with balance
            Action::make('Send Mass Reminders')
                ->color('warning')
                ->icon('heroicon-o-bell-alert')
                ->requiresConfirmation()
                ->label('Mass Reminder')
                ->action(function () {
                    $invoices = Invoice::where('balance', '>', 0)->get();
                    $count = 0;

                    foreach ($invoices as $invoice) {
                        $tenant = $invoice->tenant;
                        if (!$tenant) {
                            continue;
                        }

                        $message = SmsTemplateHelper::render('template_mass_reminder', [
                            'tenant_name' => $tenant->tenant_name,
                            'invoice_number' => $invoice->invoice_number,
                            'amount' => number_format($invoice->balance),
                            'due_date' => $invoice->due_date ? Carbon::parse($invoice->due_date)->format('d M Y') : 'N/A',
                            'app_name' => config('app.name'),
                        ]);

                        try {
                            SmsHelper::sendSms($tenant->phone_number, $message);
                            $count++;
                        } catch (\Throwable $e) {
                            // silently skip failed sends
                        }
                    }

                    Notification::make()
                        ->title("Mass reminders sent to {$count} tenants.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
