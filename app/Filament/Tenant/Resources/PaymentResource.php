<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\PaymentResource\Pages;
use App\Filament\Tenant\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Forms\Components\DatePicker;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;

use Illuminate\Support\Facades\Auth;
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

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
                //for tenants view
                //TextColumn::make('tenant.tenant_name')->label('Tenant')->searchable(),
                TextColumn::make('invoice.invoice_number')->label('Invoice'),
                TextColumn::make('amount_paid')->money('KES'),
                //TextColumn::make('balance')->label('Balance')->money('KES'),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->money('KES') // optional, if you want currency formatting
                    ->sortable()
                    ->color(function ($state) {
                        if ($state == 0) {
                            return 'success'; // Green for cleared balance
                        } elseif ($state < 0) {
                            return 'warning'; // Yellow for overpayment
                        }
                        return 'danger'; // Red for unpaid
                    }),

                /*TextColumn::make('payment_date')->date(),*/
                TextColumn::make('payment_date')->label('Payment Date')->date(),
            ])
            ->filters([
                //for tenants filter
                Filter::make('payment_month')
                    ->label('Filter by Month & Year')
                    ->form([
                        DatePicker::make('month')->label('Pick Month'),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['month'])) {
                            $query->whereMonth('payment_date', \Carbon\Carbon::parse($data['month'])->format('m'))
                                ->whereYear('payment_date', \Carbon\Carbon::parse($data['month'])->format('Y'));
                        }
                    }),
            ])
           /*  ->actions([
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

    // Disable creation from the tenant panel (tenants still use their own resource)
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            //'create' => Pages\CreatePayment::route('/create'),
            //'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    //restrict tenant to view only their payments
    public static function getEloquentQuery(): Builder
    {
        $tenant = Auth::user()?->tenant;

        if (!$tenant) {
        return parent::getEloquentQuery()->whereRaw('1 = 0'); // No access if tenant not found
        }

        return parent::getEloquentQuery()
            ->where('tenant_id', $tenant->id);
    }

}
