<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Driver;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function daily(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        
        $drivers = Driver::with(['attendances' => function($q) use ($date) {
            $q->whereDate('created_at', $date);
        }, 'tasks' => function($q) use ($date) {
            $q->whereDate('created_at', $date);
        }])->get()->map(function($driver) {
            $driver->day_attendance = $driver->attendances->first();
            $driver->delayed_tasks_count = $driver->tasks->where('delayed_reason', '<>', '')->count();
            return $driver;
        });

        return view('admin.reports.daily', compact('drivers', 'date'));
    }

    public function weekly(Request $request)
    {
        $start = $request->input('start_date', now()->startOfWeek()->toDateString());
        $end = $request->input('end_date', now()->endOfWeek()->toDateString());
        
        $drivers = Driver::with(['attendances' => function($q) use ($start, $end) {
            $q->whereBetween('created_at', [$start, $end]);
        }])->get();

        return view('admin.reports.weekly', compact('drivers', 'start', 'end'));
    }

    public function monthly(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $cacheKey = 'monthly_report_' . $month . '_' . (auth()->id() ?? 'guest');

        $data = \Cache::remember($cacheKey, 300, function () use ($month) {
            $start = Carbon::parse($month)->startOfMonth();
            $end = Carbon::parse($month)->isCurrentMonth() ? now() : Carbon::parse($month)->endOfMonth();

            // Expected days calculation
            $expectedDays = 0;
            $tempDate = $start->copy();
            while ($tempDate->lte($end)) {
                if ($tempDate->dayOfWeek !== Carbon::FRIDAY) {
                    $expectedDays++;
                }
                $tempDate->addDay();
            }

            $drivers = Driver::with([
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
                
                // Optimized date unique count without Carbon objects
                $driver->days_present = $attendances->pluck('created_at')
                    ->map(fn($d) => substr($d, 0, 10))
                    ->unique()
                    ->count();
                
                $driver->days_absent = max(0, $expectedDays - $driver->days_present);
                $driver->days_late = $attendances->where('is_late', true)->count();
                $driver->total_delay = $attendances->sum('delay_minutes');
                $driver->total_overtime = $attendances->sum('overtime_minutes');
                $driver->total_early_leave = $attendances->sum('early_leave_minutes');
                $driver->kpi_violations = $attendances->count() > 0 ? $driver->tasks->where('delayed_reason', '<>', '')->count() : 0;
                
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
                $penalty = ($driver->kpi_violations ?? 0) * 2;
                $driver->performance_score = max(0, round($baseScore - $penalty));
                
                return $driver;
            });

            return [
                'drivers' => $drivers,
                'expectedDays' => $expectedDays
            ];
        });

        $drivers = $data['drivers'];
        $expectedDays = $data['expectedDays'];

        if ($request->ajax()) {
            return view('admin.reports.partials.monthly_table', compact('drivers', 'month', 'expectedDays'))->render();
        }

        return view('admin.reports.monthly', compact('drivers', 'month', 'expectedDays'));
    }

    public function exportMonthly(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        return \Excel::download(new \App\Exports\MonthlyPerformanceExport($month), "Performance-Report-{$month}.xlsx");
    }

    public function performance(Request $request)
    {
        $drivers = Driver::with(['tasks' => function($q) {
            $q->whereNotNull('to_location_arrival_time')
              ->whereNotNull('from_location_arrival_time');
        }])->get()->map(function($driver) {
            $tasks = $driver->tasks;
            
            // KPI 1: Punctuality Score (from Model)
            $driver->kpi_punctuality = $driver->punctuality_score;
            
            // KPI 2: Operation Speed (Avg Minutes per task)
            $totalMinutes = 0;
            $completedTasks = $tasks->filter(fn($t) => $t->from_location_arrival_time && $t->to_location_arrival_time);
            foreach($completedTasks as $task) {
                $totalMinutes += Carbon::parse($task->from_location_arrival_time)->diffInMinutes(Carbon::parse($task->to_location_arrival_time));
            }
            $driver->kpi_avg_speed = $completedTasks->count() > 0 ? round($totalMinutes / $completedTasks->count()) : 0;
            
            // KPI 3: Violations (Delayed Steps)
            $driver->kpi_violations = $driver->tasks->where('delayed_reason', '<>', '')->count();
            
            return $driver;
        });

        return view('admin.reports.performance', compact('drivers'));
    }
}
