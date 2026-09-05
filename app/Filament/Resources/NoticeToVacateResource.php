<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NoticeToVacateResource\Pages;
use App\Models\NoticeToVacate;
use App\Models\Setting;
use App\Helpers\SmsHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class NoticeToVacateResource extends Resource
{
    protected static ?string $model = NoticeToVacate::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationLabel = 'Notices to Vacate';
    protected static ?string $navigationGroup = 'Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tenant.tenant_name')
                    ->label('Tenant')
                    ->disabled(),
                Forms\Components\DatePicker::make('vacate_date')
                    ->label('Vacate Date')
                    ->disabled(),
                Forms\Components\TextInput::make('reason_type')
                    ->label('Reason')
                    ->disabled(),
                Forms\Components\Textarea::make('reason_text')
                    ->label('Details')
                    ->disabled(),
                Forms\Components\Textarea::make('admin_notes')
                    ->label('Admin Notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.tenant_name')
                    ->label('Tenant')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tenant.house.house_name')
                    ->label('House')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->state(function ($record) {
                        $balance = optional($record->tenant->latestPayment)->balance ?? 0;
                        if ($balance < 0) {
                            return '+' . number_format(abs($balance), 2);
                        }
                        return number_format($balance, 2);
                    })
                    ->prefix('KES ')
                    ->color(fn ($record) => match (true) {
                        optional($record->tenant->latestPayment)->balance > 0 => 'danger',
                        optional($record->tenant->latestPayment)->balance < 0 => 'warning',
                        default => 'success',
                    })
                    ->weight('bold')
                    ->sortable(query: function ($query, $direction) {
                        return $query->join('tenants', 'notice_to_vacates.tenant_id', '=', 'tenants.id')
                            ->leftJoin('payments as latest_payment', function ($join) {
                                $join->on('tenants.id', '=', 'latest_payment.tenant_id')
                                    ->whereRaw('latest_payment.id = (SELECT MAX(id) FROM payments WHERE tenant_id = tenants.id)');
                            })
                            ->orderBy('latest_payment.balance', $direction);
                    }),
                Tables\Columns\TextColumn::make('vacate_date')
                    ->label('Vacate Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason_type')
                    ->label('Reason')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'denied' => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved On')
                    ->dateTime()
                    ->toggleable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'denied' => 'Denied',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-s-check')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Approval Notes'),
                    ])
                    ->action(function (NoticeToVacate $record, array $data) {
                        if ($record->status !== 'pending') {
                            return;
                        }
                        $record->status = 'approved';
                        $record->approved_at = now();
                        $record->approved_by = auth()->id();
                        $record->admin_notes = $data['admin_notes'] ?? null;
                        $record->save();

                        $tenant = $record->tenant;
                        if ($tenant) {
                            // Build SMS
                            $settings = Setting::forLandlord($record->landlord_id);
                            $payload = $settings->payload ?? [];
                            $template = $payload['template_notice_approved'] ?? (
                                "Hi {tenant_name}, your vacate notice has been approved. Balance: KES {balance}. Approval date: {approval_date}. Vacate date: {vacate_date}."
                            );

                            $balance = optional($tenant->latestPayment)->balance ?? 0;
                            $message = str_replace(
                                ['{tenant_name}', '{balance}', '{approval_date}', '{vacate_date}', '{property_name}'],
                                [
                                    $tenant->tenant_name,
                                    number_format($balance, 2),
                                    now()->format('d M Y'),
                                    $record->vacate_date->format('d M Y'),
                                    $tenant->house?->location?->location_name ?? '',
                                ],
                                $template
                            );

                            $phone = preg_replace('/\D/', '', $tenant->phone_number);
                            if (!Str::startsWith($phone, '254')) {
                                $phone = '254' . ltrim($phone, '0');
                            }
                            try {
                                SmsHelper::sendSms($phone, $message, $record->landlord_id);
                            } catch (\Throwable $e) {
                                // ignore SMS errors
                            }

                            // Send database notification to tenant user
                            if ($tenantUser = $tenant->user ?? null) {
                                $tenantUser->notify(new \App\Notifications\DatabaseNotification(
                                    'Notice to Vacate Approved',
                                    "Your notice to vacate {$tenant->house->house_name} on " . $record->vacate_date->format('M j, Y') . " has been approved.",
                                    null
                                ));
                            }

                            // Log notice approval
                            try {
                                \App\Helpers\ActivityLogger::log('approve_notice', auth()->id(), "Notice to vacate approved for {$tenant->tenant_name} from {$tenant->house->house_name} (Vacate date: {$record->vacate_date->format('M j, Y')})");
                            } catch (\Throwable $e) {
                                // ignore
                            }

                            // Capture the linked User before delete - TenantObserver
                            // archives the tenancy itself to DeletedTenant, so this is
                            // only about returning the User to a clean browsing state.
                            $tenantUserToDemote = $tenant->user;

                            // Delete tenant (observer will archive to Vacated/Deleted tenants)
                            $tenant->delete();

                            // Demote User back to a self-registered "looking for a house"
                            // account now that the tenancy has ended - only if that's how
                            // they originally registered (never touch admin/landlord/staff
                            // accounts that happen to also be linked as a tenant record).
                            if ($tenantUserToDemote && $tenantUserToDemote->role === 'tenant') {
                                $tenantUserToDemote->update(['role' => 'user', 'landlord_id' => null]);
                            }
                        }
                    })
                    ->visible(fn (NoticeToVacate $record) => $record->status === 'pending'),

                Tables\Actions\Action::make('deny')
                    ->label('Deny')
                    ->color('danger')
                    ->icon('heroicon-s-x-mark')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Denial Notes'),
                    ])
                    ->action(function (NoticeToVacate $record, array $data) {
                        if ($record->status !== 'pending') {
                            return;
                        }
                        $record->status = 'denied';
                        $record->denied_at = now();
                        $record->approved_by = auth()->id();
                        $record->admin_notes = $data['admin_notes'] ?? null;
                        $record->save();

                        $tenant = $record->tenant;
                        if ($tenant) {
                            $settings = Setting::forLandlord($record->landlord_id);
                            $payload = $settings->payload ?? [];
                            $template = $payload['template_notice_denied'] ?? (
                                "Hi {tenant_name}, your vacate notice has been denied. Balance: KES {balance}. Date requested: {vacate_date}."
                            );
                            $balance = optional($tenant->latestPayment)->balance ?? 0;
                            $message = str_replace(
                                ['{tenant_name}', '{balance}', '{vacate_date}', '{property_name}'],
                                [
                                    $tenant->tenant_name,
                                    number_format($balance, 2),
                                    $record->vacate_date->format('d M Y'),
                                    $tenant->house?->location?->location_name ?? '',
                                ],
                                $template
                            );
                            $phone = preg_replace('/\D/', '', $tenant->phone_number);
                            if (!Str::startsWith($phone, '254')) {
                                $phone = '254' . ltrim($phone, '0');
                            }
                            try {
                                SmsHelper::sendSms($phone, $message, $record->landlord_id);
                            } catch (\Throwable $e) {
                                // ignore SMS errors
                            }

                            // Send database notification to tenant user
                            if ($tenantUser = $tenant->user ?? null) {
                                $tenantUser->notify(new \App\Notifications\DatabaseNotification(
                                    'Notice to Vacate Denied',
                                    "Your notice to vacate {$tenant->house->house_name} on " . $record->vacate_date->format('M j, Y') . " has been denied. " . ($data['admin_notes'] ? "Reason: {$data['admin_notes']}" : ''),
                                    null
                                ));
                            }
                            
                            // Log notice denial
                            try {
                                $reason = $data['admin_notes'] ? " Reason: {$data['admin_notes']}" : '';
                                \App\Helpers\ActivityLogger::log('deny_notice', auth()->id(), "Notice to vacate denied for {$tenant->tenant_name} from {$tenant->house->house_name}.{$reason}");
                            } catch (\Throwable $e) {
                                // ignore
                            }
                        }
                    })
                    ->visible(fn (NoticeToVacate $record) => $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNoticeToVacates::route('/'),
            'view' => Pages\ViewNoticeToVacate::route('/{record}'),
        ];
    }

    /**
     * Filter resources by caretaker's location
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Manager/Caretaker are narrowed to their assigned properties, Agent is denied
        // entirely - see StaffScope::onTenantChild() (this used to duplicate that logic
        // inline without the Agent deny-path, silently exposing every tenant's notices to
        // any Agent account with Filament access).
        return \App\Support\StaffScope::onTenantChild($query);
    }
}
