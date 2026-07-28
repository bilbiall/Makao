<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActivityLog;

class ShowActivityTypes extends Command
{
    protected $signature = 'logs:activity-types';
    protected $description = 'Show all activity types being logged in the system';

    public function handle()
    {
        $this->info('=== Activity Log Types ===');
        $this->newLine();

        // Get all unique activity types from database
        $dbActivities = ActivityLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->toArray();

        // Expected activities from code
        $expectedActivities = [
            'login' => 'User Login',
            'send_invoice' => 'Invoice Created',
            'update_invoice' => 'Invoice Updated',
            'delete_invoice' => 'Invoice Deleted',
            'record_bill' => 'Bill Recorded',
            'delete_bill' => 'Bill Deleted',
            'record_payment' => 'Payment Recorded',
            'delete_payment' => 'Payment Deleted',
            'mpesa_payment' => 'M-Pesa Payment Received',
            'create_tenant' => 'Tenant Created',
            'update_tenant' => 'Tenant Updated',
            'delete_tenant' => 'Tenant Deleted',
            'create_house' => 'House Created',
            'update_house' => 'House Updated',
            'delete_house' => 'House Deleted',
            'create_location' => 'Location Created',
            'update_location' => 'Location Updated',
            'delete_location' => 'Location Deleted',
            'create_issue' => 'Issue Created',
            'update_issue' => 'Issue Status Updated',
            'delete_issue' => 'Issue Deleted',
            'create_notice' => 'Notice to Vacate Submitted',
            'approve_notice' => 'Notice to Vacate Approved',
            'deny_notice' => 'Notice to Vacate Denied',
            'create_user' => 'User Created',
            'update_user' => 'User Updated',
            'delete_user' => 'User Deleted',
            'auto_invoice' => 'Auto-Generated Invoice',
        ];

        $this->table(
            ['Activity Type', 'Description', 'In Database'],
            collect($expectedActivities)->map(function ($description, $type) use ($dbActivities) {
                return [
                    $type,
                    $description,
                    in_array($type, $dbActivities) ? '✓' : '-'
                ];
            })->toArray()
        );

        $this->newLine();
        $this->info('Total Activity Types: ' . count($expectedActivities));
        $this->info('Currently Logged: ' . count($dbActivities));

        // Show any activities in database not in our list
        $extraActivities = array_diff($dbActivities, array_keys($expectedActivities));
        if (!empty($extraActivities)) {
            $this->newLine();
            $this->warn('Additional activities found in database:');
            foreach ($extraActivities as $activity) {
                $this->line("  - {$activity}");
            }
        }

        $this->newLine();
        $this->info('View logs at: /admin/logs');

        return 0;
    }
}
