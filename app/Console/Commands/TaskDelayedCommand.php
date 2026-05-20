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
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // 1. Existing Check: Pickup Delay
        $tasks = Task::whereRaw('pickup_time < collection_date')
        ->where(function($q) { $q->whereNull('delayed_reason')->orWhere('delayed_reason', ''); })
        ->get();

        foreach ($tasks as $task) {
            $task->delayed_reason = 'pickup_delayed';  
            $driver = Driver::find($task->driver_id);
            if ($driver) {
                $driver->sendNotification( 'Delayed Task', 'You have delay in pickup task',[$driver->fcm_token],$task,'no_action');
                \Notification::send($driver, new \App\Notifications\TaskDelayed($task));
            }
            $task->save();
        }

        // 2. Existing Check: Drop-off Delay (Scheduled)
        $tasks1   = Task::whereRaw('dropoff_time < close_date')
        ->where(function($q) { $q->whereNull('delayed_reason')->orWhere('delayed_reason', ''); })
        ->get();

        foreach ($tasks1 as $task) {
            $task->delayed_reason = 'dropoff_delayed'; 
            $driver = Driver::find($task->driver_id);
            if ($driver) {
                $driver->sendNotification( 'Delayed Task', 'You have delay in drop off task',[$driver->fcm_token],$task,'no_action');
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
                $driver->sendNotification('تنبيه: تأخر وضع العينات', 'لقد تجاوزت الوقت المسموح (15 دقيقة) لوضع العينات في الحافظة.', [$driver->fcm_token], $task, 'no_action');
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
                $driver->sendNotification('تنبيه: تأخر تسليم العينات', 'لقد تجاوزت الوقت المسموح (15 دقيقة) لتسليم العينات بعد إخراجها.', [$driver->fcm_token], $task, 'no_action');
                \Notification::send($driver, new \App\Notifications\TaskDelayed($task));
            }
            $task->save();
        }

        return Command::SUCCESS;
    }
}
