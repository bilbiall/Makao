<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToLandlord;
use App\Helpers\ActivityLogger;
use App\Helpers\SmsHelper;
use App\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class NoticeToVacate extends Model
{
    use HasFactory, BelongsToLandlord;

    protected $fillable = [
        'tenant_id',
        'vacate_date',
        'reason_type',
        'reason_text',
        'status',
        'admin_notes',
        'approved_by',
        'approved_at',
        'denied_at',
        'landlord_id',
    ];

    protected $casts = [
        'vacate_date' => 'date',
        'approved_at' => 'datetime',
        'denied_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approve the notice, end the tenancy, and notify the tenant. This is the
     * single source of truth for "approve" so Filament and the AdminApp
     * app-shell can never drift apart on what approving actually does.
     */
    public function approve(?string $adminNotes = null): void
    {
        if ($this->status !== 'pending') {
            return;
        }

        $this->status = 'approved';
        $this->approved_at = now();
        $this->approved_by = auth()->id();
        $this->admin_notes = $adminNotes;
        $this->save();

        $tenant = $this->tenant;
        if (!$tenant) {
            return;
        }

        $settings = Setting::forLandlord($this->landlord_id);
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
                $this->vacate_date->format('d M Y'),
                $tenant->house?->location?->location_name ?? '',
            ],
            $template
        );

        $phone = preg_replace('/\D/', '', $tenant->phone_number);
        if (!Str::startsWith($phone, '254')) {
            $phone = '254' . ltrim($phone, '0');
        }
        try {
            SmsHelper::sendSms($phone, $message, $this->landlord_id);
        } catch (\Throwable $e) {
            // ignore SMS errors
        }

        if ($tenantUser = $tenant->user ?? null) {
            $tenantUser->notify(new DatabaseNotification(
                'Notice to Vacate Approved',
                "Your notice to vacate {$tenant->house->house_name} on " . $this->vacate_date->format('M j, Y') . " has been approved.",
                null
            ));
        }

        try {
            ActivityLogger::log('approve_notice', auth()->id(), "Notice to vacate approved for {$tenant->tenant_name} from {$tenant->house->house_name} (Vacate date: {$this->vacate_date->format('M j, Y')})");
        } catch (\Throwable $e) {
            // ignore
        }

        // Capture the linked User before delete - TenantObserver archives the
        // tenancy itself to DeletedTenant, so this is only about returning the
        // User to a clean browsing state.
        $tenantUserToDemote = $tenant->user;

        // Delete tenant (observer archives to DeletedTenant, frees the house)
        $tenant->delete();

        // Demote the User back to a self-registered "looking for a house"
        // account now that the tenancy has ended - only if that's how they
        // originally registered (never touch admin/landlord/staff accounts
        // that happen to also be linked as a tenant record).
        if ($tenantUserToDemote && $tenantUserToDemote->role === 'tenant') {
            $tenantUserToDemote->update(['role' => 'user', 'landlord_id' => null]);
        }
    }

    /**
     * Deny the notice and notify the tenant. Tenancy continues unchanged.
     */
    public function deny(?string $adminNotes = null): void
    {
        if ($this->status !== 'pending') {
            return;
        }

        $this->status = 'denied';
        $this->denied_at = now();
        $this->approved_by = auth()->id();
        $this->admin_notes = $adminNotes;
        $this->save();

        $tenant = $this->tenant;
        if (!$tenant) {
            return;
        }

        $settings = Setting::forLandlord($this->landlord_id);
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
                $this->vacate_date->format('d M Y'),
                $tenant->house?->location?->location_name ?? '',
            ],
            $template
        );
        $phone = preg_replace('/\D/', '', $tenant->phone_number);
        if (!Str::startsWith($phone, '254')) {
            $phone = '254' . ltrim($phone, '0');
        }
        try {
            SmsHelper::sendSms($phone, $message, $this->landlord_id);
        } catch (\Throwable $e) {
            // ignore SMS errors
        }

        if ($tenantUser = $tenant->user ?? null) {
            $tenantUser->notify(new DatabaseNotification(
                'Notice to Vacate Denied',
                "Your notice to vacate {$tenant->house->house_name} on " . $this->vacate_date->format('M j, Y') . " has been denied. " . ($adminNotes ? "Reason: {$adminNotes}" : ''),
                null
            ));
        }

        try {
            $reason = $adminNotes ? " Reason: {$adminNotes}" : '';
            ActivityLogger::log('deny_notice', auth()->id(), "Notice to vacate denied for {$tenant->tenant_name} from {$tenant->house->house_name}.{$reason}");
        } catch (\Throwable $e) {
            // ignore
        }
    }

    protected static function booted()
    {
        static::creating(function ($notice) {
            if (!$notice->landlord_id && $notice->tenant_id) {
                $notice->landlord_id = \App\Models\Tenant::withoutGlobalScopes()->find($notice->tenant_id)?->landlord_id;
            }
        });

        // Notify admins when a tenant submits a notice to vacate
        static::created(function ($notice) {
            $tenant = $notice->tenant;
            $admins = \App\Models\User::where('role', 'admin')->where('landlord_id', $notice->landlord_id)->get();
            
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\DatabaseNotification(
                    'New Notice to Vacate',
                    "{$tenant->tenant_name} submitted a notice to vacate {$tenant->house->house_name} on " . $notice->vacate_date->format('M j, Y'),
                    null
                ));
            }
            
            // Log notice creation
            try {
                $actor = auth()->id() ?? null;
                \App\Helpers\ActivityLogger::log('create_notice', $actor, "Notice to vacate submitted by {$tenant->tenant_name} for {$tenant->house->house_name} (Date: {$notice->vacate_date->format('M j, Y')})");
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
}
