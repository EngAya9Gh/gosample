<?php

namespace App\Http\Controllers;

use App\Models\EmergencyFlag;
use Illuminate\Support\Facades\Cache;

class EmergencyController extends Controller
{
    public function checkEmergency()
    {
        $flag = EmergencyFlag::where('active', 1)->first();

        $status = [
            'active' => $flag?->active ?? false,
            'message' => $flag?->message ?? '',
        ];

        // خزنه بالكاش لمدة 10 ثواني (تقدر تغيرها)
        Cache::put('emergency_status', $status, now()->addSeconds(10));
        return response()->json($status);
    }

    public function clearEmergency()
    {
        Cache::forget('emergency_status');

        return response()->json(['status' => false]);
    }


}
