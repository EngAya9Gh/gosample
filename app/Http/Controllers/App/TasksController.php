<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\TasksController as AdminTasksController;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
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
        $total = (clone $query)->count();
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

    /** Render the SPA Create-Task page (same fields/options + store endpoint as the modal). */
    public function create()
    {
        abort_if(Gate::denies('task_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return Inertia::render('Tasks/TaskCreate', [
            'options' => $this->options(auth()->user()),
        ]);
    }

    /**
     * Create task(s) from the SPA modal.
     * MIRRORS Admin\TasksController@store 1:1 — same StoreTaskRequest validation
     * (+ task_create Gate via authorize()), same one-task-per-(visit × from_location)
     * loop, same defaults (added_by = user email, eta = null), same driver
     * notifications + ETA job dispatch. Returns an Inertia redirect back to the
     * list (flash → toast) instead of the classic Blade redirect.
     */
    public function store(StoreTaskRequest $request)
    {
        $user = auth()->user();
        $driver = Driver::find($request->driver_id);

        for ($i = 0; $i < (int) $request->time_of_visit; $i++) {
            foreach ((array) $request->from_location as $fromLocation) {
                $task = new Task();
                $task->to_location    = $request->to_location;
                $task->type           = $request->type;
                $task->pickup_time    = $request->pickup_time;
                $task->dropoff_time   = $request->dropoff_time;
                $task->takasi         = $request->takasi;
                $task->time_of_visit  = $request->time_of_visit;
                $task->task_type      = $request->task_type;
                $task->driver_id      = $request->driver_id;
                $task->billing_client = $request->billing_client;
                $task->from_location  = $fromLocation;
                $task->added_by       = $user->email;
                $task->created_at     = now();
                $task->eta            = null;
                $task->save();

                if ($driver) {
                    $driver->sendNotification('New Task', 'You have new task', [$driver->fcm_token], $task, 'open_task');
                    $this->sendGeneralNotification($driver, $task);
                }
            }
        }

        if ($driver) {
            dispatch(new \App\Jobs\CalculateDriverETAJob($driver->id));
        }

        return redirect()->route('app.admin.tasks')->with('success', 'Task created successfully');
    }

    /**
     * Editable field values for the SPA Edit-Task modal (raw FK columns/enums),
     * fetched when the modal opens since the list rows only carry display names.
     */
    public function editData(Task $task)
    {
        abort_if(Gate::denies('task_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return response()->json([
            'id'             => $task->id,
            'from_location'  => $task->from_location,
            'to_location'    => $task->to_location,
            'billing_client' => $task->billing_client,
            'driver_id'      => $task->driver_id,
            'task_type'      => $task->task_type,
            'status'         => $task->status,
            'takasi'         => $task->takasi,
        ]);
    }

    /**
     * Update a task from the SPA modal. DELEGATES to Admin\TasksController@update
     * so the CLOSED-transition side effects (closed_by, close_date,
     * to_location_confirmation_timestamp, blazma LogData dispatch) and mass-update
     * stay 1:1 with the classic page. UpdateTaskRequest enforces the same
     * validation + task_edit Gate. We swap the classic Blade redirect for an
     * Inertia redirect back to the list (flash → toast).
     */
    public function update(UpdateTaskRequest $request, Task $task, AdminTasksController $classic)
    {
        $classic->update($request, $task);

        return redirect()->route('app.admin.tasks')->with('success', 'Task updated successfully');
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
    public function updateTimes(Request $request, Task $task)
    {
        abort_if(Gate::denies('task_edit_times'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'freezer_out_date' => 'nullable|date',
            'close_date'       => 'nullable|date',
        ]);

        $task->update([
            'freezer_out_date' => $request->freezer_out_date ? Carbon::parse($request->freezer_out_date)->toDateTimeString() : null,
            'close_date'       => $request->close_date ? Carbon::parse($request->close_date)->toDateTimeString() : null,
        ]);

        return redirect()->back()->with('status', 'Task times updated successfully.');
    }
    public function unused(Request $request)
    {
        abort_if(Gate::denies('unused_tasks'), Response::HTTP_FORBIDDEN, '403 Forbidden');

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

        $query = Task::withoutGlobalScope('active')->where('is_unused', 1)->whereHas('driver', function($q) {
            $q->where('status', 1);
        })->with(['from', 'to', 'client', 'driver'])->select('tasks.*');

        // fail-closed client scoping (identical to classic)
        if ($user && !empty($user->assigned_client_ids)) {
            $query->whereIn('billing_client', $user->assigned_client_ids);
        }

        $query->when($request->driver_id, fn ($q, $v) => $q->where('driver_id', $v))
            ->when($request->client_id, fn ($q, $v) => $q->where('billing_client', $v));

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
        $total = (clone $query)->count();
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
            ];
        });

        $filters = $request->only(['client_id', 'driver_id', 'date_from', 'date_to', 'sort_by', 'sort_order']);

        return Inertia::render('Tasks/UnusedTasksList', [
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'pageSize' => $pageSize,
            'filters'  => $filters,
            'options'  => fn () => $this->options($user),
        ]);
    }
}
