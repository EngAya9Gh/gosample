<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\Driver;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAttendanceKPIJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $attendance;

    /**
     * Create a new job instance.
     */
    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $attendance = $this->attendance;
        $driver = $attendance->driver;

        if (!$driver) return;

        $updates = [];

        // 1. Auto-populate expected times if empty
        $expectedStart = $attendance->expected_start;
        $expectedEnd = $attendance->expected_end;
        $date = $attendance->created_at ? $attendance->created_at->toDateString() : now()->toDateString();

        if (!$expectedStart || !$expectedEnd) {
            if ($attendance->shift_id && $attendance->shift) {
                // استخدام بيانات الوردية المحددة
                $expectedStart = $expectedStart ?? $attendance->shift->start_time;
                $expectedEnd = $expectedEnd ?? $attendance->shift->end_time;
            } else {
                // الرجوع للبيانات الأساسية في ملف السائق
                $expectedStart = $expectedStart ?? $driver->working_hours_start;
                $expectedEnd = $expectedEnd ?? $driver->working_hours_end;
            }
        }

        // التأكد من أن التوقيت مدمج مع التاريخ ليقبل في قاعدة البيانات
        if ($expectedStart && strlen($expectedStart) <= 8) {
            $expectedStart = $date . ' ' . $expectedStart;
        }
        if ($expectedEnd && strlen($expectedEnd) <= 8) {
            $expectedEnd = $date . ' ' . $expectedEnd;
        }

        if ($expectedStart) $updates['expected_start'] = $expectedStart;
        if ($expectedEnd) $updates['expected_end'] = $expectedEnd;

        // 2. Calculate Delay (Undertime check-in)
        if ($attendance->checkin_time && $expectedStart) {
            $checkin = Carbon::parse($attendance->checkin_time);
            $expected = Carbon::parse($expectedStart);
            
            if ($checkin->greaterThan($expected)) {
                $updates['delay_minutes'] = $checkin->diffInMinutes($expected);
                $updates['is_late'] = true;
            } else {
                $updates['delay_minutes'] = 0;
                $updates['is_late'] = false;
            }
        }

        // 3. Calculate Overtime & Early Leave (Undertime checkout)
        if ($attendance->checkout_time && $expectedEnd) {
            $checkout = Carbon::parse($attendance->checkout_time);
            $expectedE = Carbon::parse($expectedEnd);
            
            if ($checkout->greaterThan($expectedE)) {
                $updates['overtime_minutes'] = $checkout->diffInMinutes($expectedE);
                $updates['early_leave_minutes'] = 0;
            } elseif ($checkout->lessThan($expectedE)) {
                $updates['early_leave_minutes'] = $expectedE->diffInMinutes($checkout);
                $updates['overtime_minutes'] = 0;
            } else {
                $updates['overtime_minutes'] = 0;
                $updates['early_leave_minutes'] = 0;
            }
        }

        if (!empty($updates)) {
            $attendance->update($updates);
        }
    }
}
