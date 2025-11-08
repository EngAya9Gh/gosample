<?php

namespace App\Http\Controllers;


class DriverController extends Controller
{
    public function checkEmergency()
    {
        $flag = \App\Models\EmergencyFlag::find(1);
        return response()->json([
            'active' => $flag?->active ?? false,
            'message' => $flag?->message ?? '',
        ]);
    }

    public function clearEmergency()
    {
        $flag = \App\Models\EmergencyFlag::find(1);
        if ($flag) {
            $flag->update(['active' => false]);
        }

        return response()->json(['status' => true]);
    }


}
