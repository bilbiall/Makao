<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\DatabaseNotification;
use Illuminate\Console\Command;

class TestNotifications extends Command
{
    protected $signature = 'notifications:test {--user-id=1 : The ID of the user to send the notification to}';
    protected $description = 'Send a test notification to a user';

    public function handle(): int
    {
        $userId = $this->option('user-id');
        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return 1;
        }

        $user->notify(new DatabaseNotification(
            'Test Notification',
            'This is a test notification to verify the notification system is working properly.',
            null
        ));

        $this->info("Test notification sent to {$user->name} ({$user->email})");
        return 0;
    }
}
