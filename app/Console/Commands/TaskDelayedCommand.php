<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Car;
use App\Models\Sample;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Task;
use Carbon\Carbon;

class TaskDelayedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taskDelayed:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check delayed tasks';

    /**
     * Checks 1 and 2 only look at tasks collected / closed within this window.
     *
     * An on-time task keeps delayed_reason NULL forever, so without a bound both
     * checks re-read every task ever recorded, twice a minute. This command runs
     * every minute, so a late task is still flagged within a minute of being
     * collected or closed. Same window as the delayed-tasks dashboard (Task model).
     */
    private const RECHECK_WINDOW_DAYS = 4;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $recentSince = Carbon::now()->subDays(self::RECHECK_WINDOW_DAYS);

        // 1. Existing Check: Pickup Delay
        $tasks = Task::whereRaw('pickup_time < collection_date')
        ->where('collection_date', '>=', $recentSince)
        ->where(function($q) { $q->whereNull('delayed_reason')->orWhere('delayed_reason', ''); })
        ->get();

        foreach ($tasks as $task) {
            $task->delayed_reason = 'pickup_delayed';  
            $driver = Driver::find($task->driver_id);
            if ($driver) {
                $driver->pushNotification('pickup_delayed', $task, 'no_action');
                \Notification::send($driver, new \App\Notifications\TaskDelayed($task));
            }
            $task->save();
        }

        // 2. Existing Check: Drop-off Delay (Scheduled)
        $tasks1   = Task::whereRaw('dropoff_time < close_date')
        ->where('close_date', '>=', $recentSince)
        ->where(function($q) { $q->whereNull('delayed_reason')->orWhere('delayed_reason', ''); })
        ->get();

        foreach ($tasks1 as $task) {
            $task->delayed_reason = 'dropoff_delayed'; 
            $driver = Driver::find($task->driver_id);
            if ($driver) {
                $driver->pushNotification('dropoff_delayed', $task, 'no_action');
                \Notification::send($driver, new \App\Notifications\TaskDelayed($task));
            }
            $task->save(); 
        }

        // 3. New Check: Step Delay Monitoring (Step 1 -> Step 2) [15 mins]
        // From COLLECTED to IN_FREEZER
        $tasks_placement = Task::where('status', 'COLLECTED')
            ->whereNotNull('collection_date')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, collection_date, NOW()) > 15')
            ->where(function($q) { $q->whereNull('delayed_reason')->orWhere('delayed_reason', 'NOT LIKE', '%placement_delayed%'); })
            ->get();

        foreach ($tasks_placement as $task) {
            $task->delayed_reason = trim($task->delayed_reason . ',placement_delayed', ',');
            $driver = Driver::find($task->driver_id);
            if ($driver) {
                $driver->pushNotification('placement_delayed', $task, 'no_action');
                \Notification::send($driver, new \App\Notifications\TaskDelayed($task));
            }
            $task->save();
        }

        // 4. New Check: Delivery Delay Monitoring (Step 3 -> Drop-off) [15 mins]
        // From OUT_FREEZER to CLOSED
        $tasks_delivery = Task::where('status', 'OUT_FREEZER')
            ->whereNotNull('freezer_out_date')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, freezer_out_date, NOW()) > 15')
            ->where(function($q) { $q->whereNull('delayed_reason')->orWhere('delayed_reason', 'NOT LIKE', '%delivery_step_delayed%'); })
            ->get();

        foreach ($tasks_delivery as $task) {
            $task->delayed_reason = trim($task->delayed_reason . ',delivery_step_delayed', ',');
            $driver = Driver::find($task->driver_id);
            if ($driver) {
                $driver->pushNotification('delivery_delayed', $task, 'no_action');
                \Notification::send($driver, new \App\Notifications\TaskDelayed($task));
            }
            $task->save();
        }

        return Command::SUCCESS;
    }
}
