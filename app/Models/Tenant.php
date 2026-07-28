<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

//use helper for sending sms
use App\Helpers\SmsHelper;
use App\Helpers\ActivityLogger;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\HasMany; // for relationship with issues

class Tenant extends Model
{
    //all tenant db related stuff here
    protected $fillable = [
        'house_id',
        'tenant_name',
        'email',
        'phone_number',
        'date_admitted',
        'balance',
    ];

    //relationship for with the house model
    public function house()
    {
        return $this->belongsTo(House::class);
    }

    //relationship with bill model
    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    //rship with invoices
    use HasFactory;

    // A tenant can have many invoices
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    //relationship to have the latest balance
    public function latestInvoice()
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    //relationship to have the latest payment
    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    //relationship with payments
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    //relationship with user for tenant panel
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //relationship for issues
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }





    //to update status of house to occupied
    protected static function booted()
    {
        static::created(function ($tenant) {
            $tenant->house->update(['house_status' => 'Occupied']);
            
            // Get template from settings
            $settings = \App\Models\Setting::singleton();
            $template = $settings->payload['template_tenant_welcome'] ?? 'Hello {tenant_name}, welcome to {app_name}. You were admitted to {house_name} with a monthly rent of KES {rent_amount}';
            
            // Replace variables
            $message = str_replace(
                ['{tenant_name}', '{app_name}', '{house_name}', '{rent_amount}'],
                [$tenant->tenant_name, config('app.name'), $tenant->house->house_name, $tenant->house->rent_amount],
                $template
            );

            // Send SMS using your helper
            SmsHelper::sendSms($tenant->phone_number, $message);

            // Notify admins via database about new tenant admission
            $admins = \App\Models\User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\DatabaseNotification(
                    'New Tenant Admitted',
                    "{$tenant->tenant_name} was admitted to {$tenant->house->house_name}",
                    null
                ));
            }

            // Send welcome notification to tenant user (if linked)
            if ($tenantUser = $tenant->user ?? null) {
                $tenantUser->notify(new \App\Notifications\DatabaseNotification(
                    'Welcome!',
                    "Welcome to " . config('app.name') . "! You've been admitted to {$tenant->house->house_name} with a monthly rent of KES " . number_format($tenant->house->rent_amount, 2),
                    null
                ));
            }

            // Log tenant creation
            try {
                $actor = auth()->id() ?? null;
                ActivityLogger::log('create_tenant', $actor, "New tenant {$tenant->tenant_name} admitted to {$tenant->house->house_name}");
            } catch (\Throwable $e) {
                // ignore logging errors
            }
        });

        static::updated(function ($tenant) {
            // Check if the house was changed
            if ($tenant->isDirty('house_id')) {
                $originalHouseId = $tenant->getOriginal('house_id');

                // Set old house to Vacant
                \App\Models\House::where('id', $originalHouseId)->update([
                    'house_status' => 'Vacant',
                ]);

                // Set new house to Occupied
                $tenant->house->update(['house_status' => 'Occupied']);
            }
            
            // Log tenant updates
            try {
                $actor = auth()->id() ?? null;
                $changes = [];
                
                if ($tenant->wasChanged('tenant_name')) {
                    $changes[] = "name from '{$tenant->getOriginal('tenant_name')}' to '{$tenant->tenant_name}'";
                }
                if ($tenant->wasChanged('phone_number')) {
                    $changes[] = "phone number";
                }
                if ($tenant->wasChanged('email')) {
                    $changes[] = "email";
                }
                if ($tenant->wasChanged('house_id')) {
                    $oldHouse = \App\Models\House::find($tenant->getOriginal('house_id'));
                    $newHouse = $tenant->house;
                    $oldName = $oldHouse ? $oldHouse->house_name : 'Unknown';
                    $newName = $newHouse ? $newHouse->house_name : 'Unknown';
                    $changes[] = "house from '{$oldName}' to '{$newName}'";
                }
                
                if (!empty($changes)) {
                    $details = "Updated tenant {$tenant->tenant_name}: " . implode(', ', $changes);
                    \App\Helpers\ActivityLogger::log('update_tenant', $actor, $details);
                }
            } catch (\Throwable $e) {
                // ignore logging errors
            }
        });

        static::deleted(function ($tenant) {
            $tenant->house->update(['house_status' => 'Vacant']);
        });
    }
}
