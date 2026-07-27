<?php

namespace App\Console\Commands;

use App\Models\ScheduledTask;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Repairs the damage left by two long-standing bugs:
 *
 *  1. Deleting a schedule only deleted the parent row, leaving its children
 *     alive, unreachable from every screen, and still generating tasks.
 *  2. Editing a schedule only updated the parent row, leaving children pointing
 *     at the previous driver / client / dates.
 *
 * Dry run by default. Nothing is written without --force, and every destructive
 * step writes a JSON backup of the affected rows first.
 */
class RepairScheduledTasks extends Command
{
    protected $signature = 'schedules:repair
        {--dry-run          : Report only, change nothing. This is the default; the flag exists so it can be stated explicitly.}
        {--force            : Apply changes. Without this the command only reports.}
        {--days=30          : How far back to backfill tasks.scheduled_task_id.}
        {--delete-phantoms  : Also delete future NEW tasks left behind by orphaned schedules. Opt-in: tasks cascade-delete their samples.}
        {--only=            : Comma separated subset of: orphans,drift,backfill,seed,phantoms,report}';

    protected $description = 'Repair orphaned/drifted scheduled tasks and backfill task<->schedule links';

    private array $backup = [];

    public function handle(): int
    {
        // --dry-run always wins over --force, so an operator who passes both by
        // accident gets the safe outcome.
        $apply = $this->option('force') && ! $this->option('dry-run');

        if ($this->option('force') && $this->option('dry-run')) {
            $this->warn('Both --force and --dry-run given; --dry-run wins, nothing will be written.');
        }
        $steps = $this->option('only')
            ? array_map('trim', explode(',', $this->option('only')))
            : ['orphans', 'drift', 'backfill', 'seed', 'phantoms', 'report'];

        $this->line('');
        $this->info($apply ? '*** APPLYING CHANGES ***' : '--- DRY RUN (no changes will be written) ---');
        $this->line('');

        if (in_array('orphans', $steps))  $this->stepOrphans($apply);
        if (in_array('drift', $steps))    $this->stepDrift($apply);
        if (in_array('backfill', $steps)) $this->stepBackfill($apply);
        if (in_array('seed', $steps))     $this->stepSeed($apply);
        if (in_array('phantoms', $steps)) $this->stepPhantoms($apply);
        if (in_array('report', $steps))   $this->stepReport();

        if ($apply && $this->backup) {
            $path = 'schedule-repair/' . now()->format('Ymd_His') . '.json';
            Storage::put($path, json_encode($this->backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->line('');
            $this->info('Backup of every modified row written to: storage/app/' . $path);
        }

        if (! $apply) {
            $this->line('');
            $this->comment('Re-run with --force to apply.');
        }

        return self::SUCCESS;
    }

    /** Children whose parent has been soft-deleted or no longer exists. */
    private function stepOrphans(bool $apply): void
    {
        $this->components->twoColumnDetail('<fg=cyan>STEP 1</>', 'Orphaned children');

        $orphans = ScheduledTask::query()
            ->select('scheduled_tasks.*')
            ->leftJoin('scheduled_tasks as p', 'p.id', '=', 'scheduled_tasks.parent_id')
            ->whereNotNull('scheduled_tasks.parent_id')
            ->where(fn ($q) => $q->whereNull('p.id')->orWhereNotNull('p.deleted_at'))
            ->with('driver')
            ->get();

        if ($orphans->isEmpty()) {
            $this->line('  none found');
            return;
        }

        $this->line("  <fg=yellow>{$orphans->count()}</> orphaned rows still generating tasks:");
        $this->table(
            ['id', 'parent_id', 'driver', 'day', 'hour', 'status', 'end_date'],
            $orphans->take(25)->map(fn ($o) => [
                $o->id, $o->parent_id, optional($o->driver)->name ?? $o->driver_id,
                $o->day, $o->selected_hour, $o->status, $o->end_date,
            ])->all()
        );
        if ($orphans->count() > 25) {
            $this->line('  ... and ' . ($orphans->count() - 25) . ' more');
        }

        if ($apply) {
            $this->backup['orphans'] = $orphans->toArray();
            ScheduledTask::whereIn('id', $orphans->pluck('id'))->delete();
            $this->line("  <fg=green>soft-deleted {$orphans->count()} rows</>");
        }
    }

    /** Children whose shared fields no longer match their parent. */
    private function stepDrift(bool $apply): void
    {
        $this->line('');
        $this->components->twoColumnDetail('<fg=cyan>STEP 2</>', 'Children drifted from parent');

        $parents = ScheduledTask::whereNull('parent_id')->with('children')->get();
        $drifted = [];

        foreach ($parents as $parent) {
            foreach ($parent->children as $child) {
                $diff = [];
                foreach (ScheduledTask::SHARED_FIELDS as $field) {
                    if ((string) $child->{$field} !== (string) $parent->{$field}) {
                        $diff[$field] = [$child->{$field}, $parent->{$field}];
                    }
                }
                if ($diff) {
                    $drifted[] = ['child' => $child, 'parent' => $parent, 'diff' => $diff];
                }
            }
        }

        if (! $drifted) {
            $this->line('  none found');
            return;
        }

        $this->line('  <fg=yellow>' . count($drifted) . '</> children disagree with their parent:');
        $rows = [];
        foreach (array_slice($drifted, 0, 25) as $d) {
            foreach ($d['diff'] as $field => [$was, $shouldBe]) {
                $rows[] = [$d['child']->id, $d['parent']->id, $field, $was, $shouldBe];
            }
        }
        $this->table(['child id', 'parent id', 'field', 'child has', 'parent has'], $rows);

        if ($apply) {
            $this->backup['drift'] = array_map(fn ($d) => [
                'child_id' => $d['child']->id, 'before' => $d['child']->toArray(), 'diff' => $d['diff'],
            ], $drifted);

            foreach ($drifted as $d) {
                $shared = collect(ScheduledTask::SHARED_FIELDS)
                    ->mapWithKeys(fn ($f) => [$f => $d['parent']->{$f}])
                    ->all();
                $d['child']->update($shared);
            }
            $this->line('  <fg=green>synced ' . count($drifted) . ' children to their parent</>');
        }
    }

    /**
     * Link existing tasks back to the schedule that generated them.
     *
     * Tier 1 matches on every field including driver. Tier 2 drops the driver so
     * that tasks reassigned by a dispatcher are still matched. Anything matching
     * more than one schedule is left alone and reported.
     */
    private function stepBackfill(bool $apply): void
    {
        $this->line('');
        $this->components->twoColumnDetail('<fg=cyan>STEP 3</>', 'Backfill tasks.scheduled_task_id');

        $since = now()->subDays((int) $this->option('days'))->startOfDay();

        // scheduled_tasks is small - index it in memory and stream the tasks.
        $schedules = ScheduledTask::withTrashed()->get();
        $byExact = [];
        $byRoute = [];
        foreach ($schedules as $s) {
            if (! $s->selected_hour || ! $s->day) {
                continue;
            }
            $hour = substr((string) $s->selected_hour, 0, 8);
            $byExact["{$s->from_location_id}|{$s->to_location_id}|{$s->client_id}|{$s->driver_id}|{$s->day}|{$hour}"][] = $s;
            $byRoute["{$s->from_location_id}|{$s->to_location_id}|{$s->client_id}|{$s->day}|{$hour}"][] = $s;
        }

        $matched = 0; $ambiguous = 0; $unmatched = 0; $updates = [];

        Task::withoutGlobalScopes()
            ->whereNull('scheduled_task_id')
            ->whereNotNull('pickup_time')
            ->where('created_at', '>=', $since)
            ->orderBy('id')
            ->chunk(1000, function ($tasks) use ($byExact, $byRoute, &$matched, &$ambiguous, &$unmatched, &$updates) {
                foreach ($tasks as $t) {
                    $pickup = Carbon::parse($t->pickup_time);
                    $day    = $pickup->format('l');
                    $hour   = $pickup->format('H:i:s');
                    $date   = $pickup->toDateString();

                    $k1 = "{$t->from_location}|{$t->to_location}|{$t->billing_client}|{$t->driver_id}|{$day}|{$hour}";
                    $k2 = "{$t->from_location}|{$t->to_location}|{$t->billing_client}|{$day}|{$hour}";

                    $candidates = $this->inDateRange($byExact[$k1] ?? [], $date);
                    if (count($candidates) !== 1) {
                        $candidates = $this->inDateRange($byRoute[$k2] ?? [], $date);
                    }

                    if (count($candidates) === 1) {
                        $updates[$candidates[0]->id][] = $t->id;
                        $matched++;
                    } elseif (count($candidates) > 1) {
                        $ambiguous++;
                    } else {
                        $unmatched++;
                    }
                }
            });

        $this->line("  matched: <fg=green>{$matched}</>   ambiguous (left null): <fg=yellow>{$ambiguous}</>   unmatched: {$unmatched}");

        if ($apply && $updates) {
            foreach ($updates as $scheduleId => $taskIds) {
                foreach (array_chunk($taskIds, 1000) as $chunk) {
                    Task::withoutGlobalScopes()->whereIn('id', $chunk)->update(['scheduled_task_id' => $scheduleId]);
                }
            }
            $this->line("  <fg=green>linked {$matched} tasks</>");
        }
    }

    /** @return ScheduledTask[] */
    private function inDateRange(array $candidates, string $date): array
    {
        return array_values(array_filter($candidates, fn ($s) =>
            (! $s->start_date || $date >= Carbon::parse($s->start_date)->toDateString()) &&
            (! $s->end_date   || $date <= Carbon::parse($s->end_date)->toDateString())
        ));
    }

    /**
     * Stamp last_generated_at for schedules that already produced a task today.
     *
     * Deployment-critical: without this the first tick after deploy sees
     * last_generated_at IS NULL everywhere and regenerates the entire day.
     */
    private function stepSeed(bool $apply): void
    {
        $this->line('');
        $this->components->twoColumnDetail('<fg=cyan>STEP 4</>', 'Seed last_generated_at (deploy safety)');

        $sql = "SELECT t.scheduled_task_id AS id, MAX(t.created_at) AS mx
                  FROM tasks t
                  JOIN scheduled_tasks s ON s.id = t.scheduled_task_id
                 WHERE t.scheduled_task_id IS NOT NULL
                   AND DATE(t.created_at) = CURDATE()
                   AND (s.last_generated_at IS NULL OR DATE(s.last_generated_at) < CURDATE())
                 GROUP BY t.scheduled_task_id";

        $rows = DB::select($sql);
        $this->line('  schedules that already ran today and need stamping: <fg=yellow>' . count($rows) . '</>');

        $orphanTasksToday = Task::withoutGlobalScopes()
            ->whereNull('scheduled_task_id')
            ->whereDate('created_at', today())
            ->count();
        if ($orphanTasksToday > 0) {
            $this->warn("  {$orphanTasksToday} task(s) created today could not be linked to a schedule.");
            $this->warn('  If any of those came from a schedule, it may generate once more after deploy.');
        }

        if ($apply && $rows) {
            foreach ($rows as $r) {
                DB::update('UPDATE scheduled_tasks SET last_generated_at = ? WHERE id = ?', [$r->mx, $r->id]);
            }
            $this->line('  <fg=green>stamped ' . count($rows) . ' schedules</>');
        }
    }

    /** Future NEW tasks left behind by schedules that are gone. */
    private function stepPhantoms(bool $apply): void
    {
        $this->line('');
        $this->components->twoColumnDetail('<fg=cyan>STEP 5</>', 'Phantom future tasks');

        $deadScheduleIds = ScheduledTask::onlyTrashed()->pluck('id');

        $phantoms = Task::withoutGlobalScopes()
            ->whereIn('scheduled_task_id', $deadScheduleIds)
            ->where('status', 'NEW')
            ->whereDate('pickup_time', '>=', Carbon::tomorrow())
            ->whereDoesntHave('samples')   // samples cascade-delete with the task
            ->get();

        if ($phantoms->isEmpty()) {
            $this->line('  none found');
            return;
        }

        $this->line("  <fg=yellow>{$phantoms->count()}</> future NEW tasks belong to deleted schedules (none have samples)");

        if (! $this->option('delete-phantoms')) {
            $this->comment('  not deleting - pass --delete-phantoms to remove them');
            return;
        }

        if ($apply) {
            $this->backup['phantoms'] = $phantoms->toArray();
            Task::withoutGlobalScopes()->whereIn('id', $phantoms->pluck('id'))->delete();
            $this->line("  <fg=green>deleted {$phantoms->count()} tasks</>");
        }
    }

    /** Report only - never deletes. */
    private function stepReport(): void
    {
        $this->line('');
        $this->components->twoColumnDetail('<fg=cyan>STEP 6</>', 'Existing duplicates (report only)');

        $dupes = DB::select(
            "SELECT scheduled_task_id, DATE(pickup_time) d, COUNT(*) c
               FROM tasks
              WHERE scheduled_task_id IS NOT NULL
                AND created_at >= ?
              GROUP BY scheduled_task_id, d
             HAVING c > 1
              ORDER BY c DESC
              LIMIT 25",
            [now()->subDays((int) $this->option('days'))->startOfDay()]
        );

        if (! $dupes) {
            $this->line('  none found');
            return;
        }

        $this->table(['scheduled_task_id', 'date', 'task count'],
            array_map(fn ($r) => [$r->scheduled_task_id, $r->d, $r->c], $dupes));
        $this->comment('  Not deleted automatically - these may have samples or history attached.');
    }
}
