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
use Illuminate\Support\Facades\Cache;
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

    public function index() {
        $user = auth()->user();

        $notifications = Notifications::with(['client','driver','fromLocation','toLocation'])
            ->when($user->client_id, fn($q) => $q->where('billing_client', $user->client_id))
            ->orderByDesc('created_at')
            ->paginate(10);

        $topDriversKey = $user->client_id ? "top_drivers_client_{$user->client_id}" : 'top_drivers_all';
        $top_drivers = Cache::remember($topDriversKey, now()->addMinutes(10), function () use ($user) {
            $query = Task::select('tasks.driver_id', 'drivers.name', DB::raw('count(*) as total'))
                ->join('drivers','drivers.id','=','tasks.driver_id');

            if ($user->client_id) {
                $query->join('client_driver', 'client_driver.driver_id', '=', 'drivers.id')
                    ->where('client_driver.client_id', $user->client_id)
                    ->where('billing_client', $user->client_id);
            }

            return $query->groupBy('tasks.driver_id','drivers.name')
                        ->orderByDesc('total')
                        ->limit(5)
                        ->get();
        });

        // counts (نجمعهم مع بعض بدل ما نعمل كويري لكل وحدة لحالها)
        if ($user->client_id) {
            $cars = Car::whereHas('driver.clientDrivers', fn($q) => $q->where('client_id', $user->client_id))->count();
            $tasks = Task::where('billing_client', $user->client_id)->count();
            $samples = Sample::whereHas('task', fn($q) => $q->where('billing_client', $user->client_id))->count();
            $drivers = Driver::count();
            $users = User::count();
            $locations = Location::whereHas('clients', fn($q) => $q->where('client_id', $user->client_id))->count();
            $clients = 1;
        } else {
            $cars = Car::count();
            $tasks = Task::count();
            $samples = Sample::count();
            $drivers = Driver::count();
            $users = User::count();
            $locations = Location::count();
            $clients = Client::count();
        }

        return view('dashboard', compact(
            'top_drivers',
            'clients',
            'notifications',
            'tasks',
            'locations',
            'drivers',
            'samples',
            'cars',
            'users'
        ));
    }

    public function welcome()
    {
        return view('welcome');
    }
    
    public function tasksdashboard(Request $request)
    {
        abort_if(Gate::denies('tasks-dashboard'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $filters = $request->only([
            'status', 'driver_id', 'billing_client', 
            'from_location', 'to_location', 'date_from', 'date_to'
        ]);

        // نعمل مفتاح كاش ديناميكي حسب الفلترة
        $cacheKey = 'tasks_dashboard_' . md5(json_encode($filters));

        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($filters) {

            $tasks = Task::leftJoin('clients','clients.id','=','billing_client')
                ->select('clients.arabic_name', DB::raw('count(*) as total'));

            // فلترة حسب الريكوست
            if (!empty($filters['status'])) {
                $tasks->where('tasks.status', $filters['status']);
            }
            if (!empty($filters['driver_id'])) {
                $tasks->where('driver_id', $filters['driver_id']);
            }
            if (!empty($filters['billing_client'])) {
                $tasks->where('billing_client', $filters['billing_client']);
            }
            if (!empty($filters['from_location'])) {
                $tasks->where('from_location', $filters['from_location']);
            }
            if (!empty($filters['to_location'])) {
                $tasks->where('to_location', $filters['to_location']);
            }

            // فلترة التواريخ
            if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
                $tasks->whereBetween('tasks.created_at', [
                    Carbon::parse($filters['date_from'])->startOfDay(),
                    Carbon::parse($filters['date_to'])->endOfDay(),
                ]);
            } elseif (!empty($filters['date_from'])) {
                $tasks->where('tasks.created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
            } elseif (!empty($filters['date_to'])) {
                $tasks->where('tasks.created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
            }

            // استعلام أساسي
            $base = clone $tasks;

            // all tasks
            $tasks_all = (clone $base)->groupBy('clients.arabic_name')->orderByDesc('total')->get();

            // pending tasks
            $tasks_pending = (clone $base)->whereNotIn('tasks.status',['CLOSED','NO_SAMPLES'])
                ->groupBy('clients.arabic_name')->orderByDesc('total')->get();

            // closed tasks
            $tasks_closed = (clone $base)->where('tasks.status','CLOSED')
                ->groupBy('clients.arabic_name')->orderByDesc('total')->get();

            // تجهيز الداتا للـ chart
            $categories    = $tasks_all->pluck('arabic_name')->unique()->values();
            $data_all      = $tasks_all->pluck('total');
            $data_closed   = $tasks_closed->pluck('total');
            $data_pending  = $tasks_pending->pluck('total');

            return [
                'categories'   => $categories,
                'data_all'     => $data_all,
                'data_closed'  => $data_closed,
                'data_pending' => $data_pending,
            ];
        });

        // بناء الـ chart
        $chart = (new Chart)->setType('bar')
            ->setWidth('100%')
            ->setTitle(trans('translation.Tasks_Clients'))
            ->setSubtitle(trans('translation.All_Data'))
            ->setHeight(300)
            ->setXaxisCategories($data['categories'])
            ->setDataset('Tasks', 'donut', [
                ['name' => 'Tasks',         'data' => $data['data_all']],
                ['name' => 'Closed Tasks',  'data' => $data['data_closed']],
                ['name' => 'Pending Tasks', 'data' => $data['data_pending']],
            ]);

        // جلب الداتا الثابتة (ممكن تنحط بكاش لو كبيرة)
        $clients   = Client::all();
        $drivers   = Driver::all();
        $locations = Location::all();

        return view('tasks-dashboard', compact('locations','drivers','clients','chart'));
    }


    public function map(Request $request)
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
