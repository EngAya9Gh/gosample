<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Car;
use App\Models\Notifications;
use App\Models\Driver;
use App\Models\Sample;
use App\Models\Task;
use App\Models\Client;
use App\Models\Location;
use DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use League\Csv\Reader;
use Akaunting\Apexcharts\Chart;
use App\Models\CarTracking;
use Illuminate\Support\Facades\Cache;

use const Adminer\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');




    }

public function updateCar(){
$filePath = storage_path('app/public/data.csv'); // Adjust path if needed

    if (!file_exists($filePath)) {
        return response()->json(['error' => 'File not found'], 404);
    }

    // Read CSV file
    $csv = Reader::createFromPath($filePath, 'r');
    $csv->setHeaderOffset(0); // Assuming the first row contains headers

    foreach ($csv as $record) {
//dd($record);
        Car::where('id', $record['ID'])->update([
            'plate_number'   => $record['Plate Number'],
            'model'          => $record['Model'],
 //           'color'          => $record['Color'],
            'contact_person' => $record['Contact Person'],
            'description'    => $record['Description'] ?? null,
        ]);
    }

    return response()->json(['message' => 'Cars updated successfully']);
}

    // public function index()
    // {
    //     $logged_id_user = auth()->user();
    //     if($logged_id_user->client_id != null)
    //     {
    //         $notifications = Notifications::with(['client','driver','fromLocation','toLocation'])
    //         ->where('billing_client',$logged_id_user->client_id)
    //         ->orderBy('created_at','desc')
    //         ->paginate(10);
    
    //         foreach($notifications as $notification)
    //         {
    //             $notification->title = explode('\\',$notification->type )[2]; 
    //         }
    
    //         $top_drivers = Task::select('tasks.driver_id', 'drivers.name',DB::raw('count(*) as total'))
    //         ->leftJoin('drivers','drivers.id','=','tasks.driver_id')
    //         ->leftJoin('client_driver', 'client_driver.driver_id', '=', 'drivers.id')
    //         ->where('client_driver.client_id', $logged_id_user->client_id)
    //         ->where('billing_client',$logged_id_user->client_id)
    //         ->groupby('driver_id','drivers.name')
    //         ->orderBy('total','desc')
    //         ->paginate(5);
    
    //         $client = auth()->user();

    //         $cars = Car::whereHas('driver.clientDrivers', function ($query) use ($client) {
    //             $query->where('client_id', $client->client_id);
    //         })->count();
    //         $tasks = Task::where('billing_client', $client->client_id)->count();
    //         $samples = Sample::leftJoin('tasks','tasks.id','task_id')->where('tasks.billing_client',$logged_id_user->client_id)->count();
    //         $drivers = Driver::count();
    //         $users = User::count();
    //         $locations = Location::leftJoin('client_location','client_location.location_id','locations.id')
    //         ->where('client_location.client_id',$logged_id_user->client_id)->count();
    //         $clients = 1;
    //         return view('dashboard',[
    //             'top_drivers' => $top_drivers,
    //             'clients' => $clients,
    //             'notifications' => $notifications,
    //             'tasks' => $tasks,
    //             'locations' => $locations,
    //             'drivers' => $drivers,
    //             'samples' => $samples,
    //             'cars' => $cars,
    //             'users' => $users,
    //         ]);
    //     }else{

    //         $notifications = Notifications::with(['client','driver','fromLocation','toLocation'])
    //         ->orderBy('created_at','desc')
    //         ->paginate(10);
    
    //         foreach($notifications as $notification)
    //         {
    //             $notification->title = explode('\\',$notification->type )[2]; 
    //         }

    //         $top_drivers = Cache::remember('top_drivers', now()->addMinutes(10), function() {
    //             return DB::table('tasks')
    //                 ->select('tasks.driver_id', 'drivers.name', DB::raw('COUNT(*) as total'))
    //                 ->join('drivers', 'drivers.id', '=', 'tasks.driver_id')
    //                 ->groupBy('tasks.driver_id', 'drivers.name')
    //                 ->orderByDesc('total')
    //                 ->limit(5)
    //                 ->get();
    //         });

    //         $cars = Car::count();
    //         $tasks = Task::count();
    //         $samples = Sample::count();
    //         $drivers = Driver::count();
    //         $users = User::count();
    //         $locations = Location::count();
    //         $clients = Client::count();

    //         return view('dashboard',[
    //             'top_drivers' => $top_drivers,
    //             'clients' => $clients,
    //             'notifications' => $notifications,
    //             'tasks' => $tasks,
    //             'locations' => $locations,
    //             'drivers' => $drivers,
    //             'samples' => $samples,
    //             'cars' => $cars,
    //             'users' => $users,
    //         ]);
    //      }
    // }

    // public function index()
    // {
    //     $loggedUser = auth()->user();

    //     // =========================
    //     // Notifications
    //     // =========================
    //     $notificationsQuery = Notifications::with(['client','driver','fromLocation','toLocation'])
    //         ->orderBy('created_at', 'desc');

    //     if ($loggedUser->client_id) {
    //         $notificationsQuery->where('billing_client', $loggedUser->client_id);
    //     }

    //     $notifications = $notificationsQuery->paginate(10);

    //     foreach ($notifications as $notification) {
    //         $notification->title = explode('\\', $notification->type)[2];
    //     }

    //     // =========================
    //     // Top Drivers
    //     // =========================
    //     if ($loggedUser->client_id) {
    //         $top_drivers = Task::select('tasks.driver_id', 'drivers.name', DB::raw('COUNT(*) as total'))
    //             ->leftJoin('drivers','drivers.id','=','tasks.driver_id')
    //             ->leftJoin('client_driver', 'client_driver.driver_id', '=', 'drivers.id')
    //             ->where('client_driver.client_id', $loggedUser->client_id)
    //             ->where('billing_client',$loggedUser->client_id)
    //             ->groupBy('driver_id','drivers.name')
    //             ->orderBy('total','desc')
    //             ->paginate(5);
    //     } else {
    //         $top_drivers = Cache::remember('top_drivers', now()->addMinutes(10), function() {
    //             return DB::table('tasks')
    //                 ->select('tasks.driver_id', 'drivers.name', DB::raw('COUNT(*) as total'))
    //                 ->join('drivers', 'drivers.id', '=', 'tasks.driver_id')
    //                 ->groupBy('tasks.driver_id', 'drivers.name')
    //                 ->orderByDesc('total')
    //                 ->limit(5)
    //                 ->get();
    //         });
    //     }

    //     // =========================
    //     // Statistics
    //     // =========================
    //     if ($loggedUser->client_id) {
    //         $cars = Car::whereHas('driver.clientDrivers', function ($query) use ($loggedUser) {
    //             $query->where('client_id', $loggedUser->client_id);
    //         })->count();

    //         $tasks = Task::where('billing_client', $loggedUser->client_id)->count();

    //         $samples = Sample::leftJoin('tasks','tasks.id','=','task_id')
    //             ->where('tasks.billing_client',$loggedUser->client_id)
    //             ->count();

    //         $locations = Location::leftJoin('client_location','client_location.location_id','=','locations.id')
    //             ->where('client_location.client_id',$loggedUser->client_id)
    //             ->count();

    //         $clients = 1; // ثابت للـ client
    //     } else {
    //         $cars = Car::count();
    //         $tasks = Task::count();
    //         $samples = Sample::count();
    //         $locations = Location::count();
    //         $clients = Client::count();
    //     }

    //     $drivers = Driver::count();
    //     $users   = User::count();

    //     // =========================
    //     // Return View
    //     // =========================
    //     return view('dashboard', [
    //         'top_drivers'   => $top_drivers,
    //         'clients'       => $clients,
    //         'notifications' => $notifications,
    //         'tasks'         => $tasks,
    //         'locations'     => $locations,
    //         'drivers'       => $drivers,
    //         'samples'       => $samples,
    //         'cars'          => $cars,
    //         'users'         => $users,
    //     ]);
    // }

    public function index()
    {
        $loggedUser = auth()->user();

        // =========================
        // Notifications
        // =========================
        $notificationsQuery = Notifications::with([
            'client:id,english_name',
            'driver:id,name',
            'fromLocation:id,name',
            'toLocation:id,name'
        ])->orderBy('created_at', 'desc');

        if ($loggedUser->client_id) {
            $notificationsQuery->where('billing_client', $loggedUser->client_id);
        }

        $notifications = $notificationsQuery->limit(10)->get();

        // =========================
        // Top Drivers
        // =========================
        $cacheKeyTopDrivers = $loggedUser->client_id
            ? "top_drivers_client_{$loggedUser->client_id}"
            : 'top_drivers_admin';

        $t1 = microtime(true);
        $top_drivers = Cache::remember($cacheKeyTopDrivers, now()->addMinutes(30), function () use ($loggedUser) {
            if ($loggedUser->client_id) {
                // INNER JOIN مع الفهارس الجديدة على client_driver
                return DB::table('tasks')
                    ->select('tasks.driver_id', 'drivers.name', DB::raw('COUNT(tasks.id) as total'))
                    ->join('drivers', 'drivers.id', '=', 'tasks.driver_id')
                    ->join('client_driver', 'client_driver.driver_id', '=', 'drivers.id')
                    ->where('client_driver.client_id', $loggedUser->client_id)
                    ->where('tasks.billing_client', $loggedUser->client_id)
                    ->where('drivers.status', 1)
                    ->whereNull('tasks.deleted_at')
                    ->groupBy('tasks.driver_id', 'drivers.name')
                    ->orderByDesc('total')
                    ->limit(5)
                    ->get();
            } else {
                // Subquery محسّن مع الفهرس المركب tasks_deleted_at_driver_id_idx
                return DB::table(DB::raw('(
                    SELECT driver_id, COUNT(id) as total
                    FROM tasks
                    WHERE deleted_at IS NULL AND driver_id IS NOT NULL
                    GROUP BY driver_id
                ) t'))
                ->select('t.driver_id', 'drivers.name', 't.total')
                ->join('drivers', 'drivers.id', '=', 't.driver_id')
                ->where('drivers.status', 1)
                ->orderByDesc('t.total')
                ->limit(5)
                ->get();
            }
        });
        $t2 = microtime(true);
        \Log::info('[Dashboard] top_drivers', [
            'cache_key' => $cacheKeyTopDrivers,
            'ms'        => round(($t2 - $t1) * 1000, 2),
        ]);

        // =========================
        // Statistics
        // =========================
        $cacheKeyStats = $loggedUser->client_id
            ? "dashboard_stats_client_{$loggedUser->client_id}"
            : 'dashboard_stats_admin';

        $t3 = microtime(true);
        if ($loggedUser->client_id) {
            $stats = Cache::remember($cacheKeyStats, now()->addMinutes(30), function () use ($loggedUser) {
                return (object) [
                    'cars'      => Car::whereHas('driver.clientDrivers', function ($q) use ($loggedUser) {
                                      $q->where('client_id', $loggedUser->client_id);
                                  })->count(),
                    'tasks'     => Task::where('billing_client', $loggedUser->client_id)->count(),
                    'samples'   => Sample::join('tasks', 'tasks.id', '=', 'task_id')
                                      ->where('tasks.billing_client', $loggedUser->client_id)
                                      ->count(),
                    'locations' => Location::leftJoin('client_location', 'client_location.location_id', '=', 'locations.id')
                                      ->where('client_location.client_id', $loggedUser->client_id)
                                      ->count(),
                    'clients'   => 1,
                ];
            });
        } else {
            $stats = Cache::remember($cacheKeyStats, now()->addMinutes(30), function () {
                return (object) [
                    'cars'      => DB::table('cars')->count(),
                    'tasks'     => DB::table('tasks')->count(),
                    'samples'   => DB::table('samples')->count(),
                    'drivers'   => DB::table('drivers')->count(),
                    'users'     => DB::table('users')->count(),
                    'locations' => DB::table('locations')->count(),
                    'clients'   => DB::table('clients')->count(),
                ];
            });
        }
        $t4 = microtime(true);
        \Log::info('[Dashboard] stats', [
            'cache_key' => $cacheKeyStats,
            'ms'        => round(($t4 - $t3) * 1000, 2),
        ]);

        // =========================
        // Return View
        // =========================
        return view('dashboard', [
            'top_drivers'   => $top_drivers,
            'clients'       => $stats->clients,
            // 'clients'       => 0,
            'notifications' => $notifications,
            'tasks'         => $stats->tasks,
            'locations'     => $stats->locations,
            'drivers'       => $stats->drivers ?? Driver::count(),
            'samples'       => $stats->samples,
            'cars'          => $stats->cars,
            'users'         => $stats->users ?? User::count(),
            // 'tasks'         => 0,
            // 'locations'     => 0,
            // 'drivers'       => 0,
            // 'samples'       => 0,
            // 'cars'          => 0,
            // 'users'         => 0,
        ]);
    }


    public function welcome()
    {
        return view('welcome');
    }
    // public function tasksdashboard(Request $request)
    // {

    //     $tasks = Task::
    //     leftJoin('clients','clients.id','billing_client')
    //     ->select('clients.arabic_name', DB::raw('count(*) as total'));
    //     if($request->status != null)
    //     {
    //         $tasks =$tasks->where('tasks.status',$request->status);
    //     }
    //     if($request->driver_id != null)
    //     {
    //         $tasks =$tasks->where('driver_id',$request->driver_id);
    //     }
    //     if($request->billing_client != null)
    //     {
    //         $tasks =$tasks->where('billing_client',$request->billing_client);
    //     }
    //     if($request->from_location != null)
    //     {
    //         $tasks =$tasks->where('from_location',$request->from_location);
    //     }
    //     if($request->to_location != null)
    //     {
    //         $tasks =$tasks->where('to_location',$request->to_location);
    //     }
    //     if($request->date_from !=null && $request->date_to !=null)
    //     {
    //         $tasks =$tasks->whereBetween('tasks.created_at',
    //             [
    //                 Carbon::createFromDate($request->date_from )->toDateString(),
    //                 Carbon::createFromDate($request->date_to)
    //                     //  ->addDays(1)
    //                      ->toDateString()
    //             ]
    //         );
    //     } else{
    //         if ($request->date_from !=null) {
    //             $tasks =$tasks->where('tasks.created_at', '>=', $request->date_from );
    //         }
    //         if ($request->date_to !=null) {
    //             $tasks =$tasks->where('tasks.created_at', '>=', $request->date_to );
    //         }
    //     }
    //     $tasks_all =clone $tasks;
    //     $tasks_pending =clone $tasks;
    //     $tasks_closed =clone $tasks;

    //     $tasks_all =$tasks->groupby('clients.arabic_name')
    //     ->orderby('total','desc')
    //     ->get();

    //     $tasks_pending =$tasks_pending->groupby('clients.arabic_name')
    //     // ->where('tasks.status','=','NEW')
    //     ->whereNotIn('tasks.status',['CLOSED','NO_SAMPLES'])
    //     ->orderby('total','desc')
    //     ->get();

    //     $tasks_closed =$tasks_closed->groupby('clients.arabic_name')
    //     ->where('tasks.status','CLOSED')
    //     ->orderby('total','desc')
    //     ->get();
       

       

    //     $categories = [];
    //     $data = [];
    //     $data_closed = [];
    //     $data_pending = [];

    //     $index = 0;
    //     foreach ($tasks_all as $task) {
    //         $categories[]=$task->arabic_name;
    //         $data[]=$task->total;
    //         $index =$index +1;
    //     }
    //     foreach ($tasks_closed as $task) {
    //         $categories[]=$task->arabic_name;
    //         $data_closed[]=$task->total;
    //         $index =$index +1;
    //     }
    //     foreach ($tasks_pending as $task) {
    //         $categories[]=$task->arabic_name;
    //         $data_pending[]=$task->total;
    //         $index =$index +1;
    //     }
        

    //     $chart = (new Chart)->setType('bar')
    //     ->setWidth('100%')
    //     ->setTitle(trans('translation.Tasks_Clients'))
    //     ->setWidth('100%')
    //     ->setSubtitle(trans('translation.All_Data'))
    //     ->setHeight(300)
    //         ->setXaxisCategories($categories)
    //         // ->setXaxisCategories(['2001', '2002','2003','20021', '22002','20032','22','20021', '22002','20032','22','23'])
    //         ->setDataset('Tasks', 'donut', [
    //             [
    //                 'name'  => 'Tasks',
    //                 'data'  =>  $data
    //             ],
    //             [
    //                 'name'  => 'Closed Tasks',
    //                 'data'  => $data_closed
    //             ],
    //             [
    //                 'name'  => 'Pending Tasks',
    //                 'data'  => $data_pending
    //             ]
    //         ]);


            
    //     $clients = Client::all();
    //     $logged_id_user = auth()->user();
    //     if($logged_id_user->client_id != null)
    //     {

    //         $drivers = Driver::all();
    //         $locations = Location::all();
    //         return view('tasks-dashboard',compact('locations','drivers','clients','chart'));
    //     } else{
    //         $drivers = Driver::all();
    //         $locations = Location::all();
    //         return view('tasks-dashboard',compact('locations','drivers','clients','chart'));
    //     }
    //     // return view('tasks-dashboard');
    // }
    public function tasksdashboard(Request $request)
    {
        // =========================
        // Aggregate tasks by client in one query
        // =========================
        $tasksData = Task::join('clients', 'clients.id', 'billing_client')
            ->select(
                'clients.arabic_name',
                DB::raw("COUNT(*) as total"),
                DB::raw("SUM(CASE WHEN tasks.status='CLOSED' THEN 1 ELSE 0 END) as closed_total"),
                DB::raw("SUM(CASE WHEN tasks.status NOT IN ('CLOSED','NO_SAMPLES') THEN 1 ELSE 0 END) as pending_total")
            )
            ->when($request->status, fn($q) => $q->where('tasks.status', $request->status))
            ->when($request->driver_id, fn($q) => $q->where('driver_id', $request->driver_id))
            ->when($request->billing_client, fn($q) => $q->where('billing_client', $request->billing_client))
            ->when($request->from_location, fn($q) => $q->where('from_location', $request->from_location))
            ->when($request->to_location, fn($q) => $q->where('to_location', $request->to_location))
            ->when($request->date_from && $request->date_to, function ($q) use ($request) {
                $q->whereBetween('tasks.created_at', [
                    Carbon::parse($request->date_from)->startOfDay(),
                    Carbon::parse($request->date_to)->endOfDay()
                ]);
            })
            ->groupBy('clients.arabic_name')
            ->orderByDesc('total')
            ->get();

        // =========================
        // Prepare chart arrays
        // =========================
        $categories = $tasksData->pluck('arabic_name')->toArray();
        $data_all = $tasksData->pluck('total')->toArray();
        $data_closed = $tasksData->pluck('closed_total')->toArray();
        $data_pending = $tasksData->pluck('pending_total')->toArray();

        // =========================
        // Build chart
        // =========================
        $chart = (new Chart)
            ->setType('bar')
            ->setWidth('100%')
            ->setHeight(300)
            ->setTitle(trans('translation.Tasks_Clients'))
            ->setSubtitle(trans('translation.All_Data'))
            ->setXaxisCategories($categories)
            ->setDataset('Tasks', 'donut', [
                ['name' => 'Tasks', 'data' => $data_all],
                ['name' => 'Closed Tasks', 'data' => $data_closed],
                ['name' => 'Pending Tasks', 'data' => $data_pending],
            ]);

        // =========================
        // Get clients, drivers, locations
        // =========================
        $clients = Client::all();
        $drivers = Driver::all();
        $locations = Location::all();

        // =========================
        // Return view
        // =========================
        return view('tasks-dashboard', compact('locations', 'drivers', 'clients', 'chart'));
    }


    public function map(Request $request)
    {
        $logged_id_user = auth()->user();

        if($logged_id_user->client_id != null) {
            $drivers = Driver::leftJoin('client_driver','driver_id','drivers.id')
            ->where('client_driver.client_id',$logged_id_user->client_id)
            ->select('id','name')->get();
            $plateNumbers = Driver::leftJoin('client_driver','driver_id','drivers.id')
            ->leftJoin('cars','cars.driver_id','drivers.id')
            ->where('client_driver.client_id',$logged_id_user->client_id)
            ->select('cars.plate_number')->get();

            $plateNumbers = Driver::leftJoin('client_driver','driver_id','drivers.id')
                ->leftJoin('cars','cars.driver_id','drivers.id')
                ->where('client_driver.client_id',$logged_id_user->client_id)
                ->whereNotNull('cars.plate_number')         // يستثني NULL
                ->where('cars.plate_number', '!=', '')      // يستثني الفاضي
                ->select('cars.plate_number')
                ->distinct()
                ->pluck('cars.plate_number')
                ->toArray();  
            
        } else {
            $drivers = Driver::select('id','name')->get();
            $plateNumbers = Driver::leftJoin('cars', 'cars.driver_id', '=', 'drivers.id')
                ->whereNotNull('cars.plate_number')         // يستثني NULL
                ->where('cars.plate_number', '!=', '')      // يستثني الفاضي
                ->select('cars.plate_number')
                ->distinct()
                ->pluck('cars.plate_number')
                ->toArray();  
            }
        return view('map',compact('drivers', 'plateNumbers'));
        
        if($logged_id_user->client_id != null)
        {
            // get driver of client
            $drivers = Driver::leftJoin('client_driver','driver_id','drivers.id')->get();
            $locations = Driver::select('drivers.*','imei','plate_number','model')
            ->leftJoin('cars','cars.driver_id','drivers.id')
            ->leftJoin('client_driver','client_driver.driver_id','drivers.id')
            ->where('client_driver.client_id',$logged_id_user->client_id)
            ->whereNotNull('cars.lat')
            ->where('cars.status', 1)
            ->with(['driverActiveTasks' =>function ($query) use ($logged_id_user) {
                $query->where('billing_client', $logged_id_user->client_id);
            }
            
            ,'driverActiveDelayedTasks','driverActiveTasks.from','driverActiveTasks.to','driverActiveTasks.samples','car','car.carTracking'])
            ;
            if($request->driver_id != null)
            {
                $locations = $locations->where('drivers.id',$request->driver_id);
            }
            if($request->imei != null)
            {
                $locations = $locations->where('cars.imei',$request->imei);
            }
            $locations = $locations->get();

           
            return view('map',compact('locations','drivers'));
        } else{
            // $drivers = Driver::all();
            $drivers = Driver::select('id','name')->get();
            $locations = Driver::select('drivers.*','imei','plate_number','model')
            ->leftJoin('cars','cars.driver_id','drivers.id')
            ->whereNotNull('cars.lat')
            ->where('cars.status', 1)
            ->with(['driverActiveTasks','driverActiveDelayedTasks','driverActiveTasks.from','driverActiveTasks.to','driverActiveTasks.samples','car','car.carTracking']);
            if($request->driver_id != null)
            {
                $locations = $locations->where('drivers.id',$request->driver_id);
            }
            if($request->imei != null)
            {
                $locations = $locations->where('cars.imei',$request->imei);
            }
            if($request->plate_number != null)
            {
                $locations = $locations->where('cars.plate_number',$request->plate_number);
            }
            $locations = $locations->get();

            // print_r($locations);
            foreach($locations as &$loc) {
                if ($loc->car && $loc->car->carTracking->isNotEmpty()) {
                    $latestTracking = $loc->car->carTracking->sortByDesc('created_at')->first();
                    $loc->lat = $latestTracking?->lat;
                    $loc->lng = $latestTracking?->lng;
                }
            }
            return view('map',compact('locations','drivers'));
        }
    }
    public function filterMap(Request $request)
    {
        $logged_id_user = auth()->user();

        $locations = Driver::select('drivers.*','imei','plate_number','model')
            ->leftJoin('cars','cars.driver_id','drivers.id')
            ->when($logged_id_user->client_id, function($q) use($logged_id_user) {
                $q->leftJoin('client_driver','client_driver.driver_id','drivers.id')
                ->where('client_driver.client_id',$logged_id_user->client_id);
            })
            ->whereNotNull('cars.lat')
            ->where('cars.status', 1)
            ->with([
                'driverActiveTasks' => function ($query) use ($logged_id_user) {
                    if ($logged_id_user->client_id) {
                        $query->where('billing_client', $logged_id_user->client_id);
                    }
                },
                'driverActiveDelayedTasks',
                'driverActiveTasks.from',
                'driverActiveTasks.to',
                'driverActiveTasks.samples',
                'car',
                'car.carTracking'
            ]);

        if ($request->driver_id) {
            $locations->where('drivers.id',$request->driver_id);
        }
        if ($request->imei) {
            $locations->where('cars.imei',$request->imei);
        }
        if ($request->plate_number) {
            $locations->where('cars.plate_number',$request->plate_number);
        }

        $locations = $locations->get();

        // نجيب أحدث إحداثيات من tracking
        foreach($locations as &$loc) {
            if ($loc->car && $loc->car->carTracking->isNotEmpty()) {
                $latestTracking = $loc->car->carTracking->sortByDesc('created_at')->first();
                $loc->lat = $latestTracking?->lat;
                $loc->lng = $latestTracking?->lng;
            }
        }

        return response()->json($locations);
    }

    // public function map(Request $request)
    // {
    //     $loggedUser = auth()->user();

    //     $locations = Driver::query()
    //         ->select([
    //             'drivers.id',
    //             'drivers.name',
    //             'cars.id as car_id',
    //             'cars.imei',
    //             'cars.plate_number',
    //             'cars.model',
    //             DB::raw('(SELECT lat FROM car_tracking WHERE car_tracking.car_id = cars.id ORDER BY car_tracking.created_at DESC LIMIT 1) as lat'),
    //             DB::raw('(SELECT lng FROM car_tracking WHERE car_tracking.car_id = cars.id ORDER BY car_tracking.created_at DESC LIMIT 1) as lng')
    //         ])
    //         ->leftJoin('cars', 'cars.driver_id', '=', 'drivers.id')
    //         ->whereNotNull('cars.lat')
    //         ->where('cars.status', 1)
    //         ->with([
    //             'driverActiveTasks' => function ($query) use ($loggedUser) {
    //                 if ($loggedUser->client_id) {
    //                     $query->where('billing_client', $loggedUser->client_id);
    //                 }
    //             },
    //             'driverActiveDelayedTasks',
    //             'driverActiveTasks.from',
    //             'driverActiveTasks.to',
    //             'driverActiveTasks.samples',
    //             'car',
    //             'car.carTracking'
    //         ]);
    //     $drivers = Driver::leftJoin('client_driver','driver_id','drivers.id');
    //     if ($loggedUser->client_id) {
    //         $locations->leftJoin('client_driver', 'client_driver.driver_id', '=', 'drivers.id')
    //             ->where('client_driver.client_id', $loggedUser->client_id);
    //         $drivers = $drivers->where('client_driver.client_id', $loggedUser->client_id);
    //     }

    //     if ($request->filled('driver_id')) {
    //         $locations->where('drivers.id', $request->driver_id);
    //     }
    //     if ($request->filled('imei')) {
    //         $locations->where('cars.imei', $request->imei);
    //     }
    //     if ($request->filled('plate_number')) {
    //         $locations->where('cars.plate_number', $request->plate_number);
    //     }

    //     $locations = $locations->get();
    //     dd($locations);
    //     $drivers = $drivers->get();
    //     return view('map', compact('locations', 'drivers'));
    // }

    public function getDriverLocations(Request $request)
    {


        $logged_id_user = auth()->user();
        if($logged_id_user->client_id != null)
        {
            // get driver of client
            $locations = Driver::select('drivers.*','imei','plate_number','model')
            ->leftJoin('cars','cars.driver_id','drivers.id')
            ->leftJoin('client_driver','client_driver.driver_id','drivers.id')
            ->where('client_driver.client_id',$logged_id_user->client_id)
            ->whereNotNull('cars.lat')
            ->where('cars.status', 1)
            ->with(['driverActiveTasks' =>function ($query) use ($logged_id_user) {
                $query->where('billing_client', $logged_id_user->client_id);
            }

            ,'driverActiveDelayedTasks','driverActiveTasks.from','driverActiveTasks.to','driverActiveTasks.samples','car','car.carTracking'])
            ;
            if($request->driver_id != null)
            {
                $locations = $locations->where('drivers.id',$request->driver_id);
            }
            if($request->imei != null)
            {
                $locations = $locations->where('cars.imei',$request->imei);
            }
            $locations = $locations->get();

            return response()->json($locations);
        } else{
            $locations = Driver::select('drivers.*','imei','plate_number','model')
            ->leftJoin('cars','cars.driver_id','drivers.id')
            ->whereNotNull('cars.lat')
            ->where('cars.status', 1)
            ->with(['driverActiveTasks','driverActiveDelayedTasks','driverActiveTasks.from','driverActiveTasks.to','driverActiveTasks.samples','car','car.carTracking']);
            if($request->driver_id != null)
            {
                $locations = $locations->where('drivers.id',$request->driver_id);
            }
            if($request->imei != null)
            {
                $locations = $locations->where('cars.imei',$request->imei);
            }
            if($request->plate_number != null)
            {
                $locations = $locations->where('cars.plate_number',$request->plate_number);
            }
            $locations = $locations->get();
            return response()->json($locations);
        }

    }

    public function map_old(Request $request)
    {

       
        $logged_id_user = auth()->user();
        if($logged_id_user->client_id != null)
        {
            // get driver of client
            $drivers = Driver::leftJoin('client_driver','driver_id','drivers.id')->get();
            $locations = Driver::select('drivers.*','imei','plate_number','model')
            ->leftJoin('cars','cars.driver_id','drivers.id')
            ->leftJoin('client_driver','client_driver.driver_id','drivers.id')
            ->where('client_driver.client_id',$logged_id_user->client_id)
            ->whereNotNull('cars.lat')
            ->where('cars.status', 1)
            ->with(['driverActiveTasks' =>function ($query) use ($logged_id_user) {
                $query->where('billing_client', $logged_id_user->client_id);
            }
            
            ,'driverActiveDelayedTasks','driverActiveTasks.from','driverActiveTasks.to','driverActiveTasks.samples','car','car.carTracking'])
            ;
            if($request->driver_id != null)
            {
                $locations = $locations->where('drivers.id',$request->driver_id);
            }
            if($request->imei != null)
            {
                $locations = $locations->where('cars.imei',$request->imei);
            }
            $locations = $locations->get();

           
            return view('map',compact('locations','drivers'));
        } else{
            $drivers = Driver::all();
            $locations = Driver::select('drivers.*','imei','plate_number','model')
            ->leftJoin('cars','cars.driver_id','drivers.id')
            ->whereNotNull('cars.lat')
            ->where('cars.status', 1)
            ->with(['driverActiveTasks','driverActiveDelayedTasks','driverActiveTasks.from','driverActiveTasks.to','driverActiveTasks.samples','car','car.carTracking']);
            if($request->driver_id != null)
            {
                $locations = $locations->where('drivers.id',$request->driver_id);
            }
            if($request->imei != null)
            {
                $locations = $locations->where('cars.imei',$request->imei);
            }
            if($request->plate_number != null)
            {
                $locations = $locations->where('cars.plate_number',$request->plate_number);
            }
            $locations = $locations->get();
            return view('map',compact('locations','drivers'));
        }
        
    }
}
