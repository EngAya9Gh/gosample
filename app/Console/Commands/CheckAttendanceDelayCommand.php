<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Driver;
use App\Models\Attendance;
use Carbon\Carbon;

class CheckAttendanceDelayCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:check-late';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for drivers who missed their shift start time and send alerts.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $todayName = strtolower($now->format('l')); // e.g., 'monday'

        // 1. Get all drivers who have active shifts today
        $drivers = Driver::with(['activeShifts' => function($q) use ($todayName) {
            $q->whereJsonContains('days', $todayName);
        }])->where('status', 1)->get();

        foreach ($drivers as $driver) {
            if ($driver->activeShifts->isEmpty()) {
                // Check legacy fields if no active new shifts are present
                $this->checkLegacyShifts($driver, $now);
                continue;
            }

            foreach ($driver->activeShifts as $shift) {
                $this->checkShiftDelay($driver, $shift->start_time, $shift->end_time, $shift->id, $now);
            }
        }

        $this->info('Attendance delay check completed.');
    }

    private function checkLegacyShifts($driver, Carbon $now)
    {
        // Check first shift
        if (!empty($driver->working_hours_start) && !empty($driver->working_hours_end)) {
            $this->checkShiftDelay($driver, $driver->working_hours_start, $driver->working_hours_end, null, $now);
        }

        // Check second shift
        if (!empty($driver->second_shift_working_hours_start) && !empty($driver->second_shift_working_hours_end)) {
            $this->checkShiftDelay($driver, $driver->second_shift_working_hours_start, $driver->second_shift_working_hours_end, null, $now);
        }
    }

    private function checkShiftDelay($driver, $start_time_str, $end_time_str, $shift_id, Carbon $now)
    {
        // Parse shift times for today
        $shiftStart = Carbon::createFromFormat('H:i:s', $start_time_str)->setDate($now->year, $now->month, $now->day);
        $shiftEnd = Carbon::createFromFormat('H:i:s', $end_time_str)->setDate($now->year, $now->month, $now->day);
        
        // Handle night shifts that cross midnight
        if ($shiftEnd->lessThan($shiftStart)) {
            $shiftEnd->addDay();
        }

        // We only care if it's currently within the shift time and more than 15 minutes past start
        if ($now->greaterThanOrEqualTo($shiftStart->copy()->addMinutes(15)) && $now->lessThanOrEqualTo($shiftEnd)) {
            
            // Check if there is an attendance record for today and this shift
            $query = Attendance::where('driver_id', $driver->id)
                ->whereDate('created_at', $now->toDateString())
                ->whereNotNull('checkin_time');

            if ($shift_id) {
                $query->where('shift_id', $shift_id);
            } else {
                $query->where('expected_start', $start_time_str);
            }

            $attendanceExists = $query->exists();

            if (!$attendanceExists) {
                // Check if we already created an auto-absence record to prevent multiple alerts
                $absenceRecord = Attendance::where('driver_id', $driver->id)
                    ->whereDate('created_at', $now->toDateString())
                    ->where('source', 'auto')
                    ->where(function($q) use ($shift_id, $start_time_str) {
                        if ($shift_id) {
                            $q->where('shift_id', $shift_id);
                        } else {
                            $q->where('expected_start', $start_time_str);
                        }
                    })
                    ->first();

                if (!$absenceRecord) {
                    // Calculate delay minutes
                    $delayMinutes = $now->diffInMinutes($shiftStart);

                    // 1. Create automatic attendance record as late/missing
                    Attendance::create([
                        'driver_id' => $driver->id,
                        'shift_id' => $shift_id,
                        'expected_start' => $start_time_str,
                        'expected_end' => $end_time_str,
                        'delay_minutes' => $delayMinutes,
                        'is_late' => true,
                        'source' => 'auto',
                        'alert_sent' => true,
                        // Not setting checkin_time because they haven't actually checked in yet
                    ]);

                    // 2. Send Real-Time Alert to Admin/Driver
                    // We use driver's sendNotification method if appropriate, or another alerting system
                    // To keep it simple, we'll send it to the driver and maybe admin
                    
                    $title = "🚨 تنبيه تأخير دوام";
                    $body = "لقد تجاوزت موعد بدء الدوام المحدد بـ " . $delayMinutes . " دقيقة. الرجاء تسجيل الحضور فوراً.";
                    
                    if ($driver->fcm_token) {
                        $driver->sendNotification($title, $body, [$driver->fcm_token], (object)['id' => null, 'from' => (object)['name'=>''], 'to' => (object)['name'=>'']], 'attendance_alert');
                    }
                    
                    \Log::info("Late attendance alert sent for Driver ID: {$driver->id}");
                } else {
                    // Update the delay minutes of the auto record
                    $absenceRecord->update([
                        'delay_minutes' => $now->diffInMinutes($shiftStart)
                    ]);
                }
            }
        }
    }
}
