<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Issue extends Model
{
     // Allow these attributes to be mass-assigned
    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'status',
    ];

     /**
     * Get the tenant who reported this issue.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function booted()
    {
        // Notify when new issue is created
        static::created(function ($issue) {
            try {
                $tenant = $issue->tenant;
                
                // Log issue creation
                try {
                    $actor = auth()->id() ?? null;
                    \App\Helpers\ActivityLogger::log('create_issue', $actor, "Issue '{$issue->title}' created by {$tenant->tenant_name} - Status: {$issue->status}");
                } catch (\Throwable $e) {
                    // ignore
                }
                
                // Notify tenant user if exists
                $tenantUser = $tenant->user ?? null;
                if ($tenantUser) {
                    $tenantUser->notify(new \App\Notifications\DatabaseNotification(
                        'Issue Submitted',
                        "Your issue '{$issue->title}' has been submitted and is being reviewed.",
                        null
                    ));
                }

                // Notify admins about new issue
                $admins = \App\Models\User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\DatabaseNotification(
                        'New Issue Reported',
                        "{$tenant->tenant_name} reported: '{$issue->title}' - Status: {$issue->status}",
                        null
                    ));
                }
            } catch (\Throwable $e) {
                // ignore notification errors
            }
        });

        // Notify when issue status changes
        static::updated(function ($issue) {
            if ($issue->wasChanged('status')) {
                try {
                    // Load tenant with user relationship
                    $tenant = $issue->tenant()->with('user')->first();
                    
                    // Log issue status update
                    try {
                        $actor = auth()->id() ?? null;
                        $oldStatus = $issue->getOriginal('status');
                        \App\Helpers\ActivityLogger::log('update_issue', $actor, "Issue '{$issue->title}' status changed from '{$oldStatus}' to '{$issue->status}'");
                    } catch (\Throwable $e) {
                        // ignore
                    }
                    
                    // Notify tenant user if exists
                    if ($tenant && $tenant->user) {
                        try {
                            $tenant->user->notify(new \App\Notifications\DatabaseNotification(
                                'Issue Status Updated',
                                "Your issue '{$issue->title}' status changed to {$issue->status}",
                                null
                            ));
                            \Log::info('Issue notification sent to tenant user: ' . $tenant->user->email);
                        } catch (\Throwable $e) {
                            \Log::error('Failed to notify tenant user ' . $tenant->user->email . ': ' . $e->getMessage());
                        }
                    } else {
                        \Log::warning('Issue #{$issue->id} tenant has no linked user account');
                    }

                    // Notify admins about the status change
                    $admins = \App\Models\User::where('role', 'admin')->get();
                    foreach ($admins as $admin) {
                        try {
                            $admin->notify(new \App\Notifications\DatabaseNotification(
                                'Issue Status Updated',
                                "Issue '{$issue->title}' for {$tenant->tenant_name} status changed to {$issue->status}",
                                null
                            ));
                        } catch (\Throwable $e) {
                            \Log::error('Failed to notify admin ' . $admin->email . ': ' . $e->getMessage());
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::error('Issue notification error: ' . $e->getMessage());
                }
            }
        });
        
        static::deleted(function ($issue) {
            try {
                $actor = auth()->id() ?? null;
                $tenant = $issue->tenant;
                \App\Helpers\ActivityLogger::log('delete_issue', $actor, "Issue '{$issue->title}' deleted (Tenant: {$tenant->tenant_name})");
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
}
