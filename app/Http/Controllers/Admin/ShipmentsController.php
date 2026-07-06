<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShipmentRequest;
use App\Models\Shipment;
use App\Jobs\GenerateAtenatiTokenJob;
use App\Models\ApiAyenati;
use App\Models\AyenatiToken;
use App\Models\Car;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Task;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Carbon\Carbon;


class ShipmentsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('shipment_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = Shipment::with(['task.from', 'task.to', 'task.driver']);

        if ($request->filled('keyword')) {
            $kw = $request->keyword;
            $query->where(function($q) use ($kw) {
                $q->where('reference_number', 'LIKE', "%{$kw}%")
                  ->orWhere('carrier', 'LIKE', "%{$kw}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status_code', $request->status);
        }
        if ($request->filled('carrier')) {
            $query->where('carrier', $request->carrier);
        }
        if ($request->filled('from_location')) {
            $loc = $request->from_location;
            $query->whereHas('task', function($q) use ($loc) {
                $q->where('from_location', $loc);
            });
        }
        if ($request->filled('to_location')) {
            $loc = $request->to_location;
            $query->whereHas('task', function($q) use ($loc) {
                $q->where('to_location', $loc);
            });
        }
        if ($request->filled('driver_id')) {
            $drv = $request->driver_id;
            $query->whereHas('task', function($q) use ($drv) {
                $q->where('driver_id', $drv);
            });
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->date_from)->startOfDay(),
                Carbon::parse($request->date_to)->endOfDay()
            ]);
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
            return [
                'sequence'           => ++$seq,
                'id'                 => $s->id,
                'carrier'            => $s->carrier,
                'reference_number'   => $s->reference_number,
                'pickup_otp'         => $s->pickup_otp,
                'dropoff_otp'        => $s->dropoff_otp,
                'status_code'        => $s->status_code,
                'batch'              => $s->batch,
                'journey_type'       => $s->journey_type,
                'sla_code'           => $s->sla_code,
                'created_at'         => $s->created_at ? $s->created_at->format('Y-m-d H:i') : null,
                'task_id'            => $s->task_id,
                'from_location_name' => optional(optional($s->task)->from)->name,
                'to_location_name'   => optional(optional($s->task)->to)->name,
                'driver_name'        => optional(optional($s->task)->driver)->name,
            ];
        });

        $filters = $request->only([
            'keyword', 'status', 'carrier', 'driver_id', 'from_location', 'to_location', 'date_from', 'date_to', 'sort_by', 'sort_order'
        ]);

        $drivers = Driver::select('id', 'name')->get()->map(fn($d) => ['value' => $d->id, 'label' => $d->name]);
        $locations = Location::select('id', 'name')->get()->map(fn($l) => ['value' => $l->id, 'label' => $l->name]);
        $carriers = Shipment::select('carrier')->whereNotNull('carrier')->distinct()->pluck('carrier')->map(fn($c) => ['value' => $c, 'label' => $c]);

        return Inertia::render('Shipments/ShipmentsList', [
            'rows'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'pageSize'  => $pageSize,
            'filters'   => $filters,
            'drivers'   => $drivers,
            'locations' => $locations,
            'carriers'  => $carriers,
        ]);
    }

    public function show(Shipment $shipment)
    {
        abort_if(Gate::denies('shipment_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');
//$shipment->task_id = 370630;
//$shipment->save();
        $shipment->load('task');
        $drivers = Driver::all();
	$task = $shipment->task ?? null;

//dd($shipment);
        return view('admin.shipments.show', compact('shipment','drivers','task'));
    }

    public function create()
    {
        abort_if(Gate::denies('task_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $logged_id_user = auth()->user();
        if (!empty($logged_id_user->assigned_client_ids))
        {
            $from_locations = Location::select('locations.*')
            ->leftJoin('client_location','client_location.location_id','locations.id')
            ->whereIn('client_location.client_id', $logged_id_user->assigned_client_ids)
            ->pluck('name', 'id');
            $to_locations = $from_locations;

            $drivers = Driver::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

        } else{
            $from_locations = Location::pluck('name', 'id');

            $to_locations = Location::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

            $drivers = Driver::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');
        }

        $tasks = Task::where('status', 'NEW')->pluck('id')->prepend(trans('translation.pleaseSelect'), '');
	
        return view('admin.shipments.create', compact('drivers', 'from_locations', 'to_locations', 'tasks'));
    }

    public function store(StoreShipmentRequest $request)
    {
        $logged_id_user = auth()->user();
        $driver = Driver::find( $request->driver_id);
        $shipment = new Shipment();//::create($request->all());
        $shipment->carrier = $request->carrier;
        $shipment->sender_name = $request->sender_name ?? '';
        $shipment->sender_long = $request->sender_long ?? '';
        $shipment->sender_lat = $request->sender_lat ?? '';
        $shipment->sender_mobile = $request->sender_mobile ?? '';
        $shipment->receiver_name = $request->receiver_name ?? '';
        $shipment->receiver_long = $request->receiver_long ?? '';
        $shipment->receiver_lat = $request->receiver_lat ?? '';
        $shipment->receiver_mobile = $request->receiver_mobile ?? '';
        $shipment->reference_number = $request->reference_number ?? '';
        $shipment->pickup_otp = rand(1000,9999);
        $shipment->batch = $request->batch;
        $shipment->journey_type = 0;
        $shipment->sla_code = "STAT";
        $shipment->status_code = "Assigned";

        $shipment->task_id = $request->task;
        $shipment->from_location = $request->from_location;
        $shipment->to_location = $request->to_location;
        $shipment->driver_id = $request->driver_id;
        $shipment->created_at = now();
        $shipment->save();

        // $driver->sendNotification( 'New shipment', 'You have new shipment',[$driver->fcm_token],$shipment,'open_task');

        return redirect()->route('admin.shipments.index');
    }
    public function assignDriver(Request $request, $shipmentId)
    {
        //GenerateAtenatiTokenJob::dispatch();
        $driverId = $request->input('driver');
        $shipmentw = Shipment::find($shipmentId);
       // Find the selected driver
       $driver = Driver::findOrFail($driverId);
       // Retrieve the last active access token from the token table
       $latestToken = AyenatiToken::orderBy('created_at', 'desc')->first();

        // \Log::info($latestToken);
       if (!$latestToken) {
           // Handle the case when there is no access token available
           // Log an error, throw an exception, or take appropriate action
           // generate token
           $errorMessage = 'No access token available.';
           return redirect()->back()->with('error', $errorMessage);
       }

       $accessToken = $latestToken->access_token;

       $result = $this->updateNotificationCall($shipmentw,$driver,$accessToken);
       \Log::error($result);
       if( $result)
       {
            $otp = rand(111111,900999);
            // save drop_off to table of shipment
            $shipmentw->dropoff_otp = $otp;
            $shipmentw->driver_id = $driver->id;
            $shipmentw->status_code = 'confirmed';
            $shipmentw->save();

            $task = Task::find($shipmentw->task_id);
            if (isset($task->id)){
                $task->driver_id = $driver->id;
 		$task->pickup_time = now();
                $task->save();
                $driver->sendNotification( 'New Task', 'You have new task',[$driver->fcm_token],$task,'open_task');
            }
            $s_id = $shipmentw->id;
           $result = $this->updateNotificationCall($shipmentw,$driver,$accessToken,'dispatched');
             if ($result) {
                 $result = $this->updateNotificationCall($shipmentw,$driver,$accessToken,'delivered');
                 if ($result) {
                     $this->updateDropOffOTP("$s_id", $otp, $accessToken);
                 }
             }

            // $shipmentw->dropoff_otp = $otp;
            // $shipmentw->save();
       }else{
            $errorMessage = 'unable to access api.';
            return redirect()->back()->with('error', $errorMessage);
       }



        // Redirect back to the shipment show page or any other desired location
        return redirect()->back();
    }

    public function deliver(Request $request, $shipmentId)
    {

        $shipmentw = Shipment::find($shipmentId);
        // \Log::info($shipmentw);
       $driver = Driver::find($shipmentw->driver_id);
       if($driver == null)
       {
            $errorMessage = 'Please assign driver first.';
            return redirect()->back()->with('error', $errorMessage);
       }
       // Retrieve the last active access token from the token table
       $latestToken = AyenatiToken::orderBy('created_at', 'desc')->first();

       if (!$latestToken) {
           // Handle the case when there is no access token available
           // Log an error, throw an exception, or take appropriate action
           // generate token
           $errorMessage = 'No access token available.';
           return redirect()->back()->with('error', $errorMessage);
       }

       $accessToken = $latestToken->access_token;

       $result = $this->deliverCall($shipmentw,$driver,$accessToken);
       if( $result)
       {
            $shipmentw->status_code = 'delivered';
            $shipmentw->save();
       }else{
            $errorMessage = 'unable to access api.';
            return redirect()->back()->with('error', $errorMessage);
       }

        // Redirect back to the shipment show page or any other desired location
        return redirect()->back();
    }

    public function dispatchshipment($shipmentId,$accessToken)
    {
        $requestData = [
            'shipment_id' => "$shipmentId"
        ];
        $timeoutMilliseconds = 120000;
        $response = Http::withOptions([
            'timeout' => $timeoutMilliseconds / 1000, // Convert milliseconds to seconds
        ])->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ])->post('https://api.lean.sa/p-ayenati/notifications/dispatchshipment', $requestData);

        if ($response->successful()) {
            $responseData = $response->json();
            // Log the successful response in the ApiResponse table
            ApiAyenati::create([
                'api_url' => 'https://api.lean.sa/p-ayenati/notifications/dispatchshipment',
                'response_flag' => 'success',
                'response' => json_encode($responseData),
            ]);
            return true;
            // Handle the successful response as needed
        } else {
            $errorMessage = $response->body();
            \Log::error( $errorMessage);
            GenerateAtenatiTokenJob::dispatch();

            // Log the failed response in the ApiResponse table
            ApiAyenati::create([
                'api_url' => 'https://api.lean.sa/p-ayenati/notifications/dispatchshipment',
                'response_flag' => 'failed',
                'response' => $errorMessage,
            ]);
            return false;
        }
    }

    public function updateNotificationCall($shipment, $driver,$accessToken,$status = null)
    {
        // Prepare the data for the API request
        $shipmentId = $shipment->id;
        $requestData = [
            'shipment_id' => "$shipmentId",
            'agent_first_name' => $driver->name,
            'agent_last_name' => $driver->name,
            'agent_national_id' => $driver->national_id,
            'agent_mobile' => $driver->mobile,
            'status_code' => $status ?? 'confirmed',
            'track_url' => 'https://gosample.com',
            'timestamp' =>  now()->toTimeString(),
        ];


        // \Log::error("-----");
        // \Log::error("-----");
        // \Log::info( $requestData);

        // \Log::error("-----");
        // \Log::error("-----");
        // \Log::error($accessToken);
        // \Log::error("-----");
        // \Log::error("-----");
        // Make the API request
	$timeoutMilliseconds = 120000;
        $response = Http::withOptions([
            'timeout' => $timeoutMilliseconds / 1000, // Convert milliseconds to seconds
        ])->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ])->post('https://api.lean.sa/p-ayenati/notifications/updateNotificationDetails', $requestData);

        if ($response->successful()) {
            $responseData = $response->json();
            // Log the successful response in the ApiResponse table
            ApiAyenati::create([
                'api_url' => 'https://api.lean.sa/p-ayenati/notifications/updateNotificationDetails',
                'response_flag' => 'success',
                'response' => json_encode($responseData),
            ]);
            return true;
            // Handle the successful response as needed
        } else {
            $errorMessage = $response->body();
            \Log::error( $errorMessage);
            GenerateAtenatiTokenJob::dispatch();

            // Log the failed response in the ApiResponse table
            ApiAyenati::create([
                'api_url' => 'https://api.lean.sa/p-ayenati/notifications/updateNotificationDetails',
                'response_flag' => 'failed',
                'response' => $errorMessage,
            ]);
            return false;
        }
    }
    public function updateDropOffOTP($shipmentId, $otp,$accessToken)
    {

        // Prepare the data for the API request
        $requestData = [
            'shipment_id' => "$shipmentId",
            'otp' => "$otp",
            'status_code' => 'delivered',
        ];

        // Set the timeout value in milliseconds (e.g., 60 seconds)
        $timeoutMilliseconds = 120000;
        // Make the API request
        $response = Http::withOptions([
            'timeout' => $timeoutMilliseconds / 1000, // Convert milliseconds to seconds
        ])->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post('https://api.lean.sa/p-ayenati/notifications/updateDropOffOTP', $requestData);

        // Process the API response
        if ($response->successful()) {
            $responseData = $response->json();
            // Log the successful response in the ApiResponse table
            ApiAyenati::create([
                'api_url' => 'https://api.lean.sa/p-ayenati/notifications/updateDropOffOTP',
                'response_flag' => 'success',
                'response' => json_encode($responseData),
            ]);
        } else {
            $errorMessage = $response->json('message');
            ApiAyenati::create([
                'api_url' => 'https://api.lean.sa/p-ayenati/notifications/updateDropOffOTP',
                'response_flag' => 'failed',
                'response' => $errorMessage,
            ]);
        }
    }

    public function updateDropOffOTPNew(Request $request)
    {
        $shipmentId = $request->input('shipment_id');
        $otp = $request->input('otp');
        $latestToken = AyenatiToken::orderBy('created_at', 'desc')->first();
        $accessToken = $latestToken->access_token;

        // Prepare the data for the API request
        $requestData = [
            'shipment_id' => "$shipmentId",
            'otp' => "$otp",
            'status_code' => 'delivered',
        ];

        // Set the timeout value in milliseconds (e.g., 60 seconds)
        $timeoutMilliseconds = 120000;
        // Make the API request
        $response = Http::withOptions([
            'timeout' => $timeoutMilliseconds / 1000, // Convert milliseconds to seconds
        ])->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post('https://api.lean.sa/p-ayenati/notifications/updateDropOffOTP', $requestData);

        // Process the API response
        if ($response->successful()) {
            $responseData = $response->json();
            // Log the successful response in the ApiResponse table
            ApiAyenati::create([
                'api_url' => 'https://api.lean.sa/p-ayenati/notifications/updateDropOffOTP',
                'response_flag' => 'success',
                'response' => json_encode($responseData),
            ]);
        } else {
            $errorMessage = $response->json('message');
            ApiAyenati::create([
                'api_url' => 'https://api.lean.sa/p-ayenati/notifications/updateDropOffOTP',
                'response_flag' => 'failed',
                'response' => $errorMessage,
            ]);
        }
    }

    public function deliverCall($shipment, $driver,$accessToken)
    {
        // Prepare the data for the API request
        $requestData = [
            'shipment_id' => $shipment->id,
            'agent_first_name' => $driver->name,
            'agent_last_name' => $driver->name,
            'agent_national_id' => $driver->national_id,
            'agent_mobile' => $driver->mobile,
            'status_code' => 'delivered',
            'track_url' => 'https://gosample.com',
            'timestamp' =>  now()->toTimeString(),
        ];

        // Make the API request
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ])->post('https://api.lean.sa/p-ayenati/notifications/updateNotificationDetails', $requestData);

        if ($response->successful()) {
            $responseData = $response->json();
            // Log the successful response in the ApiResponse table
            ApiAyenati::create([
                'api_url' => 'https://api.lean.sa/p-ayenati/notifications/updateNotificationDetails',
                'response_flag' => 'success',
                'response' => json_encode($responseData),
            ]);
            return true;
            // Handle the successful response as needed
        } else {
            $errorMessage = $response->body();
            // Log the failed response in the ApiResponse table
            ApiAyenati::create([
                'api_url' => 'https://api.lean.sa/p-ayenati/notifications/updateNotificationDetails',
                'response_flag' => 'failed',
                'response' => $errorMessage,
            ]);
            return false;
        }
    }

}
