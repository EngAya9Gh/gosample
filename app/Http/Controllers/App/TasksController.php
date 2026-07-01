<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Client;
use App\Models\Location;
use App\Models\Driver;
use App\Models\Sample;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inertia page controller for the new Tasks screen (/app/admin/tasks).
 * MIRRORS App\Http\Controllers\Admin\TasksController@index (the classic
 * server-side DataTable) — same filters, same fail-closed client scoping,
 * same 30-day default window, same sort whitelist, same Gate (task_access).
 * Presentation-only: exports still use the existing /admin export routes.
 */
class TasksController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = auth()->user();

        // sort whitelist (identical to classic)
        $sortColumn = $request->input('sort_by');
        if (!in_array($sortColumn, ['created_at', 'updated_at', 'collection_date'], true)) {
            $sortColumn = 'created_at';
        }
        $sortOrder = $request->input('sort_order', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'desc';
        }

        $query = Task::with(['from', 'to', 'client', 'driver', 'car'])->select('tasks.*');

        // fail-closed client scoping (identical to classic)
        if ($user && !empty($user->assigned_client_ids)) {
            $query->whereIn('billing_client', $user->assigned_client_ids);
        }

        $query->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->driver_id, fn ($q, $v) => $q->where('driver_id', $v))
            ->when($request->billing_client, fn ($q, $v) => $q->where('billing_client', $v))
            ->when($request->from_location, fn ($q, $v) => $q->where('from_location', $v))
            ->when($request->to_location, fn ($q, $v) => $q->where('to_location', $v))
            ->when($request->keyword, fn ($q, $v) => $q->where('tasks.id', $v));

        $dateColumn = $request->input('search_date') ?: 'tasks.created_at';
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : null;
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : null;
        if (!$dateFrom && !$dateTo && !$request->keyword) {
            $dateFrom = Carbon::now()->subDays(30)->startOfDay();
            $dateTo = Carbon::now()->endOfDay();
        }
        if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }
        if ($dateFrom && $dateTo) {
            $query->whereBetween($dateColumn, [$dateFrom->toDateTimeString(), $dateTo->toDateTimeString()]);
        } elseif ($dateFrom) {
            $query->where($dateColumn, '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->where($dateColumn, '<=', $dateTo);
        }

        $query->orderBy($sortColumn, $sortOrder);

        $pageSize = max(1, min((int) $request->input('pageSize', 25), 1000));
        $page = max(1, (int) $request->input('page', 1));
        $total = (clone $query)->toBase()->getCountForPagination();
        $offset = ($page - 1) * $pageSize;

        $seq = $offset;
        $rows = $query->offset($offset)->limit($pageSize)->get()->map(function (Task $t) use (&$seq) {
            return [
                'sequence'           => ++$seq,
                'id'                 => $t->id,
                'created_at'         => $this->fmt($t->created_at),
                'client'             => optional($t->client)->english_name,
                'driver_name'        => optional($t->driver)->name,
                'from_location_name' => optional($t->from)->name,
                'to_location_name'   => optional($t->to)->name,
                'eta'                => $t->eta,
                'collection_date'    => $this->fmt($t->collection_date),
                'freezer_date'       => $this->fmt($t->freezer_date),
                'freezer_out_date'   => $this->fmt($t->freezer_out_date),
                'close_date'         => $this->fmt($t->close_date),
                'status'             => $t->status,
                'task_type'          => $t->task_type,
                'added_by'           => $t->added_by,
                'hours'              => $this->hours($t->collection_date, $t->close_date),
            ];
        });

        // Echo the filters back so the form stays populated across reloads.
        $filters = $request->only([
            'keyword', 'status', 'driver_id', 'billing_client', 'from_location',
            'to_location', 'date_from', 'date_to', 'search_date', 'sort_by', 'sort_order',
        ]);

        return Inertia::render('Tasks/TasksList', [
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'pageSize' => $pageSize,
            'filters'  => $filters,
            // Lazy closure → skipped on partial table reloads (only: ['rows','total']).
            'options'  => fn () => $this->options($user),
        ]);
    }

    private function options($user): array
    {
        if ($user && !empty($user->assigned_client_ids)) {
            $clients = Client::whereIn('id', $user->assigned_client_ids)->get();
            $locations = Location::select('locations.*')
                ->leftJoin('client_location', 'client_location.location_id', 'locations.id')
                ->whereIn('client_location.client_id', $user->assigned_client_ids)
                ->distinct()
                ->get();
        } else {
            $clients = Client::all();
            $locations = Location::all();
        }
        $drivers = Driver::all();

        return [
            'drivers'   => $drivers->map(fn ($d) => ['value' => $d->id, 'label' => $d->name])->values(),
            'clients'   => $clients->map(fn ($c) => ['value' => $c->id, 'label' => $c->english_name])->values(),
            'locations' => $locations->map(fn ($l) => ['value' => $l->id, 'label' => $l->name])->values(),
        ];
    }

    private function fmt($value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return Carbon::parse($value)->format('d M H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function hours($from, $to): string
    {
        if (!$from || !$to) {
            return '';
        }
        try {
            $mins = Carbon::parse($from)->diffInMinutes(Carbon::parse($to));
            return sprintf('%02d Hours, %02d Minutes', intdiv($mins, 60), $mins % 60);
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function show(Task $task)
    {
        abort_if(Gate::denies('task_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $task->load('from', 'to', 'client', 'driver', 'car');
        $bags = Sample::with('container')->where('task_id', $task->id)->get()->groupBy('bag_code');
        $bag_count = Sample::where('task_id', $task->id)->distinct('bag_code')->count('bag_code');
        $sample_count = Sample::where('task_id', $task->id)->count();
        
        $carTracking = DB::table('car_tracking')
            ->select(
                DB::raw("count(id) AS cnt"),
                DB::raw("COALESCE(ROUND(SUM(temp5),2),'0') AS total_temp_1"),
                DB::raw("COALESCE(ROUND(SUM(temp6),2),'0') AS total_temp_2"),
                DB::raw("COALESCE(ROUND(SUM(temp7),2),'0') AS total_temp_3"),
            )->where('task_id', $task->id)->first();
            
        $temperatureReadings = DB::table('car_tracking')
            ->select('created_at', 'temp5', 'temp6', 'temp7')
            ->where('task_id', $task->id)
            ->orderBy('created_at')
            ->get();

        $labels = $temperatureReadings->pluck('created_at')->map(function ($time) {
            return \Carbon\Carbon::parse($time)->format('H:i');
        });

        $temp1 = $temperatureReadings->pluck('temp5');
        $temp2 = $temperatureReadings->pluck('temp6');
        $temp3 = $temperatureReadings->pluck('temp7');

        return Inertia::render('Tasks/TaskDetails', [
            'task' => $task,
            'bags' => $bags,
            'bag_count' => $bag_count,
            'sample_count' => $sample_count,
            'carTracking' => $carTracking,
            'labels' => $labels,
            'temp1' => $temp1,
            'temp2' => $temp2,
            'temp3' => $temp3,
        ]);
    }
}
