<?php

namespace App\Models;

use App\Helpers\EmailHelper;
use App\Helpers\SmsHelper;
use App\Models\Concerns\BelongsToLandlord;
use App\Support\StaffScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single broadcast to tenants, sent via SMS, Email, or both. Recipients aren't kept
 * as a per-row delivery log (no other mass-send feature in this app does that either -
 * see Invoices::sendMassReminders()) - just aggregate counts on this row.
 */
class Announcement extends Model
{
    use BelongsToLandlord;

    protected $fillable = [
        'landlord_id',
        'sent_by',
        'location_id',
        'subject',
        'message',
        'send_sms',
        'send_email',
        'recipients_total',
        'sms_sent',
        'sms_failed',
        'email_sent',
        'email_failed',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'send_sms' => 'boolean',
            'send_email' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Resolves recipients the same way as every other tenant-facing bulk action
     * (StaffScope for the security boundary, an optional property filter on top), then
     * fires each channel per tenant with its own try/catch - a gateway failure for one
     * tenant must never abort the rest of the batch, same as sendMassReminders().
     */
    public function send(): void
    {
        $tenants = StaffScope::onTenant(Tenant::query())
            ->when($this->location_id, fn ($q) => $q->whereHas('house', fn ($h) => $h->where('location_id', $this->location_id)))
            ->get();

        $smsSent = 0;
        $smsFailed = 0;
        $emailSent = 0;
        $emailFailed = 0;

        foreach ($tenants as $tenant) {
            if ($this->send_sms && $tenant->phone_number) {
                try {
                    SmsHelper::sendSms($tenant->phone_number, $this->message, $this->landlord_id);
                    $smsSent++;
                } catch (\Throwable $e) {
                    $smsFailed++;
                }
            }

            if ($this->send_email && $tenant->email) {
                try {
                    EmailHelper::send($tenant->email, $this->subject, $this->message, $this->landlord_id);
                    $emailSent++;
                } catch (\Throwable $e) {
                    $emailFailed++;
                }
            }
        }

        $this->update([
            'recipients_total' => $tenants->count(),
            'sms_sent' => $smsSent,
            'sms_failed' => $smsFailed,
            'email_sent' => $emailSent,
            'email_failed' => $emailFailed,
            'sent_at' => now(),
        ]);
    }
}
