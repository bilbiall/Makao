<?php

namespace App\Console\Commands;

use App\Models\DeletedTenant;
use Illuminate\Console\Command;

class DeleteExpiredTenantRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:delete-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired deleted tenant records (older than 60 days)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deleted = DeletedTenant::where('auto_delete_at', '<=', now())
            ->delete();

        $this->info("Deleted $deleted expired tenant records.");

        return Command::SUCCESS;
    }
}
