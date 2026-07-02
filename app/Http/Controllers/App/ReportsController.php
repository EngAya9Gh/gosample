<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Driver;
use App\Models\Task;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('report_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tab = $request->input('tab', 'performance');

        // Prepare dropdown options
        $options = [
            'drivers' => Driver::select('id', 'name')->orderBy('name')->get(),
            'clients' => Client::select('id', 'english_name')->orderBy('english_name')->get(),
        ];

        // Fail-closed scoping
        $user = auth()->user();
        if ($user && !empty($user->assigned_client_ids)) {
            $options['clients'] = $options['clients']->whereIn('id', $user->assigned_client_ids)->values();
        }

        $reportData = [];

        switch ($tab) {
            case 'performance':
                $reportData = $this->getPerformanceData($request, $user);
                break;
            case 'monthly':
                $reportData = $this->getMonthlyData($request, $user);
                break;
            case 'weekly':
                $reportData = $this->getWeeklyData($request, $user);
                break;
            case 'daily':
                $reportData = $this->getDailyData($request, $user);
                break;
        }

        return Inertia::render('Reports/ReportsDashboard', [
            'tab' => $tab,
            'reportData' => $reportData,
            'filters' => $request->all(),
            'options' => $options
        ]);
    }

    private function getPerformanceData(Request $request, $user)
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $clientId = $request->input('client_id');
        $driverId = $request->input('driver_id');

        $query = Driver::query();
        if ($driverId) {
            $query->where('id', $driverId);
        }

        $drivers = $query->with(['tasks' => function($q) use ($dateFrom, $dateTo, $clientId, $user) {
            $q->whereBetween('created_at', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ]);
            if ($clientId) {
                $q->where('billing_client', $clientId);
            }
            if ($user && !empty($user->assigned_client_ids)) {
                $q->whereIn('billing_client', $user->assigned_client_ids);
            }
        }])->get()->map(function($driver) {
            $tasks = $driver->tasks;
            $total = $tasks->count();
            $delayed = $tasks->where('delayed_reason', '<>', '')->count();
            
            $onTime = $total - $delayed;
            $punctuality = $total > 0 ? round(($onTime / $total) * 100, 1) : 0;
            
            // FIXME: Dummy data for UI testing (remove later)
            // $punctuality = rand(40, 100);
            // $delayed = rand(0, 5);

            // Calculate avg operation speed in minutes (from_location_arrival_time to close_date)
            $totalMins = 0;
            $validTasks = 0;
            foreach($tasks as $t) {
                if ($t->from_location_arrival_time && $t->close_date) {
                    try {
                        $mins = Carbon::parse($t->from_location_arrival_time)->diffInMinutes(Carbon::parse($t->close_date));
                        $totalMins += $mins;
                        $validTasks++;
                    } catch(\Exception $e) {}
                }
            }
            $avgSpeed = $validTasks > 0 ? round($totalMins / $validTasks) : 0;

            return [
                'id' => $driver->id,
                'name' => $driver->name,
                'total_tasks' => $total,
                'delayed_tasks' => $delayed,
                'punctuality' => $punctuality,
                'avg_speed_mins' => $avgSpeed,
            ];
        });

        // Filter out drivers with 0 tasks if filtering by client
        if ($clientId || ($user && !empty($user->assigned_client_ids))) {
            $drivers = $drivers->filter(fn($d) => $d['total_tasks'] > 0)->values();
        }

        return ['drivers' => $drivers];
    }

    private function getMonthlyData(Request $request, $user)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $driverId = $request->input('driver_id');
        
        $start = Carbon::parse($month)->startOfMonth();
        $end = Carbon::parse($month)->isCurrentMonth() ? now() : Carbon::parse($month)->endOfMonth();

        $expectedDays = 0;
        $tempDate = $start->copy();
        while ($tempDate->lte($end)) {
            if ($tempDate->dayOfWeek !== Carbon::FRIDAY) {
                $expectedDays++;
            }
            $tempDate->addDay();
        }

        $query = Driver::query();
        if ($driverId) {
            $query->where('id', $driverId);
        }

        $drivers = $query->with([
            'attendances' => function($q) use ($start, $end) {
                $q->select('id', 'driver_id', 'is_late', 'delay_minutes', 'early_leave_minutes', 'overtime_minutes', 'created_at')
                  ->whereBetween('created_at', [$start->toDateString() . ' 00:00:00', $end->toDateTimeString()]);
            }, 
            'tasks' => function($q) use ($start, $end) {
                $q->select('id', 'driver_id', 'delayed_reason', 'created_at')
                  ->whereBetween('created_at', [$start->toDateString() . ' 00:00:00', $end->toDateTimeString()]);
            }
        ])->get()->map(function($driver) use ($expectedDays) {
            $attendances = $driver->attendances;
            $totalDays = $attendances->count();
            
            $daysPresent = $attendances->pluck('created_at')
                ->map(fn($d) => substr($d, 0, 10))
                ->unique()
                ->count();
            
            $daysAbsent = max(0, $expectedDays - $daysPresent);
            $daysLate = $attendances->where('is_late', true)->count();
            $totalDelay = $attendances->sum('delay_minutes');
            $totalOvertime = $attendances->sum('overtime_minutes');
            $totalEarlyLeave = $attendances->sum('early_leave_minutes');
            $kpiViolations = $attendances->count() > 0 ? $driver->tasks->where('delayed_reason', '<>', '')->count() : 0;
            
            if ($totalDays > 0) {
                $onTimeCount = $attendances->where('is_late', false)->count();
                $punctualityRate = ($onTimeCount / $totalDays) * 100;
                $fullShiftCount = $attendances->where('early_leave_minutes', '<=', 0)->count();
                $completionRate = ($fullShiftCount / $totalDays) * 100;
                $pScore = $punctualityRate * 0.50;
                $cScore = $completionRate * 0.40;
            } else {
                $pScore = 0; $cScore = 0;
            }

            $baseScore = $pScore + $cScore + ($totalDays > 0 ? 10 : 0);
            $penalty = ($kpiViolations ?? 0) * 2;
            $hrsBalance = $totalOvertime - $totalEarlyLeave;
            $performanceScore = max(0, round($baseScore - $penalty));
            
            return [
                'id' => $driver->id,
                'name' => $driver->name,
                'days_present' => $daysPresent,
                'days_absent' => $daysAbsent,
                'days_late' => $daysLate,
                'total_delay' => $totalDelay,
                'total_overtime' => $totalOvertime,
                'hrs_balance' => $hrsBalance,
                'kpi_violations' => $kpiViolations,
                'performance_score' => $performanceScore
            ];
        });

        return ['drivers' => $drivers, 'expected_days' => $expectedDays];
    }

    private function getWeeklyData(Request $request, $user)
    {
        $start = $request->input('date_from', now()->startOfWeek()->toDateString());
        $end = $request->input('date_to', now()->endOfWeek()->toDateString());
        $driverId = $request->input('driver_id');
        
        $query = Driver::query();
        if ($driverId) {
            $query->where('id', $driverId);
        }

        $drivers = $query->with(['attendances' => function($q) use ($start, $end) {
            $q->whereBetween('created_at', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay()
            ]);
        }])->get()->map(function($driver) {
            $attendances = $driver->attendances;
            $days = $attendances->pluck('created_at')->map(fn($d) => substr($d, 0, 10))->unique()->count();
            $delays = $attendances->where('is_late', true)->count(); // legacy used count 'times'
            $overtime = $attendances->sum('overtime_minutes');

            $onTimeCount = $attendances->where('is_late', false)->count();
            $totalDays = $attendances->count();
            $punctuality = $totalDays > 0 ? round(($onTimeCount / $totalDays) * 100) : 0;
             // $punctuality = rand(40, 100);
            // $delayed = rand(0, 5);
            return [
                'id' => $driver->id,
                'name' => $driver->name,
                'days_worked' => $days,
                'total_delays' => $delays,
                'overtime' => $overtime,
                'punctuality' => $punctuality,
            ];
        });

        return ['drivers' => $drivers];
    }

    private function getDailyData(Request $request, $user)
    {
        $date = $request->input('search_date', now()->toDateString());
        $driverId = $request->input('driver_id');
        
        $query = Driver::query();
        if ($driverId) {
            $query->where('id', $driverId);
        }

        $drivers = $query->with(['attendances' => function($q) use ($date) {
            $q->whereBetween('created_at', [
                Carbon::parse($date)->startOfDay(),
                Carbon::parse($date)->endOfDay(),
            ]);
        }, 'tasks' => function($q) use ($date) {
            $q->whereBetween('created_at', [
                Carbon::parse($date)->startOfDay(),
                Carbon::parse($date)->endOfDay(),
            ]);
        }])->get()->map(function($driver) {
            $attendance = $driver->attendances->first();
            $delayedTasks = $driver->tasks->where('delayed_reason', '<>', '')->count();
            
            return [
                'id' => $driver->id,
                'name' => $driver->name,
                'has_attendance' => !!$attendance,
                'check_in' => $attendance && $attendance->checkin_time ? Carbon::parse($attendance->checkin_time)->format('H:i') : '--:--',
                'check_out' => $attendance && $attendance->checkout_time ? Carbon::parse($attendance->checkout_time)->format('H:i') : '--:--',
                'is_late' => $attendance ? $attendance->is_late : false,
                'delay_mins' => $attendance ? $attendance->delay_minutes : 0,
                'total_tasks' => $driver->tasks->count(),
                'delayed_tasks' => $delayedTasks
            ];
        });

        return ['drivers' => $drivers];
    }
}
