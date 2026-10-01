<?php

namespace App\Console\Commands;

use App\Models\ChatTurn;
use Illuminate\Console\Command;

class PruneChatTurns extends Command
{
    protected $signature = 'chat:prune-turns {--days=90 : Delete logged chat messages older than this many days}';

    protected $description = 'Delete old chat assistant message logs (data-retention limit)';

    public function handle(): int
    {
        $deleted = ChatTurn::where('created_at', '<', now()->subDays((int) $this->option('days')))->delete();

        $this->info("Deleted {$deleted} chat message(s).");

        return self::SUCCESS;
    }
}
