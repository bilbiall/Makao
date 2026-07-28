<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Issue;

class CheckIssueNotifications extends Command
{
    protected $signature = 'issues:check-notifications';
    protected $description = 'Check which tenants have user accounts for issue notifications';

    public function handle()
    {
        $this->info('Checking issues and their tenant user linkages...');
        $this->newLine();

        $issues = Issue::with(['tenant.user'])->get();

        if ($issues->isEmpty()) {
            $this->warn('No issues found in the database.');
            return;
        }

        foreach ($issues as $issue) {
            $tenant = $issue->tenant;
            $hasUser = $tenant && $tenant->user;
            
            $this->line(sprintf(
                'Issue #%d: "%s" | Tenant: %s | User: %s',
                $issue->id,
                $issue->title,
                $tenant ? $tenant->tenant_name : 'N/A',
                $hasUser ? "✓ ({$tenant->user->email})" : '✗ NO USER LINKED'
            ));
        }

        $this->newLine();
        $issuesWithoutUsers = $issues->filter(fn($i) => !$i->tenant->user)->count();
        
        if ($issuesWithoutUsers > 0) {
            $this->warn("⚠ {$issuesWithoutUsers} issue(s) belong to tenants without linked users.");
            $this->warn('These tenants will NOT receive notifications.');
            $this->newLine();
            $this->info('To fix: Link tenants to users in the Tenant resource by setting the "User" field.');
        } else {
            $this->info('✓ All tenants have linked user accounts!');
        }
    }
}
