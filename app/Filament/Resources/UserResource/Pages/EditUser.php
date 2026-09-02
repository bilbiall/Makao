<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\StaffAssignment;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function beforeSave(): void
    {
        $role = $this->data['role'] ?? null;

        // Only the property owner may grant the Admin role - promoting an existing
        // staff account to admin is blocked the same way creating one is, unless the
        // record was already admin (editing other fields on an existing admin).
        if ($role === 'admin' && $this->record->role !== 'admin' && auth()->user()->role !== 'landlord') {
            Notification::make()
                ->danger()
                ->title('Not allowed')
                ->body('Only the property owner can grant the Admin role.')
                ->send();

            throw new Halt();
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Hydrate the virtual location_ids/house_ids fields from the staff_assignments
        // pivot - neither is a real column on User, so Filament can't populate them automatically.
        $data['location_ids'] = $this->record->staffAssignments()->whereNotNull('location_id')->pluck('location_id')->all();
        $data['house_ids'] = $this->record->staffAssignments()->whereNotNull('house_id')->pluck('house_id')->all();

        return $data;
    }

    protected function afterSave(): void
    {
        $user = $this->record;

        // Replace this user's assignments wholesale with whatever the form submitted -
        // simplest correct behaviour for a small admin-managed list like this.
        $user->staffAssignments()->delete();

        if (in_array($user->role, ['manager', 'caretaker'])) {
            foreach (($this->data['location_ids'] ?? []) as $locationId) {
                StaffAssignment::create([
                    'user_id' => $user->id,
                    'location_id' => $locationId,
                    'role' => $user->role,
                    'assigned_by' => auth()->id(),
                ]);
            }
        }

        if ($user->role === 'agent') {
            foreach (($this->data['house_ids'] ?? []) as $houseId) {
                StaffAssignment::create([
                    'user_id' => $user->id,
                    'house_id' => $houseId,
                    'role' => 'agent',
                    'assigned_by' => auth()->id(),
                ]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
