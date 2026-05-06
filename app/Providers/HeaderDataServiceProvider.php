<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Task;
use App\Models\Sample;
use App\Models\Swap;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class HeaderDataServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('layouts.topbar', function ($view) {
            $user = auth()->user();
            if (!$user) return;

            $user_client_id = $user->client_id;
            $cacheKey = 'header_data_' . ($user->id ?? 'guest');

            $data = Cache::remember($cacheKey, 600, function () use ($user_client_id, $user) {
                $newTasksCount = 10; // Fixed for now or optimize later
                $newSwapTasksCount = 10;
                $r = new Task();
                $fourDaysAgo = Carbon::now()->subDays(4);
                
                $pickup_delayedTasks = $r->pickup_delayedTasks($user_client_id);
                $drop_off_delayedTasks = $r->drop_off_delayedTasks($user_client_id);
                $delayed_tasks_in_freezer = $r->delayed_tasks_in_freezer($user_client_id);
                $delayed_tasks_delivered = $r->delayed_tasks_delivered($user_client_id);

                $lost_samples = Sample::where('samples.confirmed_by_client', 'LOST');
                if ($user_client_id) {
                    $lost_samples = $lost_samples->leftjoin('tasks', 'tasks.id', '=', 'samples.task_id')
                        ->where('tasks.billing_client', $user_client_id);
                }
                $lost_samples = $lost_samples->where('samples.created_at', '>=', $fourDaysAgo)->limit(10)->get();

                $systemNotifications = $user->unreadNotifications()->limit(10)->get();

                $delayed_count = count($pickup_delayedTasks) + count($drop_off_delayedTasks) +
                               count($delayed_tasks_in_freezer) + count($delayed_tasks_delivered) + 
                               count($lost_samples) + $systemNotifications->count();

                return [
                    'newTasksCount' => $newTasksCount,
                    'newSwapTasksCount' => $newSwapTasksCount,
                    'delayed_count' => $delayed_count,
                    'lost_samples' => $lost_samples,
                    'pickup_delayedTasks' => $pickup_delayedTasks,
                    'drop_off_delayedTasks' => $drop_off_delayedTasks,
                    'delayed_tasks_in_freezer' => $delayed_tasks_in_freezer,
                    'delayed_tasks_delivered' => $delayed_tasks_delivered,
                    'systemNotifications' => $systemNotifications
                ];
            });

            $view->with($data);
        });
    }
}

