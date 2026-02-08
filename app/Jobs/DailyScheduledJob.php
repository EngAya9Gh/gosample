<?php

namespace App\Jobs;

use App\Models\ScheduledTask;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckScheduledTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        
        if (! Cache::add('check_scheduled_tasks_lock', true, 55)) {
            return;
        }

        try {

            $now = now();
            
            $scheduledTasks = ScheduledTask::where('status', 'enabled')
                ->where('execution_status', 'pending')
                ->whereNull('executed_at')
                ->where('day', $now->format('l'))
                ->get();

            foreach ($scheduledTasks as $scheduledTask) {

                $scheduledAt = Carbon::parse(
                    $now->format('Y-m-d') . ' ' . $scheduledTask->selected_hour
                );

                $scheduledTask->update([
                    'last_checked_at' => now(),
                ]);
                
                if ($scheduledAt->diffInMinutes($now, false) < -1) {
                    continue;
                }

                if ($scheduledAt->diffInMinutes($now, false) > 2) {
                    $scheduledTask->update([
                        'execution_status' => 'skipped_late',
                    ]);
                    continue;
                }
                
                $existingTask = Task::where('from_location', $scheduledTask->from_location_id)
                    ->where('to_location', $scheduledTask->to_location_id)
                    ->where('driver_id', $scheduledTask->driver_id)
                    ->where('billing_client', $scheduledTask->client_id)
                    ->where('pickup_time', $scheduledAt)
                    ->first();

                if ($existingTask) {
                    $scheduledTask->update([
                        'executed_at'      => now(),
                        'execution_status' => 'executed',
                    ]);
                    continue;
                }

                DB::transaction(function () use ($scheduledTask, $scheduledAt) {

                    Task::create([
                        'from_location'  => $scheduledTask->from_location_id,
                        'to_location'    => $scheduledTask->to_location_id,
                        'billing_client' => $scheduledTask->client_id,
                        'driver_id'      => $scheduledTask->driver_id,
                        'task_type'      => $scheduledTask->task_type,
                        'pickup_time'    => $scheduledAt,
                    ]);

                    $scheduledTask->update([
                        'executed_at'      => now(),
                        'execution_status' => 'executed',
                    ]);
                });
            }

        } catch (Throwable $e) {

            ScheduledTask::where('execution_status', 'pending')
                ->whereNull('executed_at')
                ->update([
                    'execution_status' => 'skipped_error',
                ]);

            throw $e;

        } finally {
            Cache::forget('check_scheduled_tasks_lock');
        }
    }
}
