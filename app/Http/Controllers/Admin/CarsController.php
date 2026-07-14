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

class CarsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('car_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = Car::withoutGlobalScope('enabled')->with(['driver'])->select(sprintf('%s.*', (new Car)->table));

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('imei', 'like', '%' . $request->keyword . '%')
                  ->orWhere('plate_number', 'like', '%' . $request->keyword . '%')
                  ->orWhereHas('driver', function ($dq) use ($request) {
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

        $sortBy    = $request->input('sortBy', 'id');
        $sortOrder = $request->input('sortOrder', 'desc');
        $pageSize  = max(1, min((int) $request->input('pageSize', 25), 100));

        $query->orderBy($sortBy, $sortOrder);

        $page   = max(1, (int) $request->input('page', 1));
        $total  = (clone $query)->count();
        $offset = ($page - 1) * $pageSize;

        $rows = $query->offset($offset)->limit($pageSize)->get()->map(fn ($car) => [
            'id'            => $car->id,
            'driver_id'     => $car->driver_id,
            'driver_name'   => $car->driver?->name,
            'driver_mobile' => $car->driver?->mobile,
            'imei'          => $car->imei,
            'plate_number'  => $car->plate_number,
            'model'         => $car->model,
            'color'         => $car->color,
            'contact_person'=> $car->contact_person,
            'afaqi'         => $car->afaqi,
            'description'   => $car->description,
            'status'        => $car->status,
            'created_at'    => $car->created_at?->format('Y-m-d H:i'),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['rows' => $rows, 'total' => $total]);
        }

        return \Inertia\Inertia::render('Cars/CarsList', [
            'initialRows'  => $rows,
            'initialTotal' => $total,
            'can'          => [
                'car_create' => Gate::allows('car_create'),
                'car_edit'   => Gate::allows('car_edit'),
                'car_show'   => Gate::allows('car_show'),
                'car_delete' => Gate::allows('car_delete'),
            ],
            'filters'      => [
                'drivers' => Driver::select('id as value', 'name as label')->get(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        abort_if(Gate::denies('car_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::select('id as value', 'name as label')->get();

        return \Inertia\Inertia::render('Cars/CarForm', [
            'drivers' => $drivers,
        ]);
    }

    public function store(StoreCarRequest $request)
    {
        // If a soft-deleted record exists with the same IMEI, rename it first.
        $existing = Car::withTrashed()->where('imei', $request->imei)->first();
        if ($existing) {
            $existing->imei = $existing->imei . '_delete';
            $existing->save();
        }

        Car::create($request->all());

        return redirect()->route('admin.cars.index')->with('success', 'Car created successfully.');
    }

    public function edit(Request $request, $id)
    {
        abort_if(Gate::denies('car_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $car     = Car::withoutGlobalScope('enabled')->findOrFail($id);
        $car->load('driver');
        $drivers = Driver::select('id as value', 'name as label')->get();

        return \Inertia\Inertia::render('Cars/CarForm', [
            'car'     => $car,
            'drivers' => $drivers,
        ]);
    }

    public function update(UpdateCarRequest $request, $id)
    {
        $car         = Car::withoutGlobalScope('enabled')->findOrFail($id);
        $oldDriverId = $car->driver_id;
        $newDriverId = $request->driver_id;

        // If another (possibly soft-deleted) car has the same IMEI, rename it.
        $duplicate = Car::withoutGlobalScope('enabled')->withTrashed()
            ->where('imei', $request->imei)
            ->where('id', '!=', $car->id)
            ->first();

        if ($duplicate) {
            $duplicate->imei = $duplicate->imei . '_delete';
            $duplicate->save();
        }

        $car->update($request->all());

        // Handle driver-link history when the assigned driver changes.
        if ($oldDriverId !== $newDriverId) {
            $message = '';

            if ($newDriverId) {
                // Unlink the new driver from any other car he/she was assigned to.
                Car::withoutGlobalScope('enabled')
                    ->where('driver_id', $newDriverId)
                    ->where('id', '!=', $car->id)
                    ->get()
                    ->each(function ($prevCar) use ($newDriverId, &$message) {
                        $prevCar->driver_id = null;
                        $prevCar->save();

                        \App\Models\CarLinkHistory::create([
                            'car_id'    => $prevCar->id,
                            'driver_id' => $newDriverId,
                            'action'    => 'unlinked',
                        ]);

                        $identifier = $prevCar->imei ?? $prevCar->plate_number ?? $prevCar->id;
                        $message .= "تم فصل ارتباط السائق بسيارته السابقة ({$identifier}) تلقائياً. ";
                    });
            }

            if ($oldDriverId) {
                \App\Models\CarLinkHistory::create([
                    'car_id'    => $car->id,
                    'driver_id' => $oldDriverId,
                    'action'    => 'unlinked',
                ]);
                $oldDriverName = \App\Models\Driver::find($oldDriverId)?->name ?? 'غير معروف';
                $message .= "تم فصل الارتباط تلقائياً عن السائق السابق: ({$oldDriverName}). ";
            }

            if ($newDriverId) {
                \App\Models\CarLinkHistory::create([
                    'car_id'    => $car->id,
                    'driver_id' => $newDriverId,
                    'action'    => 'linked',
                ]);
                $message .= 'تم ربط السيارة بالسائق الجديد بنجاح.';
            }

            if ($message) {
                return redirect()->back()->with('success', $message);
            }
        }

        return redirect()->back()->with('success', 'Car updated successfully.');
    }

    public function show(Request $request, $id)
    {
        abort_if(Gate::denies('car_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $car = Car::withoutGlobalScope('enabled')->findOrFail($id);
        $car->load(['driver', 'carCarLinkHistories.driver', 'carTasks.from', 'carTasks.to', 'carTasks.client', 'carTasks.driver', 'carTracking', 'containers', 'media']);

        $mediaUrls  = [];
        $directions = ['signature', 'image_front', 'image_back', 'image_right', 'image_left', 'image_inside1', 'image_inside2'];

        foreach ($directions as $dir) {
            try {
                if ($car->hasMedia($dir) && ($media = $car->firstMedia($dir))) {
                    $mediaUrls[$dir] = asset($media->getDiskPath());
                }
            } catch (\Exception) {
                // Media storage unavailable; skip.
            }
        }

        $cars = Car::select('id', 'plate_number')->get()
            ->map(fn ($c) => ['value' => $c->id, 'label' => $c->plate_number])
            ->values();

        return \Inertia\Inertia::render('Cars/CarView', [
            'car'       => $car,
            'mediaUrls' => $mediaUrls,
            'cars'      => $cars,
            'drivers'   => Driver::select('id as value', 'name as label')->get(),
            'can'       => [
                'car_edit'         => Gate::allows('car_edit'),
                'container_create' => Gate::allows('container_create'),
                'container_edit'   => Gate::allows('container_edit'),
            ],
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('can-delete');

        Car::withoutGlobalScope('enabled')->findOrFail($id)->delete();

        return back();
    }

    public function massDestroy(MassDestroyCarRequest $request)
    {
        $this->authorize('can-delete');

        Car::find(request('ids'))->each->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
