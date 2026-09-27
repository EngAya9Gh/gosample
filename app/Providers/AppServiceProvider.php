<?php

namespace App\Providers;

use Illuminate\Database\Query\Builder as QueryBuilder;
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

        $this->registerCaseInsensitiveSearchMacros();

        // تسجيل أي استعلام يستغرق أكثر من 100ms في ملفات الـ Log لمراقبة أداء السيرفر
        \Illuminate\Support\Facades\DB::listen(function ($query) {
            if ($query->time > 100) {
                $req = request();
                $url = $req ? $req->fullUrl() : 'CLI / Job';
                $method = $req ? $req->method() : 'CLI';

                \Illuminate\Support\Facades\Log::warning('[Slow Query Detected]', [
                    'url'    => $url,
                    'method' => $method,
                    'sql'    => $query->sql,
                    'time'   => $query->time . ' ms',
                ]);
            }
        });
    }

    /**
     * whereLike() / orWhereLike() — a LIKE that never depends on the column's
     * collation. Plain `LIKE` is case-insensitive on a *_ci column but exact on a
     * *_bin / *_cs one, so the same filter behaved differently per column and per
     * environment. Both sides are lowercased here instead.
     *
     * $value carries its own wildcards, e.g. whereLike('name', "%{$kw}%").
     */
    protected function registerCaseInsensitiveSearchMacros(): void
    {
        QueryBuilder::macro('whereLike', function ($column, $value, $boolean = 'and') {
            /** @var QueryBuilder $this */
            return $this->whereRaw(
                'LOWER(' . $this->grammar->wrap($column) . ') LIKE ?',
                [mb_strtolower((string) $value)],
                $boolean
            );
        });

        QueryBuilder::macro('orWhereLike', function ($column, $value) {
            /** @var QueryBuilder $this */
            return $this->whereLike($column, $value, 'or');
        });
    }
}
