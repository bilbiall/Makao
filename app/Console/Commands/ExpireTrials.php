<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireTrials extends Command
{
    protected $signature = 'app:expire-trials';

    protected $description = 'Flip trialing/active subscriptions whose expiry date has passed to expired';

    public function handle(): int
    {
        $expired = Subscription::whereIn('status', ['trialing', 'active'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $subscription) {
            $subscription->update(['status' => 'expired']);
        }

        $this->info("{$expired->count()} subscription(s) marked as expired.");

        return self::SUCCESS;
    }
}
