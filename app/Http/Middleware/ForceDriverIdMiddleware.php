<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceDriverIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
//        if (auth()->guard('drivers')->check()) {
//            $request->merge([
//                'driver_id' => auth()->guard('drivers')->id()
//            ]);
//        }

        return $next($request);
    }
}
