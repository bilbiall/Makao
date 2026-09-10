<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Filament\Resources\TenantResource\RelationManagers;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

//for the panel
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;

use Filament\Tables\Columns\TextColumn;

use App\Models\User; // Afor linking tenant to user
use App\Helpers\SmsHelper; // ✅ for sms
use Illuminate\Support\Str; // For generating a temporary password


class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //form input for new tenant
                /*Select::make('house_id')
                    ->label('House')
                    ->relationship('house', 'house_name', modifyQueryUsing: function ($query) {
                        $query->where('house_status', 'Vacant');
                    })
                    ->searchable()
                    ->required(),*/
                /*Select::make('house_id')
                    ->label('House')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        return \App\Models\House::query()
                            ->where('house_name', 'like', "%{$search}%")
                            ->pluck('house_name', 'id');
                    })
                    ->getOptionLabelUsing(function ($value): ?string {
                        return \App\Models\House::find($value)?->house_name;
                    })
                    ->required(),*/
                    /*Select::make('house_id')
                        ->label('House')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search) {
                            return \App\Models\House::query()
                                ->where('house_status', 'Vacant') // ✅ Only vacant
                                ->where('house_name', 'like', "%{$search}%")
                                ->pluck('house_name', 'id');
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            return \App\Models\House::find($value)?->house_name;
                        })
                        ->required(),*/

                //cleaner alternan=tive
                //select user
                /*Select::make('user_id')
                    ->label('Linked User')
                    ->relationship('user', 'name') // assumes User model has a 'name' column
                    ->searchable(),
                    ->required(),*/

                //select user or alt create new user
                /*Select::make('user_id')
                    ->label('Linked User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')->required(),
                        TextInput::make('email')->email()->required()->unique(User::class, 'email'),
                        TextInput::make('password')->password()->required(),
                    ])
                    ->createOptionUsing(function (array $data) {
                        return User::create([
                            'name' => $data['name'],
                            'email' => $data['email'],
                            'password' => bcrypt($data['password']),
                        ]);
                    }),*/
                // No account is created here anymore - the tenant self-registers and
                // connects to this record via a 6-char join code sent by SMS (see
                // Tenant::booted()'s `created` hook / Tenant::sendInviteSms()). If this
                // tenant already has a linked account (e.g. admitted via an approved
                // viewing request first), `user_id` stays whatever it already is -
                // this form never sets it directly.
                Select::make('house_id')
                    ->label('House')
                    ->relationship('house', 'house_name', modifyQueryUsing: fn ($query) =>
                        $query->where('house_status', 'Vacant')
                    )
                    ->searchable()
                    ->required(),



                TextInput::make('tenant_name')->required(),
                TextInput::make('email')->email()->helperText('Optional - only used if you want to reach this tenant by email too.'),
                TextInput::make('phone_number')->required()
                    ->helperText('The tenant self-registers and connects to this record via a code texted to this number - make sure it\'s correct.'),
                TextInput::make('payment_account_code')
                    ->label('M-Pesa Account Number')
                    ->helperText('What this tenant should type as the Paybill Account Number when paying rent directly via M-Pesa. Defaults to their unit name - change it if you\'d rather they use something else.')
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('landlord_id', auth()->user()->landlord_id))
                    ->maxLength(50),
                /*Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name') // or 'email' if you want email shown
                    ->searchable()
                    ->required(), // Optional if you want to always assign user*/

                DatePicker::make('date_admitted')->default(now())->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //for the display in tenants page
                TextColumn::make('tenant_name')->searchable(),
                TextColumn::make('house.house_name')->label('House'),
                TextColumn::make('house.rent_amount')->label('Rent')->money('KES'),
                /*TextColumn::make('latestInvoice.balance')
                    ->label('Balance')
                    ->money('KES')
                    ->color(function ($state) {
                        if ($state === null) return 'secondary'; // No invoice yet
                        if ($state == 0) return 'success';
                        if ($state < 0) return 'warning'; // Overpaid
                        return 'danger'; // Still owing
                    }),*/
                //include color for overpaid
                TextColumn::make('latestPayment.balance')
                    ->label('Balance')
                    ->money('KES')
                    ->color(function ($state) {
                        if ($state === null) {
                            return 'secondary'; // No payment yet
                        } elseif ($state == 0) {
                            return 'success'; // Fully paid
                        } elseif ($state < 0) {
                            return 'warning'; // Overpaid
                        } else {
                            return 'danger'; // Still has balance due
                        }
                    }),


                TextColumn::make('date_admitted')->date(),
                Tables\Columns\TextColumn::make('user_id')
                    ->label('Account')
                    ->state(fn (Tenant $record) => $record->user_id ? 'Connected' : 'Pending invite')
                    ->badge()
                    ->color(fn (Tenant $record) => $record->user_id ? 'success' : 'warning'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('resend_invite')
                    ->label('Resend invite')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Tenant $record) => !$record->user_id)
                    ->requiresConfirmation()
                    ->modalDescription('Generates a new code and re-sends the invite SMS to this tenant\'s phone number.')
                    ->action(function (Tenant $record) {
                        $record->update([
                            'join_code' => Tenant::generateJoinCode(),
                            'join_code_expires_at' => now()->addDays(14),
                        ]);
                        $record->sendInviteSms();

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Invite resent')
                            ->send();
                    }),
                Tables\Actions\Action::make('message')
                    ->label('Message')
                    ->icon('heroicon-s-chat-bubble-left')
                    ->color('success')
                    ->url(function (Tenant $record) {
                        $phone = $record->phone_number;
                        // Remove any non-digit characters from phone
                        $phone = preg_replace('/\D/', '', $phone);
                        // Ensure it starts with country code (254 for Kenya)
                        if (!str_starts_with($phone, '254')) {
                            $phone = '254' . ltrim($phone, '0');
                        }
                        // Default message with tenant name
                        $message = "Hello {$record->tenant_name}, I have a message for you regarding ...";
                        return 'https://wa.me/' . $phone . '?text=' . urlencode($message);
                    })
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Tenants')
                        ->modalDescription('Are you sure you want to delete the selected tenants? Their data will be archived for 60 days before permanent deletion.')
                        ->modalSubmitActionLabel('Yes, Delete Tenants')
                        ->successNotificationTitle('Tenants Deleted'),
                ]),
            ])
            ->recordUrl(
                fn (Tenant $record): string => Pages\ViewTenant::getUrl([$record->id])
            );
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'view' => Pages\ViewTenant::route('/{record}'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }

    /**
     * Filter resources by caretaker's location
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Manager/Caretaker are narrowed to their assigned properties (staff_assignments pivot).
        return \App\Support\StaffScope::onTenant($query);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return true;
        }

        if (!$user->hasPermission(\App\Support\StaffPermissions::ADMIT_TENANTS)) {
            return false;
        }

        if (!$user->landlord_id) {
            return true;
        }

        return app(\App\Services\PackageLimitService::class)
            ->canAdd('tenants', \App\Models\Landlord::find($user->landlord_id));
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasPermission(\App\Support\StaffPermissions::EDIT_TENANTS) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasPermission(\App\Support\StaffPermissions::VACATE_TENANTS) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasPermission(\App\Support\StaffPermissions::VACATE_TENANTS) ?? false;
    }
}
