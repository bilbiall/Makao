<?php

namespace App\Filament\Pages;

use App\Filament\Imports\ExpenseImporter;
use App\Filament\Imports\HouseImporter;
use App\Filament\Imports\InvoiceImporter;
use App\Filament\Imports\LocationImporter;
use App\Filament\Imports\PaymentImporter;
use App\Filament\Imports\TenantImporter;
use Filament\Actions\ImportAction;
use Filament\Pages\Page;

/**
 * Lets a property manager migrate from a spreadsheet/old system by downloading
 * a CSV template per data type, filling it in, and uploading it back - one
 * importer per entity, run in the dependency order shown (a Unit must exist
 * before a Tenant can be admitted into it, a Tenant before an Invoice, etc).
 */
class ImportData extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationLabel = 'Import Data';
    protected static ?string $slug = 'import-data';
    protected static ?string $navigationGroup = 'Settings';
    protected static string $view = 'filament.pages.import-data';

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, ['admin', 'landlord']);
    }

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user || ! in_array($user->role, ['admin', 'landlord'])) {
            abort(403, 'Unauthorized');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make('importLocations')
                ->label('1. Import Properties')
                ->importer(LocationImporter::class)
                ->color('gray'),

            ImportAction::make('importHouses')
                ->label('2. Import Units')
                ->importer(HouseImporter::class)
                ->color('gray'),

            ImportAction::make('importTenants')
                ->label('3. Import Tenants')
                ->importer(TenantImporter::class)
                ->color('gray'),

            ImportAction::make('importInvoices')
                ->label('4. Import Invoices')
                ->importer(InvoiceImporter::class)
                ->color('gray'),

            ImportAction::make('importPayments')
                ->label('5. Import Payments')
                ->importer(PaymentImporter::class)
                ->color('gray'),

            ImportAction::make('importExpenses')
                ->label('Import Expenses')
                ->importer(ExpenseImporter::class)
                ->color('gray'),
        ];
    }
}
