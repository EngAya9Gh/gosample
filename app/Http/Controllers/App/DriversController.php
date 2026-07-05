<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Task;
use Carbon\Carbon;
use Gate;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Models\Zone;
use App\Models\ShiftTemplate;
use App\Models\DriverShift;

/**
 * Inertia page controller for the new Drivers screen (/app/admin/drivers).
 * MIRRORS App\Http\Controllers\Admin\DriversController@index
 */
class DriversController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('driver_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $sortColumn = $request->input('sort_by');
        if (!in_array($sortColumn, ['id', 'created_at', 'name', 'status', 'username', 'mobile', 'email'], true)) {
            $sortColumn = 'id';
        }
        $sortOrder = $request->input('sort_order', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'desc';
        }
        $query = Driver::withoutGlobalScope('enabled')->select('drivers.*')->withCount('tasks');
        $query->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->mobile, fn ($q, $v) => $q->where('mobile', 'like', "%{$v}%"))
            ->when($request->keyword, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('name', 'like', "%{$v}%")
                        ->orWhere('username', 'like', "%{$v}%")
                        ->orWhere('email', 'like', "%{$v}%")
                        ->orWhere('id', $v);
                });
            });

        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : null;
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : null;
        
        if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }
        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom->toDateTimeString(), $dateTo->toDateTimeString()]);
        } elseif ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        $query->orderBy($sortColumn, $sortOrder);

        $pageSize = max(1, min((int) $request->input('pageSize', 25), 1000));
        $page = max(1, (int) $request->input('page', 1));
        $total = (clone $query)->toBase()->getCountForPagination();
        $offset = ($page - 1) * $pageSize;

        $rows = $query->offset($offset)->limit($pageSize)->get()->map(function (Driver $d) {
            return [
                'id'       => $d->id,
                'name'     => $d->name,
                'status'   => $d->status,
                'username' => $d->username,
                'mobile'   => $d->mobile,
                'email'    => $d->email,
                'tasks_count' => $d->tasks_count,
            ];
        });

        $statuses = [
            ['value' => '1', 'label' => 'Enabled'],
            ['value' => '2', 'label' => 'Disabled'],
        ];

        return Inertia::render('Drivers/DriversList', [
            'drivers' => [
                'data'     => $rows,
                'total'    => $total,
                'page'     => $page,
                'pageSize' => $pageSize,
            ],
            'filters' => [
                'keyword'   => $request->keyword ?? '',
                'status'    => $request->status ?? '',
                'mobile'    => $request->mobile ?? '',
                'date_from' => $request->date_from ?? '',
                'date_to'   => $request->date_to ?? '',
                'sort_by'   => $sortColumn,
                'sort_order'=> $sortOrder,
            ],
            'options' => [
                'statuses' => $statuses,
            ],
            // Feed the Add/Edit driver modals (fields live in DriverFormFields.vue).
            'zones' => Zone::select('id as value', 'name as label')->get(),
            'shiftTemplates' => ShiftTemplate::all(),
        ]);
    }

    /**
     * Full editable driver record for the SPA Edit modal (list rows are summaries).
     * Returns the same model the classic edit() feeds to the form.
     */
    public function editData($id)
    {
        abort_if(Gate::denies('driver_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return response()->json(
            Driver::withoutGlobalScope('enabled')->findOrFail($id)
        );
    }

    public function create()
    {
        abort_if(Gate::denies('driver_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $zones = Zone::select('id as value', 'name as label')->get();
        $shiftTemplates = ShiftTemplate::all();
        
        return Inertia::render('Drivers/DriverForm', [
            'driver' => null,
            'zones' => $zones,
            'shiftTemplates' => $shiftTemplates,
        ]);
    }

    public function store(StoreDriverRequest $request)
    {
        $driver = Driver::create($request->all());
        $this->syncDriverShifts($driver, $request);

        return redirect()->route('app.admin.drivers.index')->with('success', 'Driver created successfully.');
    }

    public function edit($id)
    {
        abort_if(Gate::denies('driver_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $driver = Driver::withoutGlobalScope('enabled')->findOrFail($id);
        $zones = Zone::select('id as value', 'name as label')->get();
        $shiftTemplates = ShiftTemplate::all();

        return Inertia::render('Drivers/DriverForm', [
            'driver' => $driver,
            'zones' => $zones,
            'shiftTemplates' => $shiftTemplates,
        ]);
    }

    public function update(UpdateDriverRequest $request, $id)
    {
        $driver = Driver::withoutGlobalScope('enabled')->findOrFail($id);
        
        if ($request->filled('password')) {
            $driver->update($request->all());
        } else {
            $driver->update($request->except('password'));
        }
        
        $this->syncDriverShifts($driver, $request);

        return redirect()->route('app.admin.drivers.index')->with('success', 'Driver updated successfully.');
    }

    public function show($id)
    {
        abort_if(Gate::denies('driver_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $driver = Driver::withoutGlobalScope('enabled')
            ->with(['zone', 'driverTasks', 'driverCarLinkHistories'])
            ->findOrFail($id);

        // Pre-calculate shift hours to pass neatly
        $driver->load(['shifts' => function($q) {
            $q->where('is_active', true);
        }]);

        $driver->append(['punctuality_score', 'shift_completion_score']);

        return Inertia::render('Drivers/DriverDetails', [
            'driver' => $driver
        ]);
    }

    public function destroy($id)
    {
        abort_if(Gate::denies('driver_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        
        $driver = Driver::withoutGlobalScope('enabled')->findOrFail($id);
        $driver->delete();

        return redirect()->back()->with('success', 'Driver deleted successfully.');
    }

    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies('driver_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        
        Driver::withoutGlobalScope('enabled')->whereIn('id', request('ids'))->delete();

        return redirect()->back()->with('success', 'Drivers deleted successfully.');
    }

    private function syncDriverShifts(Driver $driver, Request $request)
    {
        // 1. Deactivate all previous active shifts for this driver
        DriverShift::where('driver_id', $driver->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'valid_to' => now()->toDateString()
            ]);

        $days = ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"];
        $shiftCount = $request->input('shift_count', 1);

        // 2. Create Shift 1 (Always exists if start/end filled)
        if ($request->filled('working_hours_start') && $request->filled('working_hours_end')) {
            DriverShift::create([
                'driver_id' => $driver->id,
                'shift_number' => 1,
                'start_time' => $request->working_hours_start,
                'end_time' => $request->working_hours_end,
                'days' => $days,
                'valid_from' => now()->toDateString(),
                'is_active' => true,
            ]);
        }

        // 3. Create Shift 2 if count is 2 or more
        if ($shiftCount >= 2 && $request->filled('second_shift_working_hours_start') && $request->filled('second_shift_working_hours_end')) {
            DriverShift::create([
                'driver_id' => $driver->id,
                'shift_number' => 2,
                'start_time' => $request->second_shift_working_hours_start,
                'end_time' => $request->second_shift_working_hours_end,
                'days' => $days,
                'valid_from' => now()->toDateString(),
                'is_active' => true,
            ]);
        }
        
        // 4. Create Shift 3 if count is 3
        if ($shiftCount >= 3 && $request->filled('third_shift_working_hours_start') && $request->filled('third_shift_working_hours_end')) {
            DriverShift::create([
                'driver_id' => $driver->id,
                'shift_number' => 3,
                'start_time' => $request->third_shift_working_hours_start,
                'end_time' => $request->third_shift_working_hours_end,
                'days' => $days,
                'valid_from' => now()->toDateString(),
                'is_active' => true,
            ]);
        }
    }

    public function showTasks($driverId)
    {
        abort_if(Gate::denies('driver_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $driver = Driver::findOrFail($driverId);
        $tasks = $driver->activeTasks()
            ->select(
                'tasks.id',
                'tasks.to_location',
                'tasks.eta',
                'tasks.poririty',
                'from_locations.name as from_location_name',
                'to_locations.name as to_location_name'
            )
            ->leftJoin('locations as from_locations', 'from_locations.id', '=', 'tasks.from_location')
            ->leftJoin('locations as to_locations', 'to_locations.id', '=', 'tasks.to_location')
            ->orderBy('tasks.poririty')
            ->get();

        return Inertia::render('Drivers/DriverTasks', [
            'driver' => [
                'id' => $driver->id,
                'name' => $driver->name,
            ],
            'tasks' => $tasks->map(fn($t) => [
                'id' => $t->id,
                'from_location_name' => $t->from_location_name,
                'to_location_name' => $t->to_location_name,
                'eta' => $t->eta,
                'poririty' => $t->poririty,
            ]),
        ]);
    }

    public function reorderTasks(Request $request, $driverId)
    {
        abort_if(Gate::denies('driver_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        DB::transaction(function () use ($request, $driverId) {
            $order = $request->input('order', []);

            // 1. Update Priorities
            foreach ($order as $item) {
                Task::where('id', $item['id'])
                    ->update(['poririty' => $item['priority']]);
            }

            // 2. Recalculate ETA (in background)
            dispatch(new \App\Jobs\CalculateDriverETAJob($driverId))->afterResponse();
        });

        return redirect()->back()->with('success', 'Task order updated successfully.');
    }

    public function smartSortTasks(Request $request, $driverId)
    {
        abort_if(Gate::denies('driver_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // This assumes App\Services\DriverRouteService exists and does the heavy lifting
        app(\App\Services\DriverRouteService::class)->smartSortTasks($driverId);

        return redirect()->back()->with('success', 'Tasks smart-sorted successfully.');
    }
}
