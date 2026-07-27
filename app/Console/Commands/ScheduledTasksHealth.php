<?php

namespace App\Console\Commands;

use App\Models\ScheduledTask;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only health check for scheduled task generation.
 *
 * Written for environments where the only access is Forge's command runner -
 * it answers, without needing a SQL client, whether the schema is complete,
 * whether tasks are actually being generated, and whether any duplicates or
 * legacy damage remain.
 *
 * This command NEVER writes. Use schedules:repair to change anything.
 */
class ScheduledTasksHealth extends Command
{
    protected $signature = 'schedules:health {--days=7 : How many days back to check for duplicates.}';

    protected $description = 'Read-only health report for scheduled task generation';

    private array $problems = [];

    public function handle(): int
    {
        $this->line('');
        $this->line('  <options=bold>SCHEDULED TASKS - HEALTH CHECK</>   ' . now()->format('Y-m-d H:i') . '  (' . now()->format('l') . ')');
        $this->line('  ' . str_repeat('-', 66));

        $schemaOk = $this->schema();

        if (! $schemaOk) {
            $this->line('');
            $this->error('  Schema is incomplete - run: php artisan migrate --force');
            $this->line('  Until then the generator cannot run and NO tasks are being created.');
            $this->line('');

            return self::FAILURE;
        }

        $this->generation();
        $this->duplicates();
        $this->legacyDamage();
        $this->verdict();

        // Deliberately SUCCESS even when there is something to look at. Findings
        // here are informational; only a broken schema (handled above) is a
        // genuine failure. Returning non-zero made control panels and deploy
        // pipelines report the check itself as failed, which is misleading.
        return self::SUCCESS;
    }

    private function row(string $label, string $value, ?bool $ok = null): void
    {
        $dots  = str_repeat('.', max(2, 44 - strlen($label)));
        $mark  = $ok === null ? '' : ($ok ? '  <fg=green>OK</>' : '  <fg=red>!!</>');
        $this->line("  {$label} {$dots} {$value}{$mark}");
    }

    private function schema(): bool
    {
        $this->line('');
        $this->line('  <fg=cyan>SCHEMA</>');

        $checks = [
            'scheduled_tasks.last_generated_at' => Schema::hasColumn('scheduled_tasks', 'last_generated_at'),
            'tasks.scheduled_task_id'           => Schema::hasColumn('tasks', 'scheduled_task_id'),
        ];

        $checks['tasks_scheduled_task_created_idx'] = $checks['tasks.scheduled_task_id']
            && ! empty(DB::select("SHOW INDEX FROM `tasks` WHERE Key_name = 'tasks_scheduled_task_created_idx'"));

        foreach ($checks as $name => $present) {
            $this->row($name, $present ? 'present' : 'MISSING', $present);
        }

        return $checks['scheduled_tasks.last_generated_at'] && $checks['tasks.scheduled_task_id'];
    }

    private function generation(): void
    {
        $this->line('');
        $this->line('  <fg=cyan>GENERATION TODAY</>');

        $today    = now()->toDateString();
        $dayStart = now()->startOfDay();
        $dayEnd   = now()->endOfDay();

        // Date comparisons are written as plain ranges rather than whereDate().
        // whereDate() emits DATE(col) = ?, which wraps the column in a function
        // and makes the index unusable - a full scan of a large tasks table.
        $due = ScheduledTask::query()
            ->leftJoin('scheduled_tasks as p', 'p.id', '=', 'scheduled_tasks.parent_id')
            ->where('scheduled_tasks.status', 'enabled')
            ->where('scheduled_tasks.day', now()->format('l'))
            ->where('scheduled_tasks.start_date', '<=', $today)
            ->where('scheduled_tasks.end_date', '>=', $today)
            ->where(fn ($q) => $q->whereNull('scheduled_tasks.parent_id')
                ->orWhere(fn ($q2) => $q2->whereNotNull('p.id')->whereNull('p.deleted_at')))
            ->count();

        $claimed = ScheduledTask::whereBetween('last_generated_at', [$dayStart, $dayEnd])->count();
        $pending = max(0, $due - $claimed);

        // One pass for both figures instead of two full scans.
        $todayStats = Task::withoutGlobalScopes()
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->selectRaw('COUNT(*) as c, MAX(created_at) as last_at')
            ->first();

        $tasksToday = (int) ($todayStats->c ?? 0);
        $lastTask   = $todayStats->last_at ?? null;

        $this->row('schedules due today', (string) $due);
        $this->row('already generated today', (string) $claimed);
        $this->row('not yet due (hour not reached)', (string) $pending);
        $this->row('tasks created today (all sources)', (string) $tasksToday);
        $this->row('last task created', $lastTask ? \Carbon\Carbon::parse($lastTask)->diffForHumans() : 'none today');

        // If schedules are due, their hour has passed, and nothing was claimed,
        // the generator is not running.
        if ($due > 0 && $claimed === 0) {
            $this->problems[] = 'No schedule has generated today - check that the scheduler cron is running.';
        }
    }

    private function duplicates(): void
    {
        $days = (int) $this->option('days');
        $this->line('');
        $this->line("  <fg=cyan>DUPLICATES (last {$days} days)</>");

        $rows = DB::select(
            "SELECT scheduled_task_id, DATE(created_at) d, COUNT(*) c
               FROM tasks
              WHERE scheduled_task_id IS NOT NULL AND created_at >= ?
              GROUP BY scheduled_task_id, d
             HAVING c > 1
              ORDER BY c DESC LIMIT 20",
            [now()->subDays($days)->startOfDay()]
        );

        $this->row('duplicate (schedule, date) pairs', (string) count($rows), count($rows) === 0);

        if ($rows) {
            $this->table(['scheduled_task_id', 'date', 'tasks'],
                array_map(fn ($r) => [$r->scheduled_task_id, $r->d, $r->c], $rows));
            $this->problems[] = count($rows) . ' duplicate group(s) found. If any were created AFTER the fix was '
                . 'deployed, that is a regression; older ones are pre-existing damage.';
        }
    }

    private function legacyDamage(): void
    {
        $this->line('');
        $this->line('  <fg=cyan>LEGACY DAMAGE</>');

        $orphans = ScheduledTask::query()
            ->leftJoin('scheduled_tasks as p', 'p.id', '=', 'scheduled_tasks.parent_id')
            ->whereNotNull('scheduled_tasks.parent_id')
            ->where(fn ($q) => $q->whereNull('p.id')->orWhereNotNull('p.deleted_at'))
            ->count();

        // Counted with a self-join rather than by walking the families in PHP.
        // ->with('children')->cursor() does not batch its eager loads, so that
        // version issued one query per parent - thousands of round trips on a
        // real dataset, which is what made this command time out.
        // SHARED_FIELDS is a class constant, so interpolating it is not
        // user-controlled. <=> is MySQL's null-safe equality.
        $comparisons = implode(' OR ', array_map(
            fn ($f) => "NOT (c.`{$f}` <=> p.`{$f}`)",
            ScheduledTask::SHARED_FIELDS
        ));

        $drift = (int) DB::selectOne(
            "SELECT COUNT(*) AS n
               FROM scheduled_tasks c
               JOIN scheduled_tasks p ON p.id = c.parent_id
              WHERE c.deleted_at IS NULL
                AND p.deleted_at IS NULL
                AND ({$comparisons})"
        )->n;

        $unlinked = Task::withoutGlobalScopes()
            ->whereNull('scheduled_task_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $this->row('orphaned children (invisible, inert)', (string) $orphans, $orphans === 0);
        $this->row('children drifted from parent', (string) $drift, $drift === 0);
        $this->row('unlinked tasks (last 30 days)', (string) $unlinked);

        if ($orphans > 0) {
            $this->problems[] = "{$orphans} orphaned row(s) remain. They no longer generate tasks, but should be "
                . 'cleaned up: php artisan schedules:repair orphans apply';
        }
        if ($drift > 0) {
            $this->problems[] = "{$drift} drifted child row(s). Review them with the admin BEFORE syncing - "
                . 'syncing can start, stop or redirect schedules: php artisan schedules:repair drift';
        }
    }

    private function verdict(): void
    {
        $this->line('');
        $this->line('  ' . str_repeat('-', 66));

        if (empty($this->problems)) {
            $this->line('  <fg=green;options=bold>HEALTHY</> - generation is running and no duplicates were found.');
            $this->line('');

            return;
        }

        $this->line('  <fg=yellow;options=bold>NEEDS ATTENTION</>');
        foreach ($this->problems as $i => $p) {
            $this->line('    ' . ($i + 1) . '. ' . $p);
        }
        $this->line('');
    }
}
