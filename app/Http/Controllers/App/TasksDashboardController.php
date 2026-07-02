<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Inertia Tasks Dashboard (/app/tasks-dashboard) — mirrors
 * HomeController@tasksdashboard EXACTLY: same per-client aggregation
 * (total / closed / pending via CASE sums), same filter set (driver_id,
 * from_location, to_location, billing_client, status, and the date range
 * applied only when BOTH bounds are set), same ordering (total DESC).
 * No Gate check — the classic route is only auth-gated; the sidebar link
 * carries the 'tasks-dashboard' permission.
 */
class TasksDashboardController extends Controller
{
    public function index(Request $request)
    {
        $tasksData = Task::join('clients', 'clients.id', 'billing_client')
            ->select(
                'clients.arabic_name',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN tasks.status='CLOSED' THEN 1 ELSE 0 END) as closed_total"),
                DB::raw("SUM(CASE WHEN tasks.status NOT IN ('CLOSED','NO_SAMPLES') THEN 1 ELSE 0 END) as pending_total")
            )
            ->when($request->status, fn ($q) => $q->where('tasks.status', $request->status))
            ->when($request->driver_id, fn ($q) => $q->where('driver_id', $request->driver_id))
            ->when($request->billing_client, fn ($q) => $q->where('billing_client', $request->billing_client))
            ->when($request->from_location, fn ($q) => $q->where('from_location', $request->from_location))
            ->when($request->to_location, fn ($q) => $q->where('to_location', $request->to_location))
            ->when($request->date_from && $request->date_to, function ($q) use ($request) {
                $q->whereBetween('tasks.created_at', [
                    Carbon::parse($request->date_from)->startOfDay(),
                    Carbon::parse($request->date_to)->endOfDay(),
                ]);
            })
            ->groupBy('clients.arabic_name')
            ->orderByDesc('total')
            ->get();

        return Inertia::render('Dashboard/TasksDashboard', [
            'categories' => $tasksData->pluck('arabic_name'),
            'totals'     => $tasksData->pluck('total')->map(fn ($v) => (int) $v),
            'closed'     => $tasksData->pluck('closed_total')->map(fn ($v) => (int) $v),
            'pending'    => $tasksData->pluck('pending_total')->map(fn ($v) => (int) $v),
            'drivers'    => Driver::select('id', 'name')->orderBy('name')->get(),
            'locations'  => Location::select('id', 'name')->orderBy('name')->get(),
            'filters'    => [
                'driver_id'     => $request->driver_id ?? '',
                'from_location' => $request->from_location ?? '',
                'to_location'   => $request->to_location ?? '',
                'date_from'     => $request->date_from ?? '',
                'date_to'       => $request->date_to ?? '',
            ],
        ]);
    }
}
