<?php

namespace App\Http\Controllers;


class EmergencyController extends Controller
{
    public function checkEmergency()
    {
        $status = Cache::get('emergency_status', [
            'active' => false,
            'message' => '',
        ]);

        return response()->json($status);
    }

    public function clearEmergency()
    {
        Cache::forget('emergency_status');

        return response()->json(['status' => true]);
    }


}
