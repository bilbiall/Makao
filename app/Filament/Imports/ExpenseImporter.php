<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\ImportsLandlordData;
use App\Models\Expense;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Illuminate\Support\Carbon;

class ExpenseImporter extends Importer
{
    use ImportsLandlordData;

    protected static ?string $model = Expense::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('expense_month')
                ->label('Month (any date within the month, e.g. 2025-06-01)')
                ->requiredMapping()
                ->rules(['required', 'date'])
                ->castStateUsing(fn ($state) => filled($state) ? Carbon::parse($state)->startOfMonth()->toDateString() : null),

            ImportColumn::make('electricity')->numeric()->ignoreBlankState()->rules(['nullable', 'numeric']),
            ImportColumn::make('water')->numeric()->ignoreBlankState()->rules(['nullable', 'numeric']),
            ImportColumn::make('internet')->numeric()->ignoreBlankState()->rules(['nullable', 'numeric']),
            ImportColumn::make('maintenance')->numeric()->ignoreBlankState()->rules(['nullable', 'numeric']),
            ImportColumn::make('other')->label('Other expenses')->numeric()->ignoreBlankState()->rules(['nullable', 'numeric']),

            ImportColumn::make('notes')->ignoreBlankState()->rules(['nullable', 'max:255']),
        ];
    }

    public function resolveRecord(): Expense
    {
        return Expense::firstOrNew([
            'expense_month' => Carbon::parse($this->data['expense_month'])->startOfMonth()->toDateString(),
        ]);
    }
}
