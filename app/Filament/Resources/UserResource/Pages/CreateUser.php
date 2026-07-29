<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Helpers\SmsHelper;
use App\Helpers\EmailHelper;
use App\Helpers\EmailTemplateHelper;
use App\Helpers\SmsTemplateHelper;
use App\Helpers\AppHelper;
use App\Models\Setting;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

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
        $plainPassword = $this->data['plain_password'] ?? null;

        if (! $plainPassword) {
            return;
        }

        $settings = Setting::singleton();
        $payload = $settings->payload ?? [];
        $siteUrl = url('/login');

        // Send email notification
        if ($user->email) {
            $emailBody = EmailTemplateHelper::render('new_user', [
                'user_name' => $user->name,
                'email' => $user->email,
                'password' => $plainPassword,
                'role' => ucfirst($user->role),
                'site_url' => $siteUrl,
            ]);

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
                'app_name' => AppHelper::getAppName(),
            ]);

            try {
                SmsHelper::sendSms($phone, $sms);
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
