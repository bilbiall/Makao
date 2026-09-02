<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Landlord;
use App\Services\PackageLimitService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use App\Helpers\SmsHelper;
use App\Helpers\EmailHelper;
use App\Helpers\EmailTemplateHelper;
use App\Helpers\SmsTemplateHelper;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Staff seats (admin/manager/caretaker/agent) are plan-limited, same call shape
     * already used in House/Location/Tenant's creating hooks - tenants and the
     * landlord's own account are never counted, so this only fires for staff roles.
     */
    protected function beforeCreate(): void
    {
        $role = $this->data['role'] ?? null;

        // Only the property owner may grant the Admin role - a staff 'admin' account
        // must not be able to create peer admins. Server-side check because the form's
        // hidden option alone doesn't stop a tampered request.
        if ($role === 'admin' && auth()->user()->role !== 'landlord') {
            Notification::make()
                ->danger()
                ->title('Not allowed')
                ->body('Only the property owner can create Admin accounts.')
                ->send();

            throw new Halt();
        }

        if (!in_array($role, ['admin', 'manager', 'caretaker', 'agent'])) {
            return;
        }

        $landlord = Landlord::find(auth()->user()->landlord_id);
        $limitService = app(PackageLimitService::class);

        if (!$limitService->canAdd('users', $landlord)) {
            Notification::make()
                ->danger()
                ->title('Plan limit reached')
                ->body($limitService->limitMessage('users', $landlord))
                ->send();

            throw new Halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store plain password before hashing for notification
        $data['plain_password'] = $data['password'];
        // New staff (admin/caretaker) belong to the creating landlord's own account -
        // landlord_id is never a form field, so it can't be tampered with by the submitter.
        $data['landlord_id'] = auth()->user()->landlord_id;
        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;

        if (in_array($user->role, ['manager', 'caretaker'])) {
            foreach (($this->data['location_ids'] ?? []) as $locationId) {
                \App\Models\StaffAssignment::create([
                    'user_id' => $user->id,
                    'location_id' => $locationId,
                    'role' => $user->role,
                    'assigned_by' => auth()->id(),
                ]);
            }
        }

        if ($user->role === 'agent') {
            foreach (($this->data['house_ids'] ?? []) as $houseId) {
                \App\Models\StaffAssignment::create([
                    'user_id' => $user->id,
                    'house_id' => $houseId,
                    'role' => 'agent',
                    'assigned_by' => auth()->id(),
                ]);
            }
        }

        $plainPassword = $this->data['plain_password'] ?? null;

        if (! $plainPassword) {
            return;
        }

        $siteUrl = url('/login');

        // Send email notification
        if ($user->email) {
            $emailBody = EmailTemplateHelper::render('new_user', [
                'user_name' => $user->name,
                'email' => $user->email,
                'password' => $plainPassword,
                'role' => ucfirst($user->role),
                'site_url' => $siteUrl,
            ], $user->landlord_id);

            try {
                EmailHelper::send(
                    $user->email,
                    'Your Account Has Been Created',
                    $emailBody
                );
            } catch (\Throwable $e) {
                // Log but don't fail user creation
            }
        }

        // Send SMS notification if phone number available
        $phone = $user->phone_number ?? ($user->tenant ? $user->tenant->phone_number : null);
        
        if ($phone) {
            $sms = SmsTemplateHelper::render('template_new_user_sms', [
                'user_name' => $user->name,
                'email' => $user->email,
                'password' => $plainPassword,
                'role' => ucfirst($user->role),
                'site_url' => $siteUrl,
            ], $user->landlord_id);

            try {
                SmsHelper::sendSms($phone, $sms, $user->landlord_id);
            } catch (\Throwable $e) {
                // Log but don't fail user creation
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
