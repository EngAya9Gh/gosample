<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up notifications older than 3 months to save DB size';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Calculate the date 3 months ago
        $date = Carbon::now()->subMonths(3);

        // We use DB::table instead of the Eloquent model to bypass events and soft deletes, making it much faster
        $deleted = DB::table('notifications')->where('created_at', '<', $date)->delete();

        $this->info("Successfully deleted {$deleted} old notifications.");
        \Log::info("CleanupNotificationsCommand: Successfully deleted {$deleted} old notifications (older than 3 months).");

        return Command::SUCCESS;
    }
}
