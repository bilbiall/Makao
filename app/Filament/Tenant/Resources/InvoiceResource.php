<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\InvoiceResource\Pages;
use App\Filament\Tenant\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Illuminate\Support\Facades\Auth;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Str;






class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //display data for a user/tenant
                TextColumn::make('invoice_number')->label('Invoice #'),
                TextColumn::make('amount')->money('KES')->label('Amount'),
                TextColumn::make('balance')->money('KES')->label('Balance'),
                TextColumn::make('status')->badge()->color(fn(string $state) => match ($state) {
                    'paid' => 'success',
                    'partial' => 'warning',
                    default => 'danger',
                }),
            TextColumn::make('invoice_date')->label('Invoice Date')->date(),
            TextColumn::make('due_date')->label('Due Date')->date(),
            ])
            ->filters([
                //tenants filters
                SelectFilter::make('status')
                    ->label('Payment Status')
                    ->options([
                        'Paid' => 'Paid',
                        'Partial' => 'Partial',
                        'Unpaid' => 'Unpaid',
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
            ])
            ->actions([
                Action::make('pay')
                    ->label('Pay Now')
                    ->button()
                    ->form([
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'mpesa' => 'M-Pesa (STK Push)',
                                'pesapal' => 'Pesapal',
                            ])
                            ->required()
                            ->default('mpesa'),
                        TextInput::make('amount')
                            ->label('Amount to pay (KES)')
                            ->numeric()
                            ->required()
                            ->default(fn ($record) => $record->balance),
                        TextInput::make('phone_number')
                            ->label('Phone Number (for M-Pesa)')
                            ->placeholder('0712345678 or +254712345678')
                            ->helperText('Required for M-Pesa payment'),
                    ])
                    ->modalWidth('md')
                    ->modalHeading('Pay Rent')
                    ->modalButton('Continue to Payment')
                    ->action(function (Invoice $record, array $data, $livewire) {
                        $amount = $data['amount'] ?? 0;
                        $method = $data['payment_method'] ?? 'mpesa';

                        if ($method === 'mpesa') {
                            $url = route('tenant.mpesa.initiate', [
                                'invoice' => $record->id,
                                'amount' => $amount,
                                'phone_number' => $data['phone_number'] ?? '',
                            ]);
                            $livewire->redirect($url);
                        } else {
                            $url = route('tenant.payments.initiate', [
                                'invoice' => $record->id,
                                'amount' => $amount,
                            ]);
                            $livewire->redirect($url);
                        }
                    }),
            ])
           /* ->actions([
                Tables\Actions\EditAction::make(),
            ]) */
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

    // Disable creation from the admin panel (tenants still use their own resource)
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            //'create' => Pages\CreateInvoice::route('/create'),
            //'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    //users to access their only invoices
    public static function getEloquentQuery(): Builder
    {
        $tenant = Auth::user()?->tenant;


        if (!$tenant) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // prevent any access
        }

        return parent::getEloquentQuery()
            ->where('tenant_id', $tenant->id);
    }


}
