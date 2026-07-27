<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ScheduledTask;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Materialises a Task from every ScheduledTask that is due today.
 *
 * Duplicate protection is an atomic claim on scheduled_tasks.last_generated_at
 * rather than a lookup for a matching Task. The old approach searched for an
 * existing task by from/to/client/driver/pickup_time - two of which (driver and
 * pickup_time) dispatchers edit routinely - so any edit made the guard blind and
 * the next tick produced a duplicate. Deleting a generated task had the same
 * effect. Claiming the day on the schedule itself keys the check on a fact
 * nobody edits, so edits and deletions no longer regenerate anything.
 */
class CheckScheduledTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $now     = now();
        $today   = $now->toDateString();
        $dayName = $now->format('l');

        foreach ($this->dueSchedules($now, $today, $dayName) as $scheduledTask) {
            try {
                $this->generate($scheduledTask, $today);
            } catch (Throwable $e) {
                // One bad schedule must not stop the rest of the run.
                Log::error('CheckScheduledTasks: failed to generate task', [
                    'scheduled_task_id' => $scheduledTask->id,
                    'error'             => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Schedules that fall due today and have not been generated yet.
     *
     * The last_generated_at filter here is only an optimisation - the binding
     * decision is made by the atomic claim in generate().
     */
    private function dueSchedules($now, string $today, string $dayName)
    {
        return ScheduledTask::query()
            ->select('scheduled_tasks.*')
            // Orphan guard: children whose parent has been deleted are dead rows
            // that no screen in the app can reach. Never generate from them.
            ->leftJoin('scheduled_tasks as parent_row', 'parent_row.id', '=', 'scheduled_tasks.parent_id')
            ->where('scheduled_tasks.status', 'enabled')
            ->where('scheduled_tasks.day', $dayName)
            ->whereDate('scheduled_tasks.start_date', '<=', $today)
            ->whereDate('scheduled_tasks.end_date', '>=', $today)
            ->whereNotNull('scheduled_tasks.selected_hour')
            // Unchanged from the previous implementation on purpose: the timing
            // bugs (hour-only comparison, unbounded catch-up) are a separate fix.
            ->whereRaw('HOUR(scheduled_tasks.selected_hour) <= ?', [$now->format('H')])
            ->where(function ($q) {
                $q->whereNull('scheduled_tasks.parent_id')
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('parent_row.id')->whereNull('parent_row.deleted_at');
                    });
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('scheduled_tasks.last_generated_at')
                    ->orWhereDate('scheduled_tasks.last_generated_at', '<', $today);
            })
            ->get();
    }

    private function generate(ScheduledTask $scheduledTask, string $today): void
    {
        DB::transaction(function () use ($scheduledTask, $today) {
            // Atomic claim. A single conditional UPDATE is row-locked by InnoDB,
            // so two overlapping cron runs cannot both win the same day.
            $claimed = DB::update(
                'UPDATE scheduled_tasks
                    SET last_generated_at = ?
                  WHERE id = ?
                    AND deleted_at IS NULL
                    AND (last_generated_at IS NULL OR DATE(last_generated_at) < ?)',
                [now(), $scheduledTask->id, $today]
            );

            if ($claimed === 0) {
                return; // already generated today
            }

            // Deploy safety net, first run only.
            //
            // Before this class existed, last_generated_at was never written, so
            // on the very first tick after deploying every schedule looks like it
            // has never run - including the ones whose task the OLD job already
            // created earlier today. Creating again would duplicate the entire
            // day. When the column is still null we therefore fall back to the
            // legacy signature check once, and adopt the existing task instead of
            // making a new one. From the second day on last_generated_at is
            // always set, so this branch never runs again.
            if (is_null($scheduledTask->last_generated_at)) {
                $existing = $this->legacyTaskForToday($scheduledTask, $today);

                if ($existing) {
                    // Heal the link while we are here, so the task becomes
                    // traceable without waiting for schedules:repair.
                    $existing->forceFill(['scheduled_task_id' => $scheduledTask->id])->saveQuietly();

                    Log::info('CheckScheduledTasks: adopted a task created before the fix was deployed', [
                        'scheduled_task_id' => $scheduledTask->id,
                        'task_id'           => $existing->id,
                    ]);

                    return;
                }
            }

            // Inside the transaction: if this throws, the claim rolls back with
            // it and the schedule is retried on the next tick.
            Task::create([
                'from_location'     => $scheduledTask->from_location_id,
                'to_location'       => $scheduledTask->to_location_id,
                'billing_client'    => $scheduledTask->client_id,
                'driver_id'         => $scheduledTask->driver_id,
                'task_type'         => $scheduledTask->task_type,
                'pickup_time'       => $today . ' ' . $scheduledTask->selected_hour,
                'scheduled_task_id' => $scheduledTask->id,
            ]);
        });
    }

    /**
     * The pre-fix duplicate check, used only as a one-time deploy safety net.
     *
     * Driver is deliberately left out of the match: a dispatcher may already
     * have reassigned today's task, and suppressing one task for a single day
     * is far cheaper than duplicating the whole day's work.
     */
    private function legacyTaskForToday(ScheduledTask $scheduledTask, string $today): ?Task
    {
        return Task::withoutGlobalScopes()
            ->whereNull('scheduled_task_id')
            ->where('from_location', $scheduledTask->from_location_id)
            ->where('to_location', $scheduledTask->to_location_id)
            ->where('billing_client', $scheduledTask->client_id)
            ->whereDate('created_at', $today)
            ->first();
    }
}
