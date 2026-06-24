<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Task;
use Carbon\Carbon;
use App\Models\AuditLog;

class CleanOldDeletedTasksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:deleted-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hard delete tasks that have been soft-deleted for more than 6 months, along with their related records.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of old soft-deleted tasks...');

        $sixMonthsAgo = Carbon::now()->subMonths(6);

        // We use query builder directly to optimize performance and memory
        $query = DB::table('tasks')
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '<', $sixMonthsAgo);

        $totalToDelete = $query->count();

        if ($totalToDelete === 0) {
            $this->info('No old soft-deleted tasks found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$totalToDelete} tasks to hard-delete. Processing in chunks...");

        $deletedTasksCount = 0;
        $deletedSamplesCount = 0;

        $query->orderBy('id')->chunk(500, function ($tasks) use (&$deletedTasksCount, &$deletedSamplesCount) {
            $taskIds = $tasks->pluck('id')->toArray();

            // 1. Delete related samples
            $samplesDeleted = DB::table('samples')->whereIn('task_id', $taskIds)->delete();
            $deletedSamplesCount += $samplesDeleted;

            // 2. Delete related car_tracking if any (optional but good for space)
            DB::table('car_tracking')->whereIn('task_id', $taskIds)->delete();

            // 3. Hard delete the tasks
            $tasksDeleted = DB::table('tasks')->whereIn('id', $taskIds)->delete();
            $deletedTasksCount += $tasksDeleted;
        });

        $this->info("Successfully hard-deleted {$deletedTasksCount} tasks and {$deletedSamplesCount} samples.");
        
        $this->info('Optimizing table `tasks` and `samples` to reclaim disk space (this might take a minute)...');
        
        // 5. Optimize tables to immediately reclaim space on the disk and rebuild indexes
        DB::statement('OPTIMIZE TABLE tasks');
        DB::statement('OPTIMIZE TABLE samples');

        $this->info('Cleanup and Optimization complete!');
        \Log::info("✅ [Cleanup] Hard-deleted {$deletedTasksCount} tasks and {$deletedSamplesCount} samples. Tables optimized.");

        return Command::SUCCESS;
    }
}
