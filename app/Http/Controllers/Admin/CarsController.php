<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyCarRequest;
use App\Http\Requests\StoreCarRequest;
use App\Http\Requests\UpdateCarRequest;
use App\Models\Car;
use App\Models\Driver;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class CarsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('car_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = Car::withoutGlobalScope('enabled')->with(['driver'])->select(sprintf('%s.*', (new Car)->table));

        if ($request->filled('keyword')) {
            $query->where(function($q) use ($request) {
                $q->where('imei', 'like', '%' . $request->keyword . '%')
                  ->orWhere('plate_number', 'like', '%' . $request->keyword . '%')
                  ->orWhereHas('driver', function($dq) use ($request) {
                      $dq->where('name', 'like', '%' . $request->keyword . '%');
                  });
            });
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }
        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }
        if ($request->filled('imei')) {
            $query->where('imei', $request->imei);
        }
        if ($request->filled('plate_number')) {
            $query->where('plate_number', $request->plate_number);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sortBy = $request->input('sortBy', 'id');
        $sortOrder = $request->input('sortOrder', 'desc');
        $pageSize = max(1, min((int) $request->input('pageSize', 25), 100));
        
        $query->orderBy($sortBy, $sortOrder);
        
        $page = max(1, (int) $request->input('page', 1));
        $total = (clone $query)->count();
        $offset = ($page - 1) * $pageSize;

        $rows = $query->offset($offset)->limit($pageSize)->get()->map(function ($car) {
            return [
                'id' => $car->id,
                'driver_id' => $car->driver_id,
                'driver_name' => $car->driver ? $car->driver->name : null,
                'driver_mobile' => $car->driver ? $car->driver->mobile : null,
                'imei' => $car->imei,
                'plate_number' => $car->plate_number,
                'model' => $car->model,
                'color' => $car->color,
                'contact_person' => $car->contact_person,
                'afaqi' => $car->afaqi,
                'description' => $car->description,
                'status' => $car->status, // 1: enabled, 2: disabled
                'created_at' => $car->created_at ? $car->created_at->format('Y-m-d H:i') : null,
            ];
        });

        if ($request->wantsJson()) {
            return response()->json([
                'rows' => $rows,
                'total' => $total
            ]);
        }

        // Inertia SPA Route Fallback (Initial Page Load)
        if (str_starts_with($request->path(), 'app/')) {
            return \Inertia\Inertia::render('Cars/CarsList', [
                'initialRows' => $rows,
                'initialTotal' => $total,
                'can' => [
                    'car_create' => Gate::allows('car_create'),
                    'car_edit'   => Gate::allows('car_edit'),
                    'car_show'   => Gate::allows('car_show'),
                    'car_delete' => Gate::allows('car_delete'),
                ],
                'filters' => [
                    'drivers' => Driver::select('id as value', 'name as label')->get(),
                ]
            ]);
        }

        // Classic Blade Fallback
        if ($request->ajax()) { // Just to keep DataTables working for the old view if it still hits here
            $query = Car::withoutGlobalScope('enabled')->with(['driver'])->select(sprintf('%s.*', (new Car)->table));
            $table = Datatables::of($query);
            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');
            $table->editColumn('actions', function ($row) {
                $viewGate      = 'car_show';
                $editGate      = 'car_edit';
                $deleteGate    = 'car_delete';
                $crudRoutePart = 'cars';
                return view('partials.datatablesActions', compact('viewGate', 'editGate', 'deleteGate', 'crudRoutePart', 'row'));
            });
            return $table->make(true);
        }

        return view('admin.cars.index');
    }

    public function create(Request $request)
    {
        abort_if(Gate::denies('car_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::select('id as value', 'name as label')->get();

        if (str_starts_with($request->path(), 'app/')) {
            return \Inertia\Inertia::render('Cars/CarForm', [
                'drivers' => $drivers,
            ]);
        }

        $driversMap = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        return view('admin.cars.create', ['drivers' => $driversMap]);
    }

    public function store(StoreCarRequest $request)
    {
        // Check if a soft-deleted record exists with the same IMEI
        $existing = Car::withTrashed()
            ->where('imei', $request->imei)
            ->first();

        if ($existing) {
            // Modify the soft-deleted IMEI
            $existing->imei = $existing->imei . '_delete';
            $existing->save();
        }
        $car = Car::create($request->all());

        if (str_starts_with($request->path(), 'app/')) {
            return redirect()->route('app.admin.cars.index')->with('success', 'Car created successfully.');
        }

        return redirect()->route('admin.cars.index');
    }

    public function edit(Request $request, $id)
    {
        abort_if(Gate::denies('car_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $car = Car::withoutGlobalScope('enabled')->findOrFail($id);
        $car->load('driver');
        
        $drivers = Driver::select('id as value', 'name as label')->get();

        if (str_starts_with($request->path(), 'app/')) {
            return \Inertia\Inertia::render('Cars/CarForm', [
                'car' => $car,
                'drivers' => $drivers,
            ]);
        }

        $driversMap = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        return view('admin.cars.edit', ['car' => $car, 'drivers' => $driversMap]);
    }

    public function update(UpdateCarRequest $request, $id)
    {
        $car = Car::withoutGlobalScope('enabled')->findOrFail($id);
        $oldDriverId = $car->driver_id;
        $newDriverId = $request->driver_id;

        $existing = Car::withoutGlobalScope('enabled')->withTrashed()
            ->where('imei', $request->imei)
            ->where('id', '!=', $car->id)
            ->first();

        if ($existing) {
            $existing->imei = $existing->imei . '_delete';
            $existing->save();
        }

        $car->update($request->all());

        if ($oldDriverId != $newDriverId) {
            $message = '';

            if ($newDriverId) {
                $previousCars = Car::withoutGlobalScope('enabled')
                    ->where('driver_id', $newDriverId)
                    ->where('id', '!=', $car->id)
                    ->get();

                foreach ($previousCars as $prevCar) {
                    $prevCar->driver_id = null;
                    $prevCar->save();

                    \App\Models\CarLinkHistory::create([
                        'car_id' => $prevCar->id,
                        'driver_id' => $newDriverId,
                        'action' => 'unlinked'
                    ]);

                    $identifier = $prevCar->imei ?? $prevCar->plate_number ?? $prevCar->id;
                    $message .= "تم فصل ارتباط السائق بسيارته السابقة ($identifier) تلقائياً. ";
                }
            }

            if ($oldDriverId) {
                \App\Models\CarLinkHistory::create([
                    'car_id' => $car->id,
                    'driver_id' => $oldDriverId,
                    'action' => 'unlinked'
                ]);
                $oldDriver = \App\Models\Driver::find($oldDriverId);
                $oldDriverName = $oldDriver ? $oldDriver->name : 'غير معروف';
                $message .= 'تم فصل الارتباط تلقائياً عن السائق السابق: (' . $oldDriverName . '). ';
            }
            if ($newDriverId) {
                \App\Models\CarLinkHistory::create([
                    'car_id' => $car->id,
                    'driver_id' => $newDriverId,
                    'action' => 'linked'
                ]);
                $message .= 'تم ربط السيارة بالسائق الجديد بنجاح.';
            }

            if ($message) {
                if (str_starts_with($request->path(), 'app/')) {
                    // back(): the SPA edits via popup from either the list or the
                    // car-details page — return to whichever page sent the PUT.
                    return redirect()->back()->with('success', $message);
                }
                return redirect()->route('admin.cars.index')->with('success', $message);
            }
        }

        if (str_starts_with($request->path(), 'app/')) {
            return redirect()->back()->with('success', 'Car updated successfully.');
        }

        return redirect()->route('admin.cars.index');
    }

    public function show(Request $request, $id)
    {
        abort_if(Gate::denies('car_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $car = Car::withoutGlobalScope('enabled')->findOrFail($id);

        $car->load(['driver', 'carCarLinkHistories.driver', 'carTasks.from', 'carTasks.to', 'carTasks.client', 'carTasks.driver', 'carTracking', 'containers', 'media']);

        if (str_starts_with($request->path(), 'app/')) {
            $mediaUrls = [];
            $directions = ['signature', 'image_front', 'image_back', 'image_right', 'image_left', 'image_inside1', 'image_inside2'];
            foreach($directions as $dir) {
                if ($car->hasMedia($dir)) {
                    $mediaUrls[$dir] = asset($car->firstMedia($dir)->getDiskPath());
                }
            }

            // Same list as the classic containers/create form: Car::pluck with
            // the 'enabled' global scope applied (enabled cars only).
            $cars = Car::select('id', 'plate_number')->get()
                ->map(fn ($c) => ['value' => $c->id, 'label' => $c->plate_number])->values();

            return \Inertia\Inertia::render('Cars/CarView', [
                'car' => $car,
                'mediaUrls' => $mediaUrls,
                'cars' => $cars,
                'drivers' => Driver::select('id as value', 'name as label')->get(),
                'can' => [
                    'car_edit'         => Gate::allows('car_edit'),
                    'container_create' => Gate::allows('container_create'),
                    'container_edit'   => Gate::allows('container_edit'),
                ],
            ]);
        }

        return view('admin.cars.show', compact('car'));
    }

    public function destroy($id)
    {
        $this->authorize('can-delete');

        $car = Car::withoutGlobalScope('enabled')->findOrFail($id);

        $car->delete();

        return back();
    }

    public function massDestroy(MassDestroyCarRequest $request)
    {
        $this->authorize('can-delete');
        $cars = Car::find(request('ids'));

        foreach ($cars as $car) {
            $car->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
