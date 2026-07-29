<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Forensics for a task that has gone missing from the UI.
 *
 * A task can disappear for three quite different reasons, and they have very
 * different recovery stories:
 *
 *   1. is_unused = 1  - the row is intact but hidden by the model's global
 *                       scope. Fully recoverable, samples untouched.
 *   2. hard deleted, audited - Task has no SoftDeletes, so a delete is
 *                       permanent, but audit_logs keeps the whole row. The task
 *                       can be rebuilt; its samples cannot, because
 *                       samples.task_id is ON DELETE CASCADE and Sample is not
 *                       audited.
 *   3. hard deleted, not audited - the audit trail only records deletes made by
 *                       a logged-in user through a model event. Console deletes
 *                       and query-builder mass deletes leave no trace, so
 *                       recovery needs a database backup.
 *
 * Read-only unless "restore" is passed as the second argument.
 */
class TraceTask extends Command
{
    protected $signature = 'tasks:trace
        {id      : The task id to investigate.}
        {mode?   : Pass "restore" to attempt recovery. Omit to only report.}';

    protected $description = 'Find out what happened to a missing task, and recover it if possible';

    public function handle(): int
    {
        $id   = (int) $this->argument('id');
        $mode = strtolower(trim((string) $this->argument('mode')));

        if ($mode !== '' && $mode !== 'restore') {
            $this->error("Unknown mode \"{$mode}\". Pass \"restore\" to recover, or omit it to only report.");

            return self::INVALID;
        }

        $this->line('');
        $this->line("  <options=bold>TASK {$id} - TRACE</>   " . now()->format('Y-m-d H:i'));
        $this->line('  ' . str_repeat('-', 66));

        $row = DB::table('tasks')->where('id', $id)->first();

        $this->reportRow($id, $row);
        $this->reportSamples($id, (bool) $row);
        $audit = $this->reportAudit($id);

        $this->line('');
        $this->line('  ' . str_repeat('-', 66));

        if ($row) {
            return $this->handlePresent($id, $row, $mode);
        }

        return $this->handleMissing($id, $audit, $mode);
    }

    private function reportRow(int $id, $row): void
    {
        $this->line('');
        $this->line('  <fg=cyan>TASK ROW</>  (read directly, ignoring every model scope)');

        if (! $row) {
            $this->line('    row exists in `tasks` .......... <fg=red>NO - the row is gone</>');

            return;
        }

        $hidden = (int) ($row->is_unused ?? 0) === 1;

        $this->line('    row exists in `tasks` .......... <fg=green>YES</>');
        $this->line('    visible in the app ............. ' . ($hidden ? '<fg=red>NO (is_unused = 1)</>' : '<fg=green>YES</>'));
        $this->line('    status ......................... ' . ($row->status ?? '-'));
        $this->line('    driver_id ...................... ' . ($row->driver_id ?? '-'));
        $this->line('    pickup_time .................... ' . ($row->pickup_time ?? '-'));
        $this->line('    created_at ..................... ' . ($row->created_at ?? '-'));
        $this->line('    updated_at ..................... ' . ($row->updated_at ?? '-'));

        if (Schema::hasColumn('tasks', 'scheduled_task_id')) {
            // Not evidence of a manual task on its own: nothing wrote this
            // column before the duplicate-guard release, so every task created
            // before then is null regardless of where it came from.
            $this->line('    scheduled_task_id .............. ' . ($row->scheduled_task_id ?? 'null'));

            if (is_null($row->scheduled_task_id)) {
                $this->line('      (null means manual OR created before this column was populated)');
            }
        }
    }

    private function reportSamples(int $id, bool $taskExists): void
    {
        $this->line('');
        $this->line('  <fg=cyan>SAMPLES</>');

        $samples = DB::table('samples')->where('task_id', $id)->count();
        $this->line("    samples pointing at this task .. {$samples}");

        if ($samples > 0 && ! $taskExists) {
            $this->line('    <fg=yellow>Samples survive but the task row does not - unusual, the foreign key</>');
            $this->line('    <fg=yellow>should have removed them. Worth investigating separately.</>');
        }

        if ($samples > 0 && $taskExists) {
            $this->line('    <fg=green>The samples are intact.</>');
        }
    }

    private function reportAudit(int $id): ?AuditLog
    {
        $this->line('');
        $this->line('  <fg=cyan>AUDIT TRAIL</>');

        $logs = AuditLog::where('subject_id', $id)
            ->where('subject_type', 'like', '%Task%')
            ->orderBy('id')
            ->get();

        if ($logs->isEmpty()) {
            $this->line('    no audit records for this task');
            $this->line('    <fg=yellow>Note: deletes are only audited when made by a logged-in user through</>');
            $this->line('    <fg=yellow>the UI. Console jobs and bulk deletes leave no audit record, so an</>');
            $this->line('    <fg=yellow>absent record does NOT prove the task was never deleted.</>');

            return null;
        }

        $this->table(
            ['when', 'what', 'by user'],
            $logs->map(fn ($l) => [$l->created_at, $l->description, $l->user_id])->all()
        );

        return $logs->firstWhere('description', 'audit:deleted');
    }

    private function handlePresent(int $id, $row, string $mode): int
    {
        if ((int) ($row->is_unused ?? 0) !== 1) {
            $this->line('  <fg=green;options=bold>VERDICT: the task exists and is visible.</>');
            $this->line('  It was never deleted. If the admin cannot see it, the cause is a filter,');
            $this->line('  a permission, or a client/driver restriction on the search screen.');
            $this->line('');

            return self::SUCCESS;
        }

        $this->line('  <fg=yellow;options=bold>VERDICT: the task was NOT deleted - it is hidden.</>');
        $this->line('  is_unused = 1, so the global scope removes it from every query in the app.');
        $this->line('  This flag is set by the RemoveOldNewTasks job, which hides NEW tasks left');
        $this->line('  over from a previous day. The row and its samples are fully intact.');

        if ($mode !== 'restore') {
            $this->line('');
            $this->comment('  To make it visible again:');
            $this->comment("    php artisan tasks:trace {$id} restore");
            $this->line('');

            return self::SUCCESS;
        }

        DB::table('tasks')->where('id', $id)->update(['is_unused' => 0]);
        $this->line('');
        $this->info("  Restored. Task {$id} is visible again (is_unused set to 0).");
        $this->line('');

        return self::SUCCESS;
    }

    private function handleMissing(int $id, ?AuditLog $deleted, string $mode): int
    {
        if (! $deleted) {
            $this->line('  <fg=red;options=bold>VERDICT: the row is gone and there is no delete record.</>');
            $this->line('  It cannot be rebuilt from the audit trail. Recovery needs a database');
            $this->line('  backup from before the deletion.');
            $this->line('');
            $this->line('  Deletes that leave no audit record: console commands, scheduled jobs,');
            $this->line('  and bulk query-builder deletes. Note that its samples are also gone,');
            $this->line('  because samples.task_id cascades and Sample is not audited.');
            $this->line('');

            return self::FAILURE;
        }

        $this->line('  <fg=yellow;options=bold>VERDICT: the task was deleted, and the row was captured.</>');
        $this->line("  Deleted at {$deleted->created_at} by user id {$deleted->user_id}.");
        $this->line('  The task itself can be rebuilt from the audit record.');
        $this->line('  <fg=red>Its samples cannot</> - the foreign key cascade removed them and Sample');
        $this->line('  is not audited.');

        $props = collect($deleted->properties)->toArray();

        if ($mode !== 'restore') {
            $this->line('');
            $this->line('  <fg=cyan>Captured values</>');
            foreach ($props as $k => $v) {
                if (! is_scalar($v) && ! is_null($v)) {
                    continue;
                }
                $this->line('    ' . str_pad((string) $k, 28) . ' ' . var_export($v, true));
            }
            $this->line('');
            $this->comment('  To rebuild the task row:');
            $this->comment("    php artisan tasks:trace {$id} restore");
            $this->line('');

            return self::SUCCESS;
        }

        // Only write columns that actually exist on the table.
        $columns = Schema::getColumnListing('tasks');
        $insert  = array_filter(
            $props,
            fn ($v, $k) => in_array($k, $columns, true) && (is_scalar($v) || is_null($v)),
            ARRAY_FILTER_USE_BOTH
        );
        $insert['id'] = $id;

        DB::table('tasks')->insert($insert);

        $this->line('');
        $this->info("  Task {$id} rebuilt from the audit record (" . count($insert) . ' columns).');
        $this->warn('  Its samples were not recovered; they were cascade-deleted and never audited.');
        $this->line('');

        return self::SUCCESS;
    }
}
