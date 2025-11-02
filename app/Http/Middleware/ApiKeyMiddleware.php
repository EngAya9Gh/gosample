<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(!$request->header('secret-key'))
            return response()->failed('403 Forbidden',403);
        $token = config('services.blazma.BLAZMA_SECRET_KEY');

        Log::info("header plazma " . $request->header('secret-key') . " " . $token);
        return $request->header('secret-key') == $token ? $next($request) : response()->failed('403 Forbidden',403);
    }
}
