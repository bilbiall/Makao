<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ViewingRequestResource\Pages;
use App\Models\Tenant;
use App\Models\ViewingRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The admission workflow's admin-facing surface: a prospective renter ("user")
 * requests a viewing on a public listing (App\Http\Controllers\PropertyListingController),
 * and a landlord/manager/caretaker either admits them (promoting User -> Tenant,
 * reusing Tenant::booted()'s existing side effects) or revokes the request.
 */
class ViewingRequestResource extends Resource
{
    protected static ?string $model = ViewingRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Viewing Requests';
    protected static ?string $navigationGroup = 'Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user.name')->label('Requested by')->disabled(),
                Forms\Components\TextInput::make('house.house_name')->label('House')->disabled(),
                Forms\Components\Textarea::make('admin_notes')->label('Notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Requested by')->searchable(),
                Tables\Columns\TextColumn::make('user.phone_number')->label('Phone')->toggleable(),
                Tables\Columns\TextColumn::make('house.house_name')->label('House')->searchable(),
                Tables\Columns\TextColumn::make('house.location.location_name')->label('Property')->toggleable(),
                Tables\Columns\TextColumn::make('requested_at')->label('Requested')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'admitted' => 'success',
                        'revoked' => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'admitted' => 'Admitted', 'revoked' => 'Revoked']),
            ])
            ->actions([
                Tables\Actions\Action::make('admit')
                    ->label('Admit')
                    ->color('success')
                    ->icon('heroicon-s-check')
                    ->requiresConfirmation()
                    ->modalDescription('This will promote the requester to a tenant of this house.')
                    ->form([
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->required()
                            ->default(fn (ViewingRequest $record) => $record->user->phone_number),
                    ])
                    ->action(function (ViewingRequest $record, array $data) {
                        $user = $record->user;
                        $house = $record->house;

                        if ($house->house_status !== 'Vacant') {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Cannot admit')
                                ->body('This house is no longer vacant (it may have been marked Occupied or Unavailable). Revoke this request instead.')
                                ->send();
                            return;
                        }

                        $user->update([
                            'role' => 'tenant',
                            'landlord_id' => $house->landlord_id,
                            'phone_number' => $data['phone_number'],
                        ]);

                        // Everything downstream (house flips to Occupied, welcome SMS,
                        // admin notifications, plan-limit enforcement) already happens
                        // via Tenant::booted() - reused as-is, not duplicated here.
                        Tenant::create([
                            'user_id' => $user->id,
                            'house_id' => $house->id,
                            'tenant_name' => $user->name,
                            'email' => $user->email,
                            'phone_number' => $data['phone_number'],
                            'date_admitted' => now(),
                        ]);

                        $record->update([
                            'status' => 'admitted',
                            'handled_by' => auth()->id(),
                        ]);
                    })
                    ->visible(fn (ViewingRequest $record) => $record->status === 'pending'),

                Tables\Actions\Action::make('revoke')
                    ->label('Revoke')
                    ->color('danger')
                    ->icon('heroicon-s-x-mark')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')->label('Reason (optional)'),
                    ])
                    ->action(function (ViewingRequest $record, array $data) {
                        $record->update([
                            'status' => 'revoked',
                            'admin_notes' => $data['admin_notes'] ?? null,
                            'handled_by' => auth()->id(),
                        ]);

                        $record->user->notify(new \App\Notifications\DatabaseNotification(
                            'Viewing Request Update',
                            "Your viewing request for {$record->house->house_name} was not successful." . (!empty($data['admin_notes']) ? " {$data['admin_notes']}" : ''),
                            route('app.user.applications')
                        ));
                    })
                    ->visible(fn (ViewingRequest $record) => $record->status === 'pending'),
            ]);
    }

    /**
     * Manager/Caretaker are narrowed to their assigned properties (staff_assignments pivot).
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // ViewingRequest has no direct location_id column, but it does have a
        // house() relation - StaffScope::onTenant() narrows via any model's house()
        // relation (the name reflects Tenant's usage, not a Tenant-only constraint).
        return \App\Support\StaffScope::onTenant($query);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListViewingRequests::route('/'),
        ];
    }
}
