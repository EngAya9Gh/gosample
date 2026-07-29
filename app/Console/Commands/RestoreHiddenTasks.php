<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Finds and recovers tasks that were hidden despite holding collected samples.
 *
 * RemoveOldNewTasks used to flag any leftover NEW task as unused, which set
 * is_unused = 1 and removed it from every query in the application. Because a
 * driver scans barcodes while a task is still NEW, tasks holding real work were
 * hidden too - their samples stayed listed while every task-derived column went
 * blank. The job no longer does this, but rows hidden before that fix are still
 * invisible and need bringing back.
 *
 * Read-only unless "restore" is passed.
 */
class RestoreHiddenTasks extends Command
{
    protected $signature = 'tasks:hidden
        {mode? : Pass "restore" to un-hide tasks that hold samples. Omit to only report.}
        {--all : Also restore hidden tasks that hold NO samples. Rarely wanted.}';

    protected $description = 'Report, and optionally restore, tasks hidden by is_unused that still hold samples';

    public function handle(): int
    {
        $mode = strtolower(trim((string) $this->argument('mode')));

        if ($mode !== '' && $mode !== 'restore') {
            $this->error("Unknown mode \"{$mode}\". Pass \"restore\" to recover, or omit it to only report.");

            return self::INVALID;
        }

        $this->line('');
        $this->line('  <options=bold>HIDDEN TASKS</>   ' . now()->format('Y-m-d H:i'));
        $this->line('  ' . str_repeat('-', 66));

        $hidden = DB::table('tasks')->where('is_unused', 1)->count();

        // Hidden tasks that hold samples - real work that vanished from the UI.
        $withSamples = DB::table('tasks')
            ->join('samples', 'samples.task_id', '=', 'tasks.id')
            ->where('tasks.is_unused', 1)
            ->distinct()
            ->count('tasks.id');

        $strandedSamples = DB::table('samples')
            ->join('tasks', 'tasks.id', '=', 'samples.task_id')
            ->where('tasks.is_unused', 1)
            ->count();

        $this->line('');
        $this->line('    hidden tasks (is_unused = 1) ......... ' . $hidden);
        $this->line('    ...of those, holding samples ......... ' . $withSamples
            . ($withSamples > 0 ? '  <fg=red><-- real work, invisible</>' : '  <fg=green>none</>'));
        $this->line('    samples stranded on them ............. ' . $strandedSamples);

        if ($withSamples > 0) {
            $rows = DB::table('tasks')
                ->join('samples', 'samples.task_id', '=', 'tasks.id')
                ->where('tasks.is_unused', 1)
                ->groupBy('tasks.id', 'tasks.status', 'tasks.driver_id', 'tasks.created_at')
                ->orderByDesc(DB::raw('COUNT(samples.id)'))
                ->limit(25)
                ->get([
                    'tasks.id', 'tasks.status', 'tasks.driver_id', 'tasks.created_at',
                    DB::raw('COUNT(samples.id) as sample_count'),
                ]);

            $this->line('');
            $this->table(
                ['task id', 'status', 'driver', 'created', 'samples'],
                $rows->map(fn ($r) => [$r->id, $r->status, $r->driver_id, $r->created_at, $r->sample_count])->all()
            );

            if ($withSamples > 25) {
                $this->line('  ... and ' . ($withSamples - 25) . ' more');
            }
        }

        $this->line('');
        $this->line('  ' . str_repeat('-', 66));

        if ($withSamples === 0 && ! $this->option('all')) {
            $this->info('  Nothing to recover - no hidden task is holding samples.');
            $this->line('');

            return self::SUCCESS;
        }

        if ($mode !== 'restore') {
            $this->comment('  Nothing was written. To bring these back:');
            $this->comment('    php artisan tasks:hidden restore');
            $this->line('');

            return self::SUCCESS;
        }

        $restored = $this->option('all')
            ? DB::table('tasks')->where('is_unused', 1)->update(['is_unused' => 0])
            : DB::table('tasks')
                ->whereIn('id', function ($q) {
                    $q->select('task_id')->from('samples')->whereNotNull('task_id');
                })
                ->where('is_unused', 1)
                ->update(['is_unused' => 0]);

        $this->info("  Restored {$restored} task(s); they are visible in the app again.");
        $this->line('  Their samples were never touched, so nothing else needs repairing.');
        $this->line('');

        return self::SUCCESS;
    }
}
