<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use App\Models\Task;
use App\Models\ScheduledTask;
use App\Jobs\RemoveOldNewTasks;  // Import the RemoveOldNewTasks job class
use Carbon\Carbon;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
// use App\Console\TaskDelayedCommand;
use App\Jobs\GenerateAtenatiTokenJob;
use App\Jobs\CheckScheduledTasks;
use App\Jobs\DailyScheduledJob;
//use App\Console\Commands\CarTrackCommand;
class Kernel extends ConsoleKernel
{

    // protected $commands = [
    //     TaskDelayedCommand::class,
    // ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {


        // $schedule->command('inspire')->hourly();
        $schedule->command('taskDelayed:cron')->everyMinute();
        $schedule->command('car-track:cron')->everyMinute(); // every minutes
        // $schedule->command('afaqi:cron')->hourly();
        $schedule->command('cleanup:api-responses')->weeklyOn(0, '02:00'); // Run every Sunday at 2 AM
        // $schedule->command('cleanup:audit-logs')->weeklyOn(0, '02:30'); // Run every Sunday at 2:30 AM
        $schedule->command('cleanup:deleted-tasks')->dailyAt('03:00');//weeklyOn(0, '03:00'); // Run every Sunday at 3:00 AM
        // $schedule->command('task:cron') ->everyTwoMinutes();
        $schedule->command('daily-schedule:cron') ->everyTwoMinutes();
        $schedule->command('attendance:check-late')->everyMinute();
        // Auto-derive driver check-in/check-out from task collection & drop-off activity.
        $schedule->command('attendance:calc-auto')->everyFiveMinutes()->withoutOverlapping();
        // $schedule->job(new DailyScheduledJob)->dailyAt('20:00'); // Schedule the job to run daily at 8:00 PM

        $schedule->command('notifications:cleanup')->weeklyOn(0, '02:00');

        // $schedule->call(function () {
        //     $scheduledTasks = ScheduledTask::where('status', 'enabled')->get();


        //     foreach ($scheduledTasks as $scheduledTask) {
        //         if ($scheduledTask->isDue()) {
        //             // Check if a task with the same properties already exists
        //             $existingTask = Task::where('from_location', $scheduledTask->from_location_id)
        //                                 ->where('to_location', $scheduledTask->to_location_id)
        //                                 ->where('driver_id', $scheduledTask->driver_id)
        //                                 ->where('billing_client', $scheduledTask->client_id)
        //                                 ->whereBetween('created_at', [\Carbon\Carbon::today()->startOfDay(), \Carbon\Carbon::today()->endOfDay()])
        //                                 ->first();

        //             if (!$existingTask) {
        //                 // Create a new task
        //                 \Log::info("create new task..");
        //                 $task = new Task;
        //                 $task->from_location = $scheduledTask->from_location_id;
        //                 $task->to_location = $scheduledTask->to_location_id;
        //                 $task->billing_client = $scheduledTask->client_id;
        //                 $task->driver_id = $scheduledTask->driver_id;
        //                 $task->task_type = $scheduledTask->task_type;
        //                 // Set other task properties based on the ScheduledTask
        //                 $task->save();
        //             }
        //             else{
        //                 \Log::info("task is created before");
        //             }
        //         } else{
        //             \Log::info("not created...");
        //         }
        //     }

        // })->dailyAt('23:00');
        $schedule->job(new RemoveOldNewTasks)->everyMinute();
        // $schedule->job(new GenerateAtenatiTokenJob)->everyThirtyMinutes();
        $schedule->job(new CheckScheduledTasks)->everyMinute();
        //$schedule->job(new DailyScheduledJob)->everyMinute(); 
        // $schedule->job(new DailyScheduledJob)->dailyAt('20:00'); 
        //	$schedule->job(new CarTrackCommand)->everyMinute();

        // $schedule->job(new GenerateAtenatiTokenJob)->everyTwoHours();


    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
