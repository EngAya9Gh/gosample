<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanOldAuditLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:audit-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete audit_logs records older than 6 months to save database space';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of old audit_logs...');

        $sixMonthsAgo = Carbon::now()->subMonths(6);

        $deletedCount = DB::table('audit_logs')
            ->where('created_at', '<', $sixMonthsAgo)
            ->delete();

        $this->info("Successfully deleted {$deletedCount} old records from audit_logs.");
        \Log::info("✅ [Cleanup] Deleted {$deletedCount} records from audit_logs older than {$sixMonthsAgo}.");

        return Command::SUCCESS;
    }
}
