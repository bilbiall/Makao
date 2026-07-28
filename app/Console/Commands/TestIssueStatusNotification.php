<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Issue;
use App\Models\User;

class TestIssueStatusNotification extends Command
{
    protected $signature = 'issues:test-notification {issue_id}';
    protected $description = 'Test issue status change notification';

    public function handle()
    {
        $issueId = $this->argument('issue_id');
        $issue = Issue::with(['tenant.user'])->find($issueId);

        if (!$issue) {
            $this->error("Issue #{$issueId} not found!");
            return 1;
        }

        $this->info("Testing notification for Issue #{$issue->id}: \"{$issue->title}\"");
        $this->info("Current status: {$issue->status}");
        $this->info("Tenant: {$issue->tenant->tenant_name}");
        
        if ($issue->tenant->user) {
            $this->info("Tenant user: {$issue->tenant->user->email}");
        } else {
            $this->error("❌ Tenant has NO linked user account - notification will fail!");
            return 1;
        }

        $this->newLine();
        $oldStatus = $issue->status;
        $newStatus = match($oldStatus) {
            'open' => 'in_progress',
            'in_progress' => 'resolved',
            'resolved' => 'open',
            default => 'in_progress'
        };
        $this->info("Changing status from '{$oldStatus}' to '{$newStatus}'...");
        
        // Update the status
        $issue->status = $newStatus;
        $issue->save();

        $this->newLine();
        $this->info('✓ Status updated!');
        $this->info('Checking notifications...');
        
        // Check if notification was created
        $tenantNotification = $issue->tenant->user->notifications()->latest()->first();
        $adminNotifications = User::where('role', 'admin')->get()->flatMap(fn($admin) => $admin->notifications()->latest()->first())->filter();
        
        if ($tenantNotification && $tenantNotification->created_at->gt(now()->subSeconds(5))) {
            $this->info("✓ Tenant notification created: \"{$tenantNotification->data['title']}\"");
        } else {
            $this->error('❌ No recent tenant notification found!');
        }

        if ($adminNotifications->isNotEmpty()) {
            $this->info("✓ {$adminNotifications->count()} admin notification(s) created");
        }

        return 0;
    }
}
