<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Notifications;
use App\Models\Car;
use App\Models\Task;
use App\Models\Sample;
use App\Models\Location;
use App\Models\Driver;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Inertia Analytics dashboard (/app/dashboard) — mirrors App\Http\Controllers\HomeController@index
 * EXACTLY (same 30-min cache keys, same client scoping) plus the samples-temperature
 * donut from SampleController@report. Read-only, no business-logic change.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $loggedUser = auth()->user();
        $scoped = !empty($loggedUser->assigned_client_ids);

        // ---- Notifications (latest 10, scoped) ----
        $notifQuery = Notifications::with([
            'client:id,english_name', 'driver:id,name', 'fromLocation:id,name', 'toLocation:id,name',
        ])->orderBy('created_at', 'desc');
        if ($scoped) {
            $notifQuery->whereIn('billing_client', $loggedUser->assigned_client_ids);
        }
        $notifications = $notifQuery->limit(10)->get()->map(fn ($n) => [
            'title'   => $n->title,
            'task_id' => $n->task_id,
            'from'    => optional($n->fromLocation)->name,
            'to'      => optional($n->toLocation)->name,
            'driver'  => optional($n->driver)->name,
            'time'    => $n->created_at ? Carbon::parse($n->created_at)->diffForHumans() : '',
        ])->values();

        // ---- Top drivers (cached 30 min, same keys as HomeController) ----
        $cacheKeyTopDrivers = $scoped
            ? 'top_drivers_clients_' . md5(implode(',', $loggedUser->assigned_client_ids))
            : 'top_drivers_admin';
        $top_drivers = Cache::remember($cacheKeyTopDrivers, now()->addMinutes(30), function () use ($loggedUser, $scoped) {
            if ($scoped) {
                return DB::table('tasks')
                    ->select('tasks.driver_id', 'drivers.name', DB::raw('COUNT(tasks.id) as total'))
                    ->join('drivers', 'drivers.id', '=', 'tasks.driver_id')
                    ->join('client_driver', 'client_driver.driver_id', '=', 'drivers.id')
                    ->whereIn('client_driver.client_id', $loggedUser->assigned_client_ids)
                    ->whereIn('tasks.billing_client', $loggedUser->assigned_client_ids)
                    ->where('drivers.status', 1)
                    ->groupBy('tasks.driver_id', 'drivers.name')
                    ->orderByDesc('total')->limit(5)->get();
            }
            return DB::table(DB::raw('(SELECT driver_id, COUNT(id) as total FROM tasks WHERE driver_id IS NOT NULL GROUP BY driver_id) t'))
                ->select('t.driver_id', 'drivers.name', 't.total')
                ->join('drivers', 'drivers.id', '=', 't.driver_id')
                ->where('drivers.status', 1)
                ->orderByDesc('t.total')->limit(5)->get();
        });

        // ---- Stats (cached 30 min, same keys) ----
        $cacheKeyStats = $scoped
            ? 'dashboard_stats_clients_' . md5(implode(',', $loggedUser->assigned_client_ids))
            : 'dashboard_stats_admin';
        if ($scoped) {
            $stats = Cache::remember($cacheKeyStats, now()->addMinutes(30), function () use ($loggedUser) {
                return (object) [
                    'cars'      => Car::whereHas('driver.clientDrivers', fn ($q) => $q->whereIn('client_id', $loggedUser->assigned_client_ids))->count(),
                    'tasks'     => Task::whereIn('billing_client', $loggedUser->assigned_client_ids)->count(),
                    'samples'   => Sample::join('tasks', 'tasks.id', '=', 'task_id')->whereIn('tasks.billing_client', $loggedUser->assigned_client_ids)->count(),
                    'locations' => Location::leftJoin('client_location', 'client_location.location_id', '=', 'locations.id')->whereIn('client_location.client_id', $loggedUser->assigned_client_ids)->count(),
                    'clients'   => count($loggedUser->assigned_client_ids),
                ];
            });
        } else {
            $stats = Cache::remember($cacheKeyStats, now()->addMinutes(30), function () {
                return (object) [
                    'cars'      => DB::table('cars')->count(),
                    'tasks'     => DB::table('tasks')->count(),
                    'samples'   => DB::table('samples')->count(),
                    'drivers'   => DB::table('drivers')->count(),
                    'users'     => DB::table('users')->count(),
                    'locations' => DB::table('locations')->count(),
                    'clients'   => DB::table('clients')->count(),
                ];
            });
        }

        // ---- Samples-temperature donut (date-range; partial-reloadable) ----
        $from = $request->input('from');
        $to = $request->input('to');
        $donutQuery = Sample::select('samples.temperature_type', DB::raw('count(*) as total'));
        if ($scoped) {
            $donutQuery->join('tasks', 'tasks.id', '=', 'samples.task_id')
                ->whereIn('tasks.billing_client', $loggedUser->assigned_client_ids);
        }
        if ($from && $to) {
            $donutQuery->whereBetween('samples.created_at', [
                Carbon::parse($from)->startOfDay()->toDateTimeString(),
                Carbon::parse($to)->endOfDay()->toDateTimeString(),
            ]);
        }
        $donut = $donutQuery->groupBy('temperature_type')->orderByDesc('total')->get();

        // ---- Task Activity: real task volume over the last 12 months (cached, scoped) ----
        $cacheKeyActivity = $scoped
            ? 'dash_task_activity_clients_' . md5(implode(',', $loggedUser->assigned_client_ids))
            : 'dash_task_activity_admin';
        $taskActivity = Cache::remember($cacheKeyActivity, now()->addMinutes(30), function () use ($scoped, $loggedUser) {
            $startMonth = Carbon::now()->startOfMonth()->subMonths(11);
            $q = Task::query();
            if ($scoped) {
                $q->whereIn('billing_client', $loggedUser->assigned_client_ids);
            }
            $byMonth = $q->where('created_at', '>=', $startMonth->toDateTimeString())
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
                ->groupBy('ym')->pluck('total', 'ym');

            $labels = [];
            $values = [];
            for ($i = 0; $i < 12; $i++) {
                $m = $startMonth->copy()->addMonths($i);
                $labels[] = $m->format('M');
                $values[] = (int) ($byMonth[$m->format('Y-m')] ?? 0);
            }
            return ['labels' => $labels, 'values' => $values];
        });

        return Inertia::render('Dashboard/Analytics', [
            'stats' => [
                'tasks'     => $stats->tasks,
                'samples'   => $stats->samples,
                'clients'   => $stats->clients,
                'cars'      => $stats->cars,
                'drivers'   => $stats->drivers ?? Driver::count(),
                'users'     => $stats->users ?? User::count(),
                'locations' => $stats->locations,
            ],
            'topDrivers'    => $top_drivers,
            'notifications' => $notifications,
            'samplesReport' => [
                'labels' => $donut->pluck('temperature_type')->values(),
                'values' => $donut->pluck('total')->map(fn ($v) => (int) $v)->values(),
            ],
            'range' => ['from' => $from, 'to' => $to],
            'taskActivity' => $taskActivity,
        ]);
    }
}
