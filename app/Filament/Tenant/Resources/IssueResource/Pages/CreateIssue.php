<?php

namespace App\Filament\Tenant\Resources\IssueResource\Pages;

use App\Filament\Tenant\Resources\IssueResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

use App\Models\User; // or Admin model if separate
use App\Notifications\NewIssueNotification;

use Filament\Notifications\Notification;


class CreateIssue extends CreateRecord
{
    protected static string $resource = IssueResource::class;

    //assign tenant id
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        //$data['tenant_id'] = Auth::id();
        $data['tenant_id'] = Auth::user()?->tenant?->id; // ✅ Correctly sets tenant_id from the tenant model

        return $data;
    }

    protected function afterCreate(): void
    {
        // Notify all users with role 'admin'
        $admins = User::where('role', 'admin')->get();

       foreach ($admins as $admin) {
            $admin->notify(new NewIssueNotification($this->record));
        }

        //notif user
        Notification::make()
        ->title('Issue submitted')
        ->success()
        ->body('Your issue has been successfully submitted.')
        ->send();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }


}
