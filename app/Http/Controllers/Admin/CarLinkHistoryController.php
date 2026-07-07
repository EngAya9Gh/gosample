<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyCarLinkHistoryRequest;
use App\Http\Requests\StoreCarLinkHistoryRequest;
use App\Http\Requests\UpdateCarLinkHistoryRequest;
use App\Models\Car;
use App\Models\CarLinkHistory;
use App\Models\Driver;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CarLinkHistoryController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('car_link_history_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (str_starts_with($request->path(), 'app/')) {
            $query = CarLinkHistory::with(['driver', 'car']);

            // Filtering
            if ($request->filled('keyword')) {
                $keyword = $request->keyword;
                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('driver', function ($q2) use ($keyword) {
                        $q2->where('name', 'like', "%{$keyword}%");
                    })->orWhereHas('car', function ($q2) use ($keyword) {
                        $q2->where('imei', 'like', "%{$keyword}%")
                          ->orWhere('plate_number', 'like', "%{$keyword}%");
                    });
                });
            }

            if ($request->filled('action')) {
                $query->where('action', $request->action);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Sorting
            $sortBy = $request->get('sortBy', 'id');
            $sortOrder = $request->get('sortOrder', 'desc');
            $allowedSorts = ['id', 'created_at'];

            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('id', 'desc');
            }

            // Pagination
            $pageSize = $request->get('pageSize', 25);
            $paginator = $query->paginate($pageSize);

            // If it's an AJAX request (from axios in the Vue component)
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json([
                    'rows' => $paginator->items(),
                    'total' => $paginator->total(),
                ]);
            }

            // Initial page load
            return \Inertia\Inertia::render('CarLinkHistories/CarLinkHistoriesList', [
                'initialRows' => $paginator->items(),
                'initialTotal' => $paginator->total(),
            ]);
        }

        $carLinkHistories = CarLinkHistory::with(['driver', 'car'])->orderBy('id', 'desc')->get();


        return view('admin.carLinkHistories.index', compact('carLinkHistories'));
    }

    public function create()
    {
        abort_if(Gate::denies('car_link_history_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

        $cars = Car::pluck('imei', 'id')->prepend(trans('translation.pleaseSelect'), '');

        return view('admin.carLinkHistories.create', compact('cars', 'drivers'));
    }

    public function store(StoreCarLinkHistoryRequest $request)
    {
        $carLinkHistory = CarLinkHistory::create($request->all());

        return redirect()->route('admin.car-link-histories.index');
    }

    public function edit(CarLinkHistory $carLinkHistory)
    {
        abort_if(Gate::denies('car_link_history_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

        $cars = Car::pluck('imei', 'id')->prepend(trans('translation.pleaseSelect'), '');

        $carLinkHistory->load('driver', 'car');

        return view('admin.carLinkHistories.edit', compact('carLinkHistory', 'cars', 'drivers'));
    }

    public function update(UpdateCarLinkHistoryRequest $request, CarLinkHistory $carLinkHistory)
    {
        $carLinkHistory->update($request->all());

        return redirect()->route('admin.car-link-histories.index');
    }

    public function show(CarLinkHistory $carLinkHistory)
    {
        abort_if(Gate::denies('car_link_history_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $carLinkHistory->load('driver', 'car');

        return view('admin.carLinkHistories.show', compact('carLinkHistory'));
    }

    public function destroy(CarLinkHistory $carLinkHistory)
    {
        $this->authorize('can-delete');

        $carLinkHistory->delete();

        return back();
    }

    public function massDestroy(MassDestroyCarLinkHistoryRequest $request)
    {
        $this->authorize('can-delete');
        CarLinkHistory::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
