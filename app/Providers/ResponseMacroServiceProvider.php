<?php

namespace App\Providers;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class ResponseMacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Response::macro('success', function ($data=null, $status = 200) {
            return Response::json([
                'success' => true,
                'errors'  => [],
                'payload'    => $data,
            ], $status);
        });

        Response::macro('failed', function ($data=null,  $status = 400) {
            return Response::json([
                'success' => false,
                'reload' => false,
                'errors'  => $data,
                'payload'    => [],
            ], $status);
        });
    }
}
