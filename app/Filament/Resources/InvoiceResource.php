<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Carbon;
use App\Models\Tenant;
use App\Models\Bill;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Textarea;

use Filament\Tables\Filters\SelectFilter;

//for notifs
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use App\Models\User;



class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-s-credit-card';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                // Select the tenant
                Select::make('tenant_id')
                    ->label('Select Tenant')
                    ->relationship('tenant', 'tenant_name')
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $tenant = \App\Models\Tenant::with('house')->find($state);

                        if ($tenant && $tenant->house) {
                            $rent = $tenant->house->rent_amount;

                            // Get bills for current month
                            $bills = \App\Models\Bill::where('tenant_id', $tenant->id)
                                ->whereMonth('bill_month', now()->month)
                                ->whereYear('bill_month', now()->year)
                                ->first();

                            $billTotal = $bills
                                ? ($bills->water ?? 0) + ($bills->trash ?? 0) + ($bills->electricity ?? 0) + ($bills->internet ?? 0)
                                : 0;

                            // 🟣 New: Get tenant's running balance (owed/overpaid)
                            $previousBalance = $tenant->balance ?? 0;

                            // 🟣 Compute total amount to be paid this month
                            $total = ($rent + $billTotal) - $previousBalance;

                            $set('rent_only', $rent);
                            $set('bill_only', $billTotal);
                            $set('previous_balance', $previousBalance);
                            $set('total_amount', $total > 0 ? $total : 0); // Avoid negative invoice
                        } else {
                            $set('rent_only', null);
                            $set('bill_only', null);
                            $set('previous_balance', null);
                            $set('total_amount', null);
                        }
                    }),
                    /*TextInput::make('previous_balance')
                        ->label('Previous Balance Adjustment')
                        ->disabled(),*/



                    /*->afterStateUpdated(function ($state, callable $set) {
                        $tenant = \App\Models\Tenant::with('house')->find($state);

                        if ($tenant && $tenant->house) {
                            $rent = $tenant->house->rent_amount;

                            // Get bills for current month
                            $bills = \App\Models\Bill::where('tenant_id', $tenant->id)
                                ->whereMonth('bill_month', now()->month)
                                ->whereYear('bill_month', now()->year)
                                ->first();

                            $billTotal = $bills
                                ? ($bills->water ?? 0) + ($bills->trash ?? 0) + ($bills->electricity ?? 0) + ($bills->internet ?? 0)
                                : 0;

                            $set('total_amount', $rent + $billTotal);
                            $set('rent_only', $rent);
                            $set('bill_only', $billTotal);
                        } else {
                            $set('total_amount', null);
                            $set('rent_only', null);
                            $set('bill_only', null);
                        }
                    }),*/

                // Read-only breakdown fields
                TextInput::make('rent_only')
                    ->label('House Rent (KES)')
                    ->disabled(),

                TextInput::make('bill_only')
                    ->label('Bills Total (KES)')
                    ->disabled(),

                TextInput::make('total_amount')
                    ->label('Total Amount (KES)')
                    ->disabled(),

                // Other fields
                DatePicker::make('invoice_date')
                    ->default(now())
                    ->required(),

                DatePicker::make('due_date')
                    ->default(now()->addDays(10))
                    ->required(),

                Textarea::make('comment')
                    ->placeholder('Optional note'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //invoice view
                TextColumn::make('invoice_number')->label('Invoice #'),
                TextColumn::make('tenant.tenant_name')->label('Tenant'),
                TextColumn::make('tenant.house.house_name')->label('House'),
                //TextColumn::make('total_amount')->money('KES'),
                TextColumn::make('amount')
                    ->label('Total Amount')
                    ->money('KES') // Optional: formats as currency
                    ->sortable()
                    ->searchable(),

                TextColumn::make('balance')
                    ->label('Balance')
                    ->money('KES') // Optional: formats as currency
                    ->sortable()
                    ->searchable(),

                TextColumn::make('invoice_date')->date(),
                TextColumn::make('due_date')->date(),
                /*TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => $state === 'Paid' ? 'success' : 'danger'),*/
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match (strtolower($state)) {
                        'paid' => 'success',   // Green
                        'partial' => 'purple', // Purple
                        default => 'danger',   // Red for unpaid and any unknowns
                    }),


            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Payment Status')
                    ->options([
                        'paid' => 'Paid',
                        'partial' => 'Partial',
                        'unpaid' => 'Unpaid',
                    ]),

                //filters for invoices month
                Filter::make('bill_month')
                    ->label('Filter by Month & Year')
                    ->form([
                        DatePicker::make('month')->label('Pick Month'),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['month'])) {
                            $query->whereMonth('invoice_date', $data['month']->format('m'))
                                ->whereYear('invoice_date', $data['month']->format('Y'));
                        }
                    }),

                SelectFilter::make('location')
                    ->label('Location')
                    ->relationship('tenant.house.location', 'location_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    /**
     * Filter resources by caretaker's location
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Caretakers can only see invoices for tenants in their assigned location
        if ($user && $user->role === 'caretaker' && $user->location_id) {
            $query->whereHas('tenant.house', function ($q) use ($user) {
                $q->where('location_id', $user->location_id);
            });
        }

        return $query;
    }

    //for notifs
    /*protected function afterCreate(): void
{
    $invoice = $this->record;

    // Build message
    $message = "Invoice #{$invoice->invoice_number} created for {$invoice->tenant->tenant_name}, amount KES {$invoice->amount}";

    Notification::make()
        ->title('🧾 New Invoice')
        ->body($message)
        ->warning() // or ->success(), ->danger(), etc.
        ->actions([
            Action::make('view')
                ->label('View Invoice')
                ->url(route('filament.admin.invoices.edit', ['record' => $invoice->id]))
                ->openUrlInNewTab(),
        ])
        ->sendToDatabase(User::where('role', 'tenant')->get());
}*/
}
