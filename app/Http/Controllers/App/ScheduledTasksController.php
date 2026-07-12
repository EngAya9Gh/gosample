<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduledTaskRequest;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Location;
use App\Models\ScheduledTask;
use App\Models\Task;
use Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class ScheduledTasksController extends Controller
{
    /**
     * Display a listing of scheduled tasks (Vue).
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // 1. Base Query with parent_id = null
        $query = ScheduledTask::with(['from_location', 'to_location', 'client', 'driver'])
            ->whereNull('parent_id')
            ->select('scheduled_tasks.*');

        // 3. Quick Filters
        if ($request->filled('status')) {
            $query->where('scheduled_tasks.status', $request->status);
        }
        if ($request->filled('task_type')) {
            $query->where('scheduled_tasks.task_type', $request->task_type);
        }

        // 4. Detailed Filters
        if ($request->filled('driver_id')) {
            $query->where('scheduled_tasks.driver_id', $request->driver_id);
        }
        if ($request->filled('client_id')) {
            $query->where('scheduled_tasks.client_id', $request->client_id);
        }
        if ($request->filled('from_location')) {
            $query->where('scheduled_tasks.from_location_id', $request->from_location);
        }
        if ($request->filled('to_location')) {
            $query->where('scheduled_tasks.to_location_id', $request->to_location);
        }
        $query->when($request->name, fn($q, $v) => $q->where('scheduled_tasks.name', 'like', "%{$v}%"));

        // 5. Date Filters
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : null;
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : null;
        if ($dateFrom && $dateTo) {
            $query->whereBetween('scheduled_tasks.created_at', [$dateFrom, $dateTo]);
        } elseif ($dateFrom) {
            $query->where('scheduled_tasks.created_at', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->where('scheduled_tasks.created_at', '<=', $dateTo);
        }

        // 6. Pagination & Sorting
        $sortColumn = $request->input('sort_by', 'scheduled_tasks.created_at');
        if (!in_array($sortColumn, ['scheduled_tasks.created_at', 'scheduled_tasks.updated_at', 'scheduled_tasks.name'], true)) {
            $sortColumn = 'scheduled_tasks.created_at';
        }
        $sortOrder = $request->input('sort_order', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'desc';
        }
        $query->orderBy($sortColumn, $sortOrder);

        $page = max(1, (int)$request->input('page', 1));
        $pageSize = max(1, (int)$request->input('pageSize', 25));
        $offset = ($page - 1) * $pageSize;

        // Use count() to get accurate total records after filtering
        $total = (clone $query)->count();

        \Illuminate\Support\Facades\Log::info('ScheduledTasks Query: ' . $query->toSql(), $query->getBindings());

        // 7. Get and Transform rows
        $rows = $query->offset($offset)->limit($pageSize)->get()->map(function ($row) {
            return [
                'id'                 => $row->id,
                'name'               => $row->name,
                'status'             => $row->status,
                'task_type'          => $row->task_type,
                'driver_name'        => $row->driver ? $row->driver->name : null,
                'client_name'        => $row->client ? $row->client->english_name : null,
                'from_location_name' => $row->from_location ? $row->from_location->name : null,
                'to_location_name'   => $row->to_location ? $row->to_location->name : null,
                'start_date'         => $row->start_date,
                'end_date'           => $row->end_date,
                'selected_days'      => $row->selected_days,
                'selected_hour'      => $row->selected_hour,
                'added_by'           => $row->added_by,
                'created_at'         => $row->created_at ? $row->created_at->format('Y-m-d H:i') : null,
            ];
        });

        \Illuminate\Support\Facades\Log::info('ScheduledTasks Total: ' . $total . ' Rows Count: ' . $rows->count());

        // 8. Return Inertia response
        $filters = $request->only(['name', 'status', 'task_type', 'driver_id', 'client_id', 'from_location', 'to_location', 'date_from', 'date_to', 'sort_by', 'sort_order']);

        // 9. Form Options
        $clients = \App\Models\Client::select('id', 'english_name')->get()->map(fn($c) => ['value' => $c->id, 'label' => $c->english_name]);
        $locations = \App\Models\Location::select('id', 'name')->get()->map(fn($l) => ['value' => $l->id, 'label' => $l->name]);
        $drivers = \App\Models\Driver::select('id', 'name')->get()->map(fn($d) => ['value' => $d->id, 'label' => $d->name]);

        return Inertia::render('Tasks/ScheduledTasksList', [
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'pageSize' => $pageSize,
            'filters'  => $filters,
            'clients'  => $clients,
            'locations'=> $locations,
            'drivers'  => $drivers,
        ]);
    }

    /** Dropdown data shared by both create pages. */
    private function formOptions(): array
    {
        return [
            'drivers'   => Driver::select('id', 'name')->orderBy('name')->get()->map(fn ($d) => ['value' => $d->id, 'label' => $d->name]),
            'clients'   => Client::select('id', 'english_name')->orderBy('english_name')->get()->map(fn ($c) => ['value' => $c->id, 'label' => $c->english_name]),
            'locations' => Location::select('id', 'name')->orderBy('name')->get()->map(fn ($l) => ['value' => $l->id, 'label' => $l->name]),
            'days'      => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'statuses'  => collect(ScheduledTask::STATUS_SELECT)->map(fn ($v, $k) => ['value' => $k, 'label' => $v])->values(),
            'taskTypes' => collect(Task::TASK_TYPE_SELECT)->map(fn ($v, $k) => ['value' => $k, 'label' => $v])->values(),
        ];
    }

    /** Add Scheduled Task page. */
    public function create()
    {
        abort_if(Gate::denies('scheduled_task_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return Inertia::render('Tasks/ScheduledTaskCreate', ['options' => $this->formOptions()]);
    }

    /**
     * Store scheduled task(s). MIRRORS Admin\ScheduledTaskController@store — one
     * task per (from_location × day), selected_hour taken from the per-location
     * visit_hours map, parent-child linked via parent_id.
     */
    public function store(StoreScheduledTaskRequest $request)
    {
        $data = $request->except(['from_location_id', 'days', 'visit_hours']);
        $driver = Driver::find($request->driver_id);
        $data['name'] = optional($driver)->name . ' - ' . Carbon::parse($request->start_date)->format('Y-m-d');

        $fromLocations = $request->input('from_location_id', []);
        $selectedDays  = $request->input('days', []);
        $selectedHours = $request->input('visit_hours', []);

        // Each selected from-location must have a visit hour.
        foreach ($fromLocations as $fromLocationId) {
            if (!isset($selectedHours[$fromLocationId]) || empty($selectedHours[$fromLocationId])) {
                return back()->withErrors(['visit_hours' => 'Each from location must have a visit hour']);
            }
        }
        if (empty($selectedDays)) {
            return back()->withErrors(['days' => 'Select at least one day']);
        }

        $parent_id = null;
        foreach ($fromLocations as $fromLocationId) {
            foreach ($selectedDays as $selectedDay) {
                $scheduledTask = new ScheduledTask($data);
                $scheduledTask->parent_id       = $parent_id;
                $scheduledTask->from_location_id = $fromLocationId;
                $scheduledTask->day             = $selectedDay;
                $scheduledTask->selected_hour   = $selectedHours[$fromLocationId];
                $scheduledTask->save();
                if (is_null($parent_id)) {
                    $parent_id = $scheduledTask->id;
                }
            }
        }

        return redirect()->route('app.admin.scheduled-tasks.index')->with('success', 'Scheduled task(s) created successfully.');
    }

    /** Add Quick Schedule Task page. */
    public function quick()
    {
        abort_if(Gate::denies('scheduled_task_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return Inertia::render('Tasks/ScheduledTaskQuick', ['options' => $this->formOptions()]);
    }

    /**
     * Store quick scheduled task(s). MIRRORS Admin\ScheduledTaskController@quickAction —
     * single from_location, one task per (day × hour), parent-child linked.
     */
    public function quickAction(StoreScheduledTaskRequest $request)
    {
        $data = $request->except(['from_location_id', 'days', 'visit_hours']);
        $fromLocationId = $request->input('from_location_id');
        $selectedDays   = $request->input('days', []);
        $selectedHours  = $request->input('visit_hours', []);

        if (count($selectedDays) * count($selectedHours) === 0) {
            return back()->withErrors(['general' => 'You must select at least one day and one visit hour.']);
        }

        $parent_id = null;
        foreach ($selectedDays as $selectedDay) {
            foreach ($selectedHours as $selectedHour) {
                $scheduledTask = new ScheduledTask($data);
                $scheduledTask->parent_id       = $parent_id;
                $scheduledTask->from_location_id = $fromLocationId;
                $scheduledTask->day             = $selectedDay;
                $scheduledTask->selected_hour   = $selectedHour;
                $scheduledTask->save();
                if (empty($parent_id)) {
                    $parent_id = $scheduledTask->id;
                }
            }
        }

        return redirect()->route('app.admin.scheduled-tasks.index')->with('success', 'Scheduled task(s) created successfully.');
    }
}
