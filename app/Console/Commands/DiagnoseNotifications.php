<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Issue;

class DiagnoseNotifications extends Command
{
    protected $signature = 'notifications:diagnose {user_id?}';
    protected $description = 'Diagnose notification system for a user';

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        if (!$userId) {
            $this->info('Available users with tenant role:');
            $tenantUsers = User::whereHas('tenant')->get();
            
            if ($tenantUsers->isEmpty()) {
                $this->error('No tenant users found!');
                return 1;
            }
            
            foreach ($tenantUsers as $user) {
                $this->line("ID: {$user->id} | Email: {$user->email} | Name: {$user->name} | Tenant: {$user->tenant->tenant_name}");
            }
            
            $this->newLine();
            $this->info('Usage: php artisan notifications:diagnose {user_id}');
            return 0;
        }

        $user = User::with('tenant')->find($userId);
        
        if (!$user) {
            $this->error("User #{$userId} not found!");
            return 1;
        }

        $this->info("=== Notification Diagnostic for User #{$user->id} ===");
        $this->line("Email: {$user->email}");
        $this->line("Name: {$user->name}");
        $this->line("Role: {$user->role}");
        
        if ($user->tenant) {
            $this->line("Tenant: {$user->tenant->tenant_name}");
        } else {
            $this->warn("⚠ User has NO linked tenant");
        }
        
        $this->newLine();
        
        // Check notifications table
        $allNotifications = $user->notifications()->get();
        $unreadCount = $user->unreadNotifications()->count();
        $readCount = $user->notifications()->whereNotNull('read_at')->count();
        
        $this->info("Total Notifications: {$allNotifications->count()}");
        $this->info("Unread: {$unreadCount}");
        $this->info("Read: {$readCount}");
        
        $this->newLine();
        
        if ($allNotifications->isNotEmpty()) {
            $this->info("Recent notifications:");
            $recent = $user->notifications()->latest()->limit(5)->get();
            
            foreach ($recent as $notification) {
                $data = $notification->data;
                $status = $notification->read_at ? '✓ Read' : '✗ Unread';
                $this->line("[{$status}] {$data['title']} - {$notification->created_at->diffForHumans()}");
            }
        } else {
            $this->warn("No notifications found for this user.");
            $this->newLine();
            $this->info("Creating a test notification...");
            
            $user->notify(new \App\Notifications\DatabaseNotification(
                'Test Notification',
                'This is a test notification created by the diagnostic tool at ' . now()->format('H:i:s'),
                null
            ));
            
            $this->info("✓ Test notification created!");
            $this->line("Check the notification bell in the user's panel at /tenant");
        }
        
        $this->newLine();
        $this->info("=== Panel Access ===");
        $this->line("Tenant Panel: " . url('/tenant'));
        $this->line("Login with: {$user->email}");
        
        return 0;
    }
}
