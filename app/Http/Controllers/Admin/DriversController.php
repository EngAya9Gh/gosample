<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyDriverRequest;
use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Models\Driver;
use App\Models\Zone;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;
use App\Models\ShiftTemplate;

class DriversController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('driver_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = Driver::withoutGlobalScope('enabled')
            ->select(sprintf('%s.*', (new Driver)->getTable()));
            // Apply search criteria
            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
            }
            if ($request->filled('mobile')) {
                $query->where('mobile', $request->mobile);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
           
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $editUrl = route('admin.drivers.edit', $row->id);
                
                $buttons = "";
                
                if (Gate::allows('driver_edit')) {
                    $buttons .= '<a class="btn btn-xs btn-info shadow-sm mr-1" href="' . $editUrl . '" title="Edit"><i class="ri-edit-line text-white"></i> Edit</a>';
                }
                
                return '<div class="text-nowrap">' . $buttons . '</div>';
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->editColumn('name', function ($row) {
                return $row->name ? $row->name : '';
            });
            $table->editColumn('status', function ($row) {
                return $row->status ? Driver::STATUS_SELECT[$row->status] : '';
            });
            $table->editColumn('username', function ($row) {
                return $row->username ? $row->username : '';
            });
            $table->editColumn('mobile', function ($row) {
                return $row->mobile ? $row->mobile : '';
            });
            $table->editColumn('email', function ($row) {
                return $row->email ? $row->email : '';
            });
            $table->editColumn('language', function ($row) {
                return $row->language ? Driver::LANGUAGE_SELECT[$row->language] : '';
            });
            $table->editColumn('lat', function ($row) {
                return $row->lat ? $row->lat : '';
            });
            $table->editColumn('lng', function ($row) {
                return $row->lng ? $row->lng : '';
            });
            $table->editColumn('accepted_terms', function ($row) {
                return $row->accepted_terms ? $row->accepted_terms : '';
            });

            $table->rawColumns(['actions', 'placeholder']);

            return $table->make(true);
        }

        return view('admin.drivers.index');
    }

    public function create()
    {
        abort_if(Gate::denies('driver_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $zones = Zone::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $shiftTemplates = ShiftTemplate::all();
        return view('admin.drivers.create', compact('zones', 'shiftTemplates'));
    }

    public function store(StoreDriverRequest $request)
    {
        $driver = Driver::create($request->all());
        $this->syncDriverShifts($driver, $request);

        return redirect()->route('admin.drivers.index');
    }

    public function edit($id)
    {
        abort_if(Gate::denies('driver_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
// 
        // $zones = Zone::all();
        $driver = Driver::withoutGlobalScope('enabled')->findOrFail($id);
        $zones = Zone::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $shiftTemplates = ShiftTemplate::all();

        return view('admin.drivers.edit', compact('driver','zones', 'shiftTemplates'));
    }

    public function update(UpdateDriverRequest $request, $id)
    {
        $driver = Driver::withoutGlobalScope('enabled')->findOrFail($id);
        
        \Log::info('Driver Update Payload for Driver ID ' . $id . ':', $request->all());
        \Log::info('Driver current attributes before update:', $driver->toArray());

        $driver->update($request->all());
        
        \Log::info('Driver attributes after update:', $driver->fresh()->toArray());
        
        $this->syncDriverShifts($driver, $request);

        return redirect()->route('admin.drivers.index');
    }

    private function syncDriverShifts(Driver $driver, Request $request)
    {
        // 1. Deactivate all previous active shifts for this driver
        \App\Models\DriverShift::where('driver_id', $driver->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'valid_to' => now()->toDateString()
            ]);

        $days = ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"];
        $shiftCount = $request->input('shift_count', 1);

        // 2. Create Shift 1 (Always exists if start/end filled)
        if ($request->filled('working_hours_start') && $request->filled('working_hours_end')) {
            \App\Models\DriverShift::create([
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
            \App\Models\DriverShift::create([
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
            \App\Models\DriverShift::create([
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

    public function show($id)
    {
        abort_if(Gate::denies('driver_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $driver = Driver::withoutGlobalScope('enabled')->findOrFail($id);
        $driver->load('driverCarLinkHistories', 'driverTasks');

        return view('admin.drivers.show', compact('driver'));
    }

    public function destroy($id)
    {
        $this->authorize('can-delete');

        $driver = Driver::withoutGlobalScope('enabled')->findOrFail($id);
        $driver->delete();

        return back();
    }

    public function massDestroy(MassDestroyDriverRequest $request)
    {
        $this->authorize('can-delete');
        $drivers = Driver::find(request('ids'));

        foreach ($drivers as $driver) {
            $driver->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function getShifts($id)
    {
        $driver = Driver::with('shifts')->findOrFail($id);
        $shifts = $driver->shifts()->where('is_active', true)->get();
        return response()->json($shifts);
    }
}
