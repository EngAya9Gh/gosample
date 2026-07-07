<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyClientRequest;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Location;
use App\Models\Driver;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;

class ClientsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('client_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (str_starts_with($request->path(), 'app/')) {
            $query = Client::query();

            $logged_id_user = auth()->user();
            if ($logged_id_user->client_id !== null) {
                $query->whereIn('id', $logged_id_user->assigned_client_ids);
            }

            if ($request->filled('keyword')) {
                $keyword = $request->keyword;
                $query->where(function($q) use ($keyword) {
                    $q->where('arabic_name', 'like', "%{$keyword}%")
                      ->orWhere('english_name', 'like', "%{$keyword}%")
                      ->orWhere('email', 'like', "%{$keyword}%")
                      ->orWhere('address', 'like', "%{$keyword}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('location_id')) {
                $query->whereHas('locations', function ($q) use ($request) {
                    $q->where('location_id', $request->location_id);
                });
            }

            if ($request->filled('driver_id')) {
                $query->whereHas('drivers', function ($q) use ($request) {
                    $q->where('driver_id', $request->driver_id);
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

            $drivers = Driver::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');
            $locations = Location::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

            return \Inertia\Inertia::render('Clients/ClientsList', [
                'initialRows' => $paginator->items(),
                'initialTotal' => $paginator->total(),
                'drivers' => $drivers,
                'locations' => $locations,
            ]);
        }

        $logged_id_user = auth()->user();
        if ($logged_id_user->client_id !== null) {
            $clients = Client::whereIn('id', $logged_id_user->assigned_client_ids)->get();
        } else {
            $clients = Client::all();
        }

        return view('admin.clients.index', compact('clients'));
    }

    public function create(Request $request)
    {
        abort_if(Gate::denies('client_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');
        $locations = Location::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');
        
        if (str_starts_with($request->path(), 'app/')) {
            return \Inertia\Inertia::render('Clients/ClientForm', [
                'drivers' => $drivers,
                'locations' => $locations
            ]);
        }
        
        return view('admin.clients.create', compact('drivers','locations'));
    }

    public function store(StoreClientRequest $request)
    {
        $data = $request->all();

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('clients/logos', 'public');
            $data['logo'] = '/storage/' . $path;
        } else {
            unset($data['logo']);
        }

        $client = Client::create($data);
        $client->locations()->sync($request->input('locations', []));
        $client->drivers()->sync($request->input('drivers', []));

        if (str_starts_with($request->path(), 'app/')) {
            return redirect()->route('app.admin.clients.index');
        }

        return redirect()->route('admin.clients.index');
    }

    public function edit(Client $client, Request $request)
    {
        abort_if(Gate::denies('client_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // $drivers = Driver::pluck('plate_number', 'id')->();
        $drivers = Driver::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');
        $locations = Location::pluck('name', 'id');
        // $locations = Location::all();

        $client->load(['locations', 'drivers']);

        if (str_starts_with($request->path(), 'app/')) {
            return \Inertia\Inertia::render('Clients/ClientForm', [
                'client' => $client,
                'drivers' => $drivers,
                'locations' => $locations
            ]);
        }

        return view('admin.clients.edit', compact('client','drivers','locations'));
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $data = $request->all();

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('clients/logos', 'public');
            $data['logo'] = '/storage/' . $path;
        } else {
            // Don't overwrite the existing logo when no new file was picked
            unset($data['logo']);
        }

        $client->update($data);
        $client->locations()->sync($request->input('locations', []));
        $client->drivers()->sync($request->input('drivers', []));

        if (str_starts_with($request->path(), 'app/')) {
            return redirect()->route('app.admin.clients.index');
        }

        return redirect()->route('admin.clients.index');
    }

    public function show(Client $client)
    {
        abort_if(Gate::denies('client_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // $client->load('clientClientAddresses');

        return view('admin.clients.show', compact('client'));
    }

    public function destroy(Client $client, Request $request)
    {
        $this->authorize('can-delete');

        $client->delete();

        if (str_starts_with($request->path(), 'app/')) {
            return back();
        }

        return back();
    }

    public function getRelations(Client $client)
    {
        $client->load(['locations', 'drivers']);
        return response()->json([
            'drivers' => $client->drivers->pluck('id'),
            'locations' => $client->locations->pluck('id'),
        ]);
    }

    public function massDestroy(MassDestroyClientRequest $request)
    {
        $this->authorize('can-delete');
        Client::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
