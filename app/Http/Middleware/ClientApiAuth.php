<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Client;

class ClientApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token missing'], 401);
        }

        $client = Client::where('api_token', $token)->first();

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // خلي معلومات الكلاينت متاحة بالريكويست
        $request->merge(['auth_client' => $client]);

        return $next($request);
    }
}
