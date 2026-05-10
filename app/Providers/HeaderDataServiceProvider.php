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

            // Thin Provider: Only pass defaults. AJAX will do the heavy lifting.
            $view->with([
                'newTasksCount' => 0,
                'newSwapTasksCount' => 0,
                'delayed_count' => 0,
                'lost_samples' => [],
                'pickup_delayedTasks' => [],
                'drop_off_delayedTasks' => [],
                'delayed_tasks_in_freezer' => [],
                'delayed_tasks_delivered' => [],
                'systemNotifications' => collect([])
            ]);
        });
    }
}
