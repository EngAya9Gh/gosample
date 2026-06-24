<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanOldApiResponsesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:api-responses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete api_responses records older than 3 months to save database space';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of old api_responses...');

        $threeMonthsAgo = Carbon::now()->subMonths(3);

        // We use chunking by limits or directly a delete statement
        // directly deleting might lock the table if it's huge, but api_responses might not be too huge.
        // To be safe with large tables, it's better to delete in chunks, or run a single fast query.
        $deletedCount = DB::table('api_responses')
            ->where('created_at', '<', $threeMonthsAgo)
            ->delete();

        $this->info("Successfully deleted {$deletedCount} old records from api_responses.");
        \Log::info("✅ [Cleanup] Deleted {$deletedCount} records from api_responses older than {$threeMonthsAgo}.");

        return Command::SUCCESS;
    }
}
