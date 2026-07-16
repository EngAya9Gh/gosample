<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyLocationRequest;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\ClientLocation;
use App\Models\Location;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class LocationsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('location_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $logged_id_user = auth()->user();

        $query = Location::withoutGlobalScope('enabled')->with(['createdBy', 'updatedBy']);

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('arabic_name', 'like', "%{$keyword}%")
                  ->orWhere('city', 'like', "%{$keyword}%")
                  ->orWhere('neighborhood', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if (!empty($logged_id_user->assigned_client_ids)) {
            $query->whereHas('locationsClients', function ($q) use ($logged_id_user) {
                $q->whereIn('clients.id', $logged_id_user->assigned_client_ids);
            });
        }

        $pageSize = $request->get('pageSize', 25);
        $paginator = $query->orderBy('id', 'desc')->paginate($pageSize);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'rows' => $paginator->items(),
                'total' => $paginator->total(),
            ]);
        }

        return \Inertia\Inertia::render('Locations/LocationsList', [
            'initialRows' => $paginator->items(),
            'initialTotal' => $paginator->total(),
            'saudiCities' => \App\Models\Location::SAUDI_CITIES
        ]);
    }

    public function create()
    {
        abort_if(Gate::denies('location_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.locations.create');
    }

    public function store(StoreLocationRequest $request)
    {
        $location = Location::create($request->all());

        $logged_id_user = auth()->user();
        if (!empty($logged_id_user->assigned_client_ids)) {
            foreach ($logged_id_user->assigned_client_ids as $c_id) {
                $clientLocation = new \App\Models\ClientLocation();
                $clientLocation->client_id = $c_id;
                $clientLocation->location_id = $location->id;
                $clientLocation->save();
            }
        }
            return redirect()->route('admin.locations.index');
    }

    public function edit($id)
    {
        abort_if(Gate::denies('location_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $location = Location::withoutGlobalScope('enabled')->findOrFail($id);
        return view('admin.locations.edit', compact('location'));
    }

    public function update(UpdateLocationRequest $request, $id)
    {
        $location = Location::withoutGlobalScope('enabled')->findOrFail($id);
        $location->update($request->all());
            return redirect()->route('admin.locations.index');
    }

    public function show($id)
    {
        abort_if(Gate::denies('location_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $location = Location::withoutGlobalScope('enabled')->findOrFail($id);
        $location->load('locationsClients');

        return view('admin.locations.show', compact('location'));
    }

    public function destroy($id, Request $request)
    {
        $this->authorize('can-delete');

        $location = Location::withoutGlobalScope('enabled')->findOrFail($id);
        $location->delete();
            return back();

        return back();
    }

    public function massDestroy(MassDestroyLocationRequest $request)
    {
        $this->authorize('can-delete');
        $locations = Location::find(request('ids'));

        foreach ($locations as $location) {
            $location->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
