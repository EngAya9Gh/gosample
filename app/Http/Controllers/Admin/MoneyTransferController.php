<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyMoneyTransferRequest;
use App\Http\Requests\StoreMoneyTransferRequest;
use App\Http\Requests\UpdateMoneyTransferRequest;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Location;
use App\Models\MoneyTransfer;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class MoneyTransferController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('money_transfer_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = MoneyTransfer::with(['driver', 'client', 'from_location', 'to_location']);

        if ($request->filled('keyword')) {
            $kw = $request->keyword;
            $query->where(function($q) use ($kw) {
                $q->where('id', 'LIKE', "%{$kw}%")
                  ->orWhereHas('client', function($q2) use ($kw) {
                      $q2->where('english_name', 'LIKE', "%{$kw}%");
                  })
                  ->orWhereHas('driver', function($q2) use ($kw) {
                      $q2->where('name', 'LIKE', "%{$kw}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }
        if ($request->filled('from_location')) {
            $query->where('from_location_id', $request->from_location);
        }
        if ($request->filled('to_location')) {
            $query->where('to_location_id', $request->to_location);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sortBy = $request->filled('sort_by') ? $request->sort_by : 'id';
        $sortOrder = $request->filled('sort_order') ? $request->sort_order : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $pageSize = max(1, min((int) $request->input('pageSize', 25), 100));
        $page = max(1, (int) $request->input('page', 1));
        $total = (clone $query)->count();
        $offset = ($page - 1) * $pageSize;

        $seq = $offset;
        $rows = $query->offset($offset)->limit($pageSize)->get()->map(function ($s) use (&$seq) {
            $seq++;
            return [
                'sequence'           => $seq,
                'id'                 => $s->id,
                'driver_name'        => $s->driver ? $s->driver->name : null,
                'client_name'        => $s->client ? $s->client->english_name : null,
                'from_location_name' => $s->from_location ? $s->from_location->name : null,
                'to_location_name'   => $s->to_location ? $s->to_location->name : null,
                'amount'             => (float) $s->amount,
                'status'             => $s->status,
                'from_location_otp'  => $s->from_location_otp,
                'to_otp'             => $s->to_location_otp,
                'created_at'         => $s->created_at ? $s->created_at->format('Y-m-d H:i') : null,
            ];
        });

        if ($request->wantsJson()) {
            return response()->json([
                'rows' => $rows,
                'total' => $total,
            ]);
        }

        $drivers = Driver::select('id', 'name')->get()->map(fn($d) => ['value' => $d->id, 'label' => $d->name]);
        $clients = Client::select('id', 'english_name')->get()->map(fn($c) => ['value' => $c->id, 'label' => $c->english_name]);
        $locations = Location::select('id', 'name')->get()->map(fn($l) => ['value' => $l->id, 'label' => $l->name]);

        return \Inertia\Inertia::render('MoneyTransfers/MoneyTransfersList', [
            'initialRows' => $rows,
            'initialTotal' => $total,
            'filters' => [
                'drivers' => $drivers,
                'clients' => $clients,
                'locations' => $locations,
            ],
            'can' => [
                'money_transfer_create' => Gate::allows('money_transfer_create'),
                'money_transfer_edit'   => Gate::allows('money_transfer_edit'),
                'money_transfer_delete' => Gate::allows('money_transfer_delete'),
                'money_transfer_show'   => Gate::allows('money_transfer_show'),
            ],
        ]);
    }

    public function create()
    {
        abort_if(Gate::denies('money_transfer_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $clients = Client::pluck('english_name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $from_locations = Location::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $to_locations = Location::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.moneyTransfers.create', compact('clients', 'drivers', 'from_locations', 'to_locations'));
    }

    public function store(StoreMoneyTransferRequest $request)
    {
        $moneyTransfer = new MoneyTransfer();
        $moneyTransfer->client_id = $request->client_id;
        $moneyTransfer->driver_id = $request->driver_id;
        $moneyTransfer->from_location_id = $request->from_location_id;
        $moneyTransfer->to_location_id = $request->to_location_id;
        $moneyTransfer->amount = $request->amount;
        $moneyTransfer->status = 'new';
        $moneyTransfer->from_location_otp = $moneyTransfer->generateOtp(); // returns a 4-digit OTP as a string
        $moneyTransfer->to_location_otp = $moneyTransfer->generateOtp(); // returns a 4-digit OTP as a string
        $moneyTransfer->save();
        return redirect()->route('admin.money-transfers.index');
    }

    /**
     * Create a money transfer from the SPA modal (/app list page).
     * Mirrors store() 1:1 — status forced to 'new', both OTPs generated
     * server-side. Validates inline because StoreMoneyTransferRequest also
     * demands status/otp fields that store() overwrites anyway.
     */
    public function storePopup(Request $request)
    {
        abort_if(Gate::denies('money_transfer_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'client_id'        => ['required', 'integer'],
            'driver_id'        => ['required', 'integer'],
            'from_location_id' => ['required', 'integer'],
            'to_location_id'   => ['nullable', 'integer'],
            'amount'           => ['required', 'numeric', 'min:0'],
        ]);

        $moneyTransfer = new MoneyTransfer();
        $moneyTransfer->client_id = $data['client_id'];
        $moneyTransfer->driver_id = $data['driver_id'];
        $moneyTransfer->from_location_id = $data['from_location_id'];
        $moneyTransfer->to_location_id = $data['to_location_id'] ?? null;
        $moneyTransfer->amount = $data['amount'];
        $moneyTransfer->status = 'new';
        $moneyTransfer->from_location_otp = $moneyTransfer->generateOtp();
        $moneyTransfer->to_location_otp = $moneyTransfer->generateOtp();
        $moneyTransfer->save();

        return redirect()->route('app.admin.money-transfers.index')->with('success', 'Money transfer created successfully');
    }

    public function edit(MoneyTransfer $moneyTransfer)
    {
        abort_if(Gate::denies('money_transfer_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $clients = Client::pluck('english_name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $from_locations = Location::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $to_locations = Location::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $moneyTransfer->load('driver', 'client', 'from_location', 'to_location');

        return view('admin.moneyTransfers.edit', compact('clients', 'drivers', 'from_locations', 'moneyTransfer', 'to_locations'));
    }

    public function update(UpdateMoneyTransferRequest $request, MoneyTransfer $moneyTransfer)
    {
        // $moneyTransfer->update($request->all());

        $moneyTransfer->client_id = $request->client_id;
        $moneyTransfer->driver_id = $request->driver_id;
        $moneyTransfer->from_location_id = $request->from_location_id;
        $moneyTransfer->to_location_id = $request->to_location_id;
        $moneyTransfer->status = $request->status;
        $moneyTransfer->amount = $request->amount;

        $moneyTransfer->save();

        return redirect()->route('admin.money-transfers.index');
    }

    public function show(MoneyTransfer $moneyTransfer)
    {
        abort_if(Gate::denies('money_transfer_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $moneyTransfer->load('driver', 'client', 'from_location', 'to_location');

        return view('admin.moneyTransfers.show', compact('moneyTransfer'));
    }

    public function destroy(MoneyTransfer $moneyTransfer)
    {
        abort_if(Gate::denies('money_transfer_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $moneyTransfer->delete();

        if (request()->wantsJson()) {
            return response(null, Response::HTTP_NO_CONTENT);
        }
        
        return back();
    }

    public function massDestroy(MassDestroyMoneyTransferRequest $request)
    {
        $this->authorize('can-delete');
        $moneyTransfers = MoneyTransfer::find(request('ids'));

        foreach ($moneyTransfers as $moneyTransfer) {
            $moneyTransfer->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
