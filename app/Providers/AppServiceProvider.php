<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local')) {
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // تسجيل أي استعلام يستغرق أكثر من 100ms في ملفات الـ Log لمراقبة أداء السيرفر
        \Illuminate\Support\Facades\DB::listen(function ($query) {
            if ($query->time > 100) {
                \Illuminate\Support\Facades\Log::warning('[Slow Query Detected]', [
                    'sql'  => $query->sql,
                    'time' => $query->time . ' ms',
                ]);
            }
        });
    }
}
