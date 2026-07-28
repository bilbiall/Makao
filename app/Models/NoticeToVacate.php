<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NoticeToVacate extends Model
{
    use HasFactory;

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

    protected static function booted()
    {
        // Notify admins when a tenant submits a notice to vacate
        static::created(function ($notice) {
            $tenant = $notice->tenant;
            $admins = \App\Models\User::where('role', 'admin')->get();
            
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
