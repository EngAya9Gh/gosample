<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Jobs\LogData;
use App\Models\Car;
use App\Models\Sample;
use App\Models\Client;
use App\Exports\TaskTimeReportExport;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Task;
use App\Models\User;
use App\Services\LogService;
use Gate;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromView;
use DB;
use Dompdf\Options;
use Dompdf\Dompdf;

use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Carbon;
use Spatie\Period\Period;
use Spatie\Period\Precision;

class TasksController extends Controller
{
    public function index(Request $request)
    {
        $logged_id_user = auth()->user();
        $sortColumn = $request->sort_by;
        $sortOrder = $request->get('sort_order', 'desc');

        if (!in_array($sortColumn, ['created_at', 'updated_at', 'collection_date'])) {
            $sortColumn = 'created_at'; // Default to 'created_at' if an invalid column is provided
        }

        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc'; // Default to 'desc' if an invalid order is provided
        }

        abort_if(Gate::denies('task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($request->ajax()) {
            \Log::info('Tasks Index AJAX called (Line 51)', ['filters' => $request->all()]);

            $query = Task::with(['from', 'to', 'client', 'driver', 'car'])
                ->select('tasks.*');

            // SECURITY (fail-closed scoping):
            //  - If the user is bound to a client (client_id set) → scope to that client.
            //  - Else if the user holds an admin-level role → no scope (sees all).
            //  - Else (e.g. a "client" user whose client_id was never set) → return nothing.
            //    This prevents the data leak we just observed where a non-admin user with a
            //    NULL client_id was effectively seeing every task in the system.
            $isAdminUser = $logged_id_user && (
                $logged_id_user->hasAnyRole(['Admin', 'Super Admin'])
                || method_exists($logged_id_user, 'getIsAdminAttribute') && $logged_id_user->is_admin
            );
            if ($logged_id_user && $logged_id_user->client_id) {
                $query->where('billing_client', $logged_id_user->client_id);
                \Log::info('Filter applied: client_id = ' . $logged_id_user->client_id);
            }

            // فلترة ذكية باستخدام when()
            $query->when($request->status, function($q, $v) {
                $q->where('status', $v);
                \Log::info('Filter applied: status = ' . $v);
            })
            ->when($request->driver_id, function($q, $v) {
                $q->where('driver_id', $v);
                \Log::info('Filter applied: driver_id = ' . $v);
            })
            ->when($request->billing_client, function($q, $v) {
                $q->where('billing_client', $v);
                \Log::info('Filter applied: billing_client = ' . $v);
            })
            ->when($request->from_location, function($q, $v) {
                $q->where('from_location', $v);
                \Log::info('Filter applied: from_location = ' . $v);
            })
            ->when($request->to_location, function($q, $v) {
                $q->where('to_location', $v);
                \Log::info('Filter applied: to_location = ' . $v);
            })
            ->when($request->keyword, function($q, $v) {
                $q->where('tasks.id', $v);
                \Log::info('Filter applied: keyword = ' . $v);
            });

            // فلترة التاريخ
            $dateColumn = $request->search_date ?? 'tasks.created_at';
            // $dateFrom   = $request->date_from ? Carbon::createFromFormat('Y-m-d\TH:i', $request->date_from) : null;
            // $dateTo     = $request->date_to ? Carbon::createFromFormat('Y-m-d\TH:i', $request->date_to) : null;
            // $dateFrom   = $request->date_from;
            // $dateTo     = $request->date_to;
            // $dateFrom = $request->date_from
            //     ? Carbon::createFromFormat('Y-m-d\TH:i', $request->date_from)
            //     : null;
     
            // $dateTo = $request->date_to
            $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : null;
            $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : null;

            // إذا لم يتم تحديد تاريخ، نضع فلتراً افتراضياً لآخر 30 يوماً لتحسين الأداء
            if (!$dateFrom && !$dateTo && !$request->keyword) {
                $dateFrom = Carbon::now()->subDays(30)->startOfDay();
                $dateTo = Carbon::now()->endOfDay();
                \Log::info('Applying default 30-day filter for performance');
            }

            // Make sure start <= end
            if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }

            if ($dateFrom && $dateTo) {
                $query->whereBetween($dateColumn, [
                    $dateFrom->toDateTimeString(),
                    $dateTo->toDateTimeString(),
                ]);
                \Log::info('Filter applied: dateBetween ' . $dateFrom->toDateTimeString() . ' and ' . $dateTo->toDateTimeString());
            } elseif ($dateFrom) {
                $query->where($dateColumn, '>=', $dateFrom);
                \Log::info('Filter applied: dateFrom >= ' . $dateFrom->toDateTimeString());
            } elseif ($dateTo) {
                $query->where($dateColumn, '<=', $dateTo);
                \Log::info('Filter applied: dateTo <= ' . $dateTo->toDateTimeString());
            }

            \Log::info('Tasks Query SQL: ' . $query->toSql(), ['bindings' => $query->getBindings()]);

            if ($request->has('order.0.column')) {
                // // مصفوفة mapping للعمود الفعلي
                // $columns = [
                //     1 => 'id',              // sequence ما هو DB فيمكن تبدأ 2
                //     2 => 'id',
                //     3 => 'created_at',
                //     4 => 'billing_client',
                //     5 => 'driver_id',
                //     6 => 'from_location',
                //     7 => 'to_location',
                //     8 => 'eta',
                //     9 => 'collection_date',
                //     10 => 'freezer_date',
                //     11 => 'close_date',
                //     12 => 'status',
                //     13 => 'task_type',
                //     14 => 'added_by',
                //     15 => 'hours', // هذا ليس في DB، تجاهله
                // ];

                // if (array_key_exists($columnIndex, $columns)) {
                //     $query->orderBy($columns[$columnIndex], $dir);
                // } else {
                //     $query->orderBy('created_at', 'desc'); // fallback
                // }
            } else {
                // $query->orderBy('created_at', 'desc');
            }


            // ترتيب النتائج
            // $query->orderBy('collection_date', 'desc');
            $query->orderBy($sortColumn, $sortOrder);
    
            \Log::info('Tasks Sample Data: ', ['sample' => (clone $query)->limit(2)->get()->toArray()]);
            
            // تجهيز الجدول
            $table = Datatables::of($query)
                ->addColumn('placeholder', '&nbsp;')
                ->addColumn('actions', '&nbsp;')
                ->addColumn('sequence', function () {
                    static $index = 0;
                    return ++$index;
                })
                ->editColumn('actions', function ($row) {
                    return view('partials.datatablesActions', [
                        'viewGate' => 'task_show',
                        'editGate' => 'task_edit',
                        'deleteGate' => 'task_delete',
                        'crudRoutePart' => 'tasks',
                        'row' => $row
                    ]);
                })
                ->addColumn('from_location_name', fn($row) => optional($row->from)->name)
                ->addColumn('to_location_name', fn($row) => optional($row->to)->name)
                ->addColumn('client', fn($row) => optional($row->client)->english_name)
                ->addColumn('driver_name', fn($row) => optional($row->driver)->name)
                ->addColumn('car_imei', fn($row) => optional($row->car)->imei)
                // ->addColumn('hours', function ($row) {
                //     if (!$row->collection_date || !$row->close_date) {
                //         return '';
                //     }
                //     return parent::hoursandmins(
                //         Period::make($row->collection_date, $row->close_date, Precision::MINUTE())->length(),
                //         '%02d Hours, %02d Minutes'
                //     );
                // })
                ->addColumn('hours', function ($row) {
                    if (!$row->collection_date || !$row->close_date) {
                        return '';
                    }
                    try {
                        return parent::hoursandmins(
                            Period::make($row->collection_date, $row->close_date, Precision::MINUTE())->length(),
                            '%02d Hours, %02d Minutes'
                        );
                    } catch (\Exception $e) {
                        return '';
                    }
                })
                ->editColumn('confirmed_received_by_driver', fn($row) => $row->confirmed_received_by_driver === 1
                    ? '<span class="confirmed">Confirmed</span>'
                    : ($row->confirmed_received_by_driver === 0 ? '<span class="not-confirmed">Not Confirmed</span>' : '')
                )
                ->editColumn('driver_confirm_from_location', fn($row) => $row->driver_confirm_from_location === 1
                    ? '<span class="confirmed">Confirmed</span>'
                    : ($row->driver_confirm_from_location === 0 ? '<span class="not-confirmed">Not Confirmed</span>' : '')
                )
                ->editColumn('driver_confirm_to_location', fn($row) => $row->driver_confirm_to_location === 1
                    ? '<span class="confirmed">Confirmed</span>'
                    : ($row->driver_confirm_to_location === 0 ? '<span class="not-confirmed">Not Confirmed</span>' : '')
                )
                ->editColumn('status', function ($row) {
                    $map = [
                        'NEW'         => ['bg-primary',   'NEW'],
                        'COLLECTED'   => ['bg-info',      'COLLECTED'],
                        'IN_FREEZER'  => ['bg-warning',   'IN_FREEZER'],
                        'OUT_FREEZER' => ['bg-warning',   'OUT_FREEZER'],
                        'CLOSED'      => ['bg-success',   'CLOSED'],
                        'NO_SAMPLES'  => ['bg-secondary', 'NO_SAMPLES'],
                    ];
                    if (isset($map[$row->status])) {
                        return '<span class="badge ' . $map[$row->status][0] . '">' . $map[$row->status][1] . '</span>';
                    }
                    return $row->status ?? '';
                })
                ->rawColumns([
                    'actions', 'placeholder', 'from_location', 'to_location', 'billing_client',
                    'driver', 'car', 'driver_confirm_from_location', 'driver_confirm_to_location', 'confirmed_received_by_driver', 'status'
                ]);
 
            try {
                $startTime = microtime(true);
                $response = $table->make(true);
                $endTime = microtime(true);
                \Log::info('Tasks Index AJAX execution time: ' . ($endTime - $startTime) . ' seconds');
                \Log::info('Tasks Index JSON Response sample: ' . substr($response->getContent(), 0, 1000));
                return $response;
            } catch (\Exception $e) {
                \Log::error('Tasks Index AJAX Error: ' . $e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'error' => 'DataTables Error',
                    'message' => $e->getMessage()
                ], 500);
            }
        } else{
            // \Log::error("no ajax");
        }




        if( $logged_id_user->client_id != null)
        {
                $clients = Client::where('id', $logged_id_user->client_id)->get();
                $locations = Location::select('locations.*')
                ->leftJoin('client_location','client_location.location_id','locations.id')
                ->where('client_location.client_id',$logged_id_user->client_id)
                ->get();
                $drivers = Driver::all();
        } else{
            $clients = Client::all();
            $locations = Location::all();
            $drivers = Driver::all();
        }



        return view('admin.tasks.index',[
            'clients' =>  $clients,
            'locations' =>  $locations,
            'drivers' =>  $drivers
        ]);
    }
    public function updateTimes(Request $request, Task $task)
    {
        abort_if(Gate::denies('task_edit_times'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'freezer_out_date' => 'nullable|date',
            'close_date'       => 'nullable|date',
        ]);

        $task->update([
            'freezer_out_date' => $request->freezer_out_date ? Carbon::parse($request->freezer_out_date)->toDateTimeString() : null,
            'close_date'       => $request->close_date ? Carbon::parse($request->close_date)->toDateTimeString() : null,
        ]);

        return redirect()->back()->with('status', 'Task times updated successfully.');
    }

    /*
    // public function index(Request $request)
    // {
    //     $logged_id_user = auth()->user();
    //     abort_if(Gate::denies('task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

    //     // لو الطلب Ajax (يعني جدول البيانات)
    //     if ($request->ajax()) {
    //         $sortColumn = $request->get('sort_by', 'collection_date');
    //         $sortOrder  = $request->get('sort_order', 'desc');

    //         if (!in_array($sortColumn, ['created_at', 'updated_at', 'collection_date'])) {
    //             $sortColumn = 'collection_date';
    //         }
    //         if (!in_array($sortOrder, ['asc', 'desc'])) {
    //             $sortOrder = 'desc';
    //         }

    //         $query = Task::with(['from', 'to', 'client', 'driver', 'car'])
    //             ->select('tasks.*');

    //         // فلترة حسب العميل في حال المستخدم مربوط بعميل
    //         if ($logged_id_user->client_id) {
    //             $query->where('billing_client', $logged_id_user->client_id);
    //         }

    //         // فلترة ديناميكية
    //         $query->when($request->status, fn($q, $v) => $q->where('status', $v))
    //             ->when($request->driver_id, fn($q, $v) => $q->where('driver_id', $v))
    //             ->when($request->billing_client, fn($q, $v) => $q->where('billing_client', $v))
    //             ->when($request->from_location, fn($q, $v) => $q->where('from_location', $v))
    //             ->when($request->to_location, fn($q, $v) => $q->where('to_location', $v))
    //             ->when($request->keyword, fn($q, $v) => $q->where('tasks.id', $v));

    //         // فلترة التاريخ
    //         $dateColumn = $request->search_date ?? 'tasks.created_at';
    //         $dateFrom   = $request->date_from ? Carbon::createFromFormat('Y-m-d\TH:i', $request->date_from) : null;
    //         $dateTo     = $request->date_to ? Carbon::createFromFormat('Y-m-d\TH:i', $request->date_to) : null;

    //         if ($dateFrom && $dateTo) {
    //             $query->whereBetween($dateColumn, [$dateFrom, $dateTo]);
    //         } elseif ($dateFrom) {
    //             $query->where($dateColumn, '>=', $dateFrom);
    //         } elseif ($dateTo) {
    //             $query->where($dateColumn, '<=', $dateTo);
    //         }

    //         $query->orderBy($sortColumn, $sortOrder);

    //         // pagination خفيف وسريع
    //         $tasks = $query->paginate(20);

    //         // تنسيق البيانات (بدون Yajra)
    //         $data = $tasks->map(function ($row) {
    //             return [
    //                 'id' => $row->id,
    //                 'client' => optional($row->client)->english_name,
    //                 'from_location_name' => optional($row->from)->name,
    //                 'to_location_name' => optional($row->to)->name,
    //                 'driver_name' => optional($row->driver)->name,
    //                 'car_imei' => optional($row->car)->imei,
    //                 'collection_date' => $row->collection_date,
    //                 'close_date' => $row->close_date,
    //                 'status' => $row->status,
    //                 'hours' => ($row->collection_date && $row->close_date)
    //                     ? parent::hoursandmins(
    //                         Period::make($row->collection_date, $row->close_date, Precision::MINUTE())->length(),
    //                         '%02d Hours, %02d Minutes'
    //                     )
    //                     : '',
    //                 'confirmed_received_by_driver' =>
    //                     $row->confirmed_received_by_driver === 1 ? 'Confirmed' :
    //                     ($row->confirmed_received_by_driver === 0 ? 'Not Confirmed' : ''),
    //                 'driver_confirm_from_location' =>
    //                     $row->driver_confirm_from_location === 1 ? 'Confirmed' :
    //                     ($row->driver_confirm_from_location === 0 ? 'Not Confirmed' : ''),
    //                 'driver_confirm_to_location' =>
    //                     $row->driver_confirm_to_location === 1 ? 'Confirmed' :
    //                     ($row->driver_confirm_to_location === 0 ? 'Not Confirmed' : ''),
    //             ];
    //         });

    //         return response()->json([
    //             'data' => $data,
    //             'pagination' => [
    //                 'total' => $tasks->total(),
    //                 'per_page' => $tasks->perPage(),
    //                 'current_page' => $tasks->currentPage(),
    //                 'last_page' => $tasks->lastPage(),
    //             ],
    //         ]);
    //     }

    //     // لو مو Ajax (تحميل الصفحة عادي)
    //     if ($logged_id_user->client_id != null) {
    //         $clients = Client::where('id', $logged_id_user->client_id)->get();
    //         $locations = Location::select('locations.*')
    //             ->leftJoin('client_location', 'client_location.location_id', 'locations.id')
    //             ->where('client_location.client_id', $logged_id_user->client_id)
    //             ->get();
    //         $drivers = Driver::all();
    //     } else {
    //         $clients = Client::all();
    //         $locations = Location::all();
    //         $drivers = Driver::all();
    //     }

    //     return view('admin.tasks.index', [
    //         'clients' => $clients,
    //         'locations' => $locations,
    //         'drivers' => $drivers
    //     ]);
    // }

    // public function index(Request $request)
    // {
    //     $logged_id_user = auth()->user();
    //     $sortColumn = $request->sort_by;
    //     $sortOrder = $request->get('sort_order', 'desc');

    //     if (!in_array($sortColumn, ['created_at', 'updated_at', 'collection_date'])) {
    //         $sortColumn = 'collection_date';
    //     }

    //     if (!in_array($sortOrder, ['asc', 'desc'])) {
    //         $sortOrder = 'desc'; 
    //     }

    //     abort_if(Gate::denies('task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

    //     if ($request->ajax()) {

    //         // تحسين الاستعلام باستخدام joins بدلاً من with() لتقليل الذاكرة
    //         $query = Task::select(
    //             'tasks.*',
    //             'from_loc.name as from_location_name',
    //             'to_loc.name as to_location_name',
    //             'clients.english_name as client_name',
    //             'drivers.name as driver_name',
    //             'cars.imei as car_imei'
    //         )
    //         ->leftJoin('locations as from_loc', 'tasks.from_location', '=', 'from_loc.id')
    //         ->leftJoin('locations as to_loc', 'tasks.to_location', '=', 'to_loc.id')
    //         ->leftJoin('clients', 'tasks.billing_client', '=', 'clients.id')
    //         ->leftJoin('drivers', 'tasks.driver_id', '=', 'drivers.id')
    //         ->leftJoin('cars', 'tasks.car_id', '=', 'cars.id');

    //         // فلتر حسب العميل إذا المستخدم مربوط بعميل
    //         if ($logged_id_user->client_id) {
    //             $query->where('billing_client', $logged_id_user->client_id);
    //         }

    //         // فلترة ذكية
    //         $query->when($request->status, fn($q, $v) => $q->where('status', $v))
    //             ->when($request->driver_id, fn($q, $v) => $q->where('driver_id', $v))
    //             ->when($request->billing_client, fn($q, $v) => $q->where('billing_client', $v))
    //             ->when($request->from_location, fn($q, $v) => $q->where('from_location', $v))
    //             ->when($request->to_location, fn($q, $v) => $q->where('to_location', $v))
    //             ->when($request->keyword, fn($q, $v) => $q->where('tasks.id', $v));

    //         // فلترة التاريخ
    //         $dateColumn = $request->search_date ?? 'tasks.created_at';
    //         $dateFrom   = $request->date_from ? Carbon::createFromFormat('Y-m-d\TH:i', $request->date_from) : null;
    //         $dateTo     = $request->date_to ? Carbon::createFromFormat('Y-m-d\TH:i', $request->date_to) : null;

    //         if ($dateFrom && $dateTo) {
    //             $query->whereBetween($dateColumn, [$dateFrom, $dateTo]);
    //         } elseif ($dateFrom) {
    //             $query->where($dateColumn, '>=', $dateFrom);
    //         } elseif ($dateTo) {
    //             $query->where($dateColumn, '<=', $dateTo);
    //         }

    //         // $query->orderBy($sortColumn, $sortOrder);
    //         // $query->limit(10);
    //         // $query->get();
    //         // dd($query->get());
    //         $table = Datatables::of($query)
    //             ->addColumn('placeholder', '&nbsp;')
    //             ->addColumn('actions', '&nbsp;')
    //             ->addColumn('sequence', function () {
    //                 static $index = 0;
    //                 return ++$index;
    //             })
    //             ->editColumn('actions', function ($row) {
    //                 return view('partials.datatablesActions', [
    //                     'viewGate' => 'task_show',
    //                     'editGate' => 'task_edit',
    //                     'deleteGate' => 'task_delete',
    //                     'crudRoutePart' => 'tasks',
    //                     'row' => $row
    //                 ]);
    //             })
    //             ->addColumn('from_location_name', fn($row) => $row->from_location_name)
    //             ->addColumn('to_location_name', fn($row) => $row->to_location_name)
    //             ->addColumn('client', fn($row) => $row->client_name)
    //             ->addColumn('driver_name', fn($row) => $row->driver_name)
    //             ->addColumn('car_imei', fn($row) => $row->car_imei)
    //             ->addColumn('hours', function ($row) {
    //                 if (!$row->collection_date || !$row->close_date) {
    //                     return '';
    //                 }
    //                 return parent::hoursandmins(
    //                     Period::make($row->collection_date, $row->close_date, Precision::MINUTE())->length(),
    //                     '%02d Hours, %02d Minutes'
    //                 );
    //             })
    //             ->editColumn('confirmed_received_by_driver', fn($row) => $row->confirmed_received_by_driver === 1
    //                 ? '<span class="confirmed">Confirmed</span>'
    //                 : ($row->confirmed_received_by_driver === 0 ? '<span class="not-confirmed">Not Confirmed</span>' : '')
    //             )
    //             ->editColumn('driver_confirm_from_location', fn($row) => $row->driver_confirm_from_location === 1
    //                 ? '<span class="confirmed">Confirmed</span>'
    //                 : ($row->driver_confirm_from_location === 0 ? '<span class="not-confirmed">Not Confirmed</span>' : '')
    //             )
    //             ->editColumn('driver_confirm_to_location', fn($row) => $row->driver_confirm_to_location === 1
    //                 ? '<span class="confirmed">Confirmed</span>'
    //                 : ($row->driver_confirm_to_location === 0 ? '<span class="not-confirmed">Not Confirmed</span>' : '')
    //             )
    //             ->rawColumns([
    //                 'actions', 'placeholder', 'from_location', 'to_location', 'billing_client', 
    //                 'driver', 'car', 'driver_confirm_from_location', 'driver_confirm_to_location', 'confirmed_received_by_driver'
    //             ]);

    //         return $table->make(true);
    //     }

    //     // البيانات للواجهة العادية
    //     if ($logged_id_user->client_id != null) {
    //         $clients = Client::where('id', $logged_id_user->client_id)->get();
    //         $locations = Location::select('locations.*')
    //             ->leftJoin('client_location','client_location.location_id','locations.id')
    //             ->where('client_location.client_id',$logged_id_user->client_id)
    //             ->get();
    //         $drivers = Driver::all();
    //     } else {
    //         $clients = Client::all();
    //         $locations = Location::all();
    //         $drivers = Driver::all();
    //     }

    //     return view('admin.tasks.index',[
    //         'clients' =>  $clients,
    //         'locations' =>  $locations,
    //         'drivers' =>  $drivers
    //     ]);
    // }

    // public function index(Request $request)
    // {
    //     $logged_id_user = auth()->user();
    //     $sortColumn = $request->sort_by;
    //     $sortOrder = $request->get('sort_order', 'desc');

    //     if (!in_array($sortColumn, ['created_at', 'updated_at', 'collection_date'])) {
    //         $sortColumn = 'collection_date';
    //     }

    //     if (!in_array($sortOrder, ['asc', 'desc'])) {
    //         $sortOrder = 'desc'; 
    //     }

    //     abort_if(Gate::denies('task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
    //     if ($request->ajax()) {
    //         $query = Task::with(['from', 'to', 'client', 'driver', 'car'])
    //             ->select('tasks.*');

    //         // فلتر حسب العميل إذا المستخدم مربوط بعميل
    //         if ($logged_id_user->client_id) {
    //             $query->where('billing_client', $logged_id_user->client_id);
    //         }

    //         // فلترة ذكية باستخدام when()
    //         $query->when($request->status, fn($q, $v) => $q->where('status', $v))
    //             ->when($request->driver_id, fn($q, $v) => $q->where('driver_id', $v))
    //             ->when($request->billing_client, fn($q, $v) => $q->where('billing_client', $v))
    //             ->when($request->from_location, fn($q, $v) => $q->where('from_location', $v))
    //             ->when($request->to_location, fn($q, $v) => $q->where('to_location', $v))
    //             ->when($request->keyword, fn($q, $v) => $q->where('tasks.id', $v));

    //         // فلترة التاريخ
    //         $dateColumn = $request->search_date ?? 'tasks.created_at';
    //         $dateFrom   = $request->date_from ? Carbon::createFromFormat('Y-m-d\TH:i', $request->date_from) : null;
    //         $dateTo     = $request->date_to ? Carbon::createFromFormat('Y-m-d\TH:i', $request->date_to) : null;

    //         if ($dateFrom && $dateTo) {
    //             $query->whereBetween($dateColumn, [$dateFrom, $dateTo]);
    //         } elseif ($dateFrom) {
    //             $query->where($dateColumn, '>=', $dateFrom);
    //         } elseif ($dateTo) {
    //             $query->where($dateColumn, '<=', $dateTo);
    //         }
            
    //         $query->orderBy($sortColumn, $sortOrder);

    //         $table = Datatables::of($query)
    //             ->addColumn('placeholder', '&nbsp;')
    //             ->addColumn('actions', '&nbsp;')
    //             ->addColumn('sequence', function () {
    //                 static $index = 0;
    //                 return ++$index;
    //             })
    //             ->editColumn('actions', function ($row) {
    //                 return view('partials.datatablesActions', [
    //                     'viewGate' => 'task_show',
    //                     'editGate' => 'task_edit',
    //                     'deleteGate' => 'task_delete',
    //                     'crudRoutePart' => 'tasks',
    //                     'row' => $row
    //                 ]);
    //             })
    //             ->addColumn('from_location_name', fn($row) => optional($row->from)->name)
    //             ->addColumn('to_location_name', fn($row) => optional($row->to)->name)
    //             ->addColumn('client', fn($row) => optional($row->client)->english_name)
    //             ->addColumn('driver_name', fn($row) => optional($row->driver)->name)
    //             ->addColumn('car_imei', fn($row) => optional($row->car)->imei)
    //             ->addColumn('hours', function ($row) {
    //                 if (!$row->collection_date || !$row->close_date) {
    //                     return '';
    //                 }
    //                 return parent::hoursandmins(
    //                     Period::make($row->collection_date, $row->close_date, Precision::MINUTE())->length(),
    //                     '%02d Hours, %02d Minutes'
    //                 );
    //             })
    //             ->editColumn('confirmed_received_by_driver', fn($row) => $row->confirmed_received_by_driver === 1
    //                 ? '<span class="confirmed">Confirmed</span>'
    //                 : ($row->confirmed_received_by_driver === 0 ? '<span class="not-confirmed">Not Confirmed</span>' : '')
    //             )
    //             ->editColumn('driver_confirm_from_location', fn($row) => $row->driver_confirm_from_location === 1
    //                 ? '<span class="confirmed">Confirmed</span>'
    //                 : ($row->driver_confirm_from_location === 0 ? '<span class="not-confirmed">Not Confirmed</span>' : '')
    //             )
    //             ->editColumn('driver_confirm_to_location', fn($row) => $row->driver_confirm_to_location === 1
    //                 ? '<span class="confirmed">Confirmed</span>'
    //                 : ($row->driver_confirm_to_location === 0 ? '<span class="not-confirmed">Not Confirmed</span>' : '')
    //             )
    //             ->rawColumns([
    //                 'actions', 'placeholder', 'from_location', 'to_location', 'billing_client', 
    //                 'driver', 'car', 'driver_confirm_from_location', 'driver_confirm_to_location', 'confirmed_received_by_driver'
    //             ]);

    //         return $table->make(true);
    //     } 

    //     if( $logged_id_user->client_id != null)
    //     {
    //             $clients = Client::where('id', $logged_id_user->client_id)->get();
    //             $locations = Location::select('locations.*')
    //             ->leftJoin('client_location','client_location.location_id','locations.id')
    //             ->where('client_location.client_id',$logged_id_user->client_id)
    //             ->get();
    //             $drivers = Driver::all();
    //     } else{
    //         $clients = Client::all();
    //         $locations = Location::all();
    //         $drivers = Driver::all();
    //     }

    //     return view('admin.tasks.index',[
    //         'clients' =>  $clients,
    //         'locations' =>  $locations,
    //         'drivers' =>  $drivers
    //     ]);
    // }
    */
    // public function index(Request $request)
    // {
    //     $logged_id_user = auth()->user();
    //     $sortColumn = $request->sort_by;
    //     $sortOrder = $request->get('sort_order', 'desc');

    //     if (!in_array($sortColumn, ['created_at', 'updated_at', 'collection_date'])) {
    //         $sortColumn = 'collection_date';
    //     }

    //     if (!in_array($sortOrder, ['asc', 'desc'])) {
    //         $sortOrder = 'desc'; 
    //     }

    //     abort_if(Gate::denies('task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

    //     $query = Task::with(['from', 'to', 'client', 'driver', 'car'])
    //                 ->select('tasks.*');

    //     if ($logged_id_user->client_id) {
    //         $query->where('billing_client', $logged_id_user->client_id);
    //     }

    //     // فلاتر
    //     $query->when($request->status, fn($q, $v) => $q->where('status', $v))
    //         ->when($request->driver_id, fn($q, $v) => $q->where('driver_id', $v))
    //         ->when($request->billing_client, fn($q, $v) => $q->where('billing_client', $v))
    //         ->when($request->from_location, fn($q, $v) => $q->where('from_location', $v))
    //         ->when($request->to_location, fn($q, $v) => $q->where('to_location', $v))
    //         ->when($request->keyword, fn($q, $v) => $q->where('tasks.id', $v));

    //     // فلترة التاريخ
    //     $dateColumn = $request->search_date ?? 'tasks.created_at';
    //     $dateFrom   = $request->date_from ? Carbon::parse($request->date_from) : null;
    //     $dateTo     = $request->date_to ? Carbon::parse($request->date_to) : null;

    //     if ($dateFrom && $dateTo) {
    //         $query->whereBetween($dateColumn, [$dateFrom, $dateTo]);
    //     } elseif ($dateFrom) {
    //         $query->where($dateColumn, '>=', $dateFrom);
    //     } elseif ($dateTo) {
    //         $query->where($dateColumn, '<=', $dateTo);
    //     }

    //     $tasks = $query
    //     // ->orderBy($sortColumn, $sortOrder)
    //     ->paginate(10); // paginate بدل limit

    //     $clients = $logged_id_user->client_id 
    //         ? Client::where('id', $logged_id_user->client_id)->get()
    //         : Client::all();

    //     $locations = $logged_id_user->client_id
    //         ? Location::whereHas('clients', fn($q) => $q->where('client_id', $logged_id_user->client_id))->get()
    //         : Location::all();

    //     $drivers = Driver::all();

    //     return view('admin.tasks.index', compact('tasks','clients','locations','drivers'));
    // }



    public function pickupdelayed(Request $request)
    {
        $logged_id_user = auth()->user();
        abort_if(Gate::denies('task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($request->ajax()) {
            $query = Task::with(['from', 'to', 'client', 'driver', 'car'])->select(sprintf('%s.*', (new Task())->table));

            if( $logged_id_user->client_id != null)
            {
                $query = $query->where('billing_client', $logged_id_user->client_id);
            }
            ;




            // ->filter(function ($query)  use ($request) {
                $query= $query->whereRaw('pickup_time < collection_date');
                if ($request->status !=null) {
                    $query =  $query->where('status', '=',  $request->status );
                }
                if ($request->driver_id !=null) {
                    $query =  $query->where('driver_id', '=', $request->driver_id );
                }
                if ($request->billing_client !=null) {
                    $query =  $query->where('billing_client', '=', $request->billing_client );
                }
                if ($request->from_location !=null) {
                    $query =  $query->where('from_location', '=', $request->from_location );
                }
                if ($request->to_location !=null) {
                    $query =  $query->where('to_location', '=', $request->to_location );
                }
                if ($request->keyword !=null) {
                    $query =  $query->where('tasks.id', '=', $request->keyword );
                }
                if ($request->delayed_reason !=null) {
                    $query =  $query->where('tasks.delayed_reason', '=', $request->delayed_reason );
                }
                if($request->date_from !=null && $request->date_to !=null)
                {
                    $query =  $query->whereBetween('tasks.created_at',
                        [
                            Carbon::createFromDate($request->date_from )->toDateString(),
                            Carbon::createFromDate($request->date_to)
                            //  ->addDays(1)
                             ->toDateString()
                        ]
                        );
                } else{
                    if ($request->date_from !=null) {
                        $query =  $query->where('tasks.created_at', '>=', $request->date_from );
                    }
                    if ($request->date_to !=null) {
                        $query =   $query->where('tasks.created_at', '>=', $request->date_to );
                    }
                }

            // })
            // ;
            // \Log::info($sortColumn);
            // \Log::info( $sortOrder);
            $query = $query->orderBy($sortColumn, $sortOrder);
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->addColumn('sequence', function ($row) {
                static $index = 0;
                return ++$index;
            });
            $table->editColumn('actions', function ($row) {
                $viewGate = 'task_show';
                $editGate = 'task_edit';
                $deleteGate = 'task_delete';
                $crudRoutePart = 'tasks';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->addColumn('from_location_name', function ($row) {
                return $row->from ? $row->from->name : '';
            });

            $table->addColumn('to_location_name', function ($row) {
                return $row->to ? $row->to->name : '';
            });

            $table->addColumn('billing_client_status', function ($row) {
                return $row->client ? $row->client->english_name : '';
            });

            $table->addColumn('driver_name', function ($row) {
                return $row->driver ? $row->driver->name : '';
            });

            $table->addColumn('close_date', function ($row) {
                return $row->close_date ? $row->close_date : '';
            });

            $table->addColumn('car_imei', function ($row) {
                return $row->car ? $row->car->imei : '';
            });
            $table->addColumn('delayed_reason', function ($row) {
                return $row->delayed_reason ? $row->delayed_reason : '';
            });

            $table->addColumn('hours', function ($row) {
                return $row->close_date ? parent::hoursandmins(Period::make($row->collection_date,$row->close_date,  Precision::MINUTE())->length(), '%02d Hours, %02d Minutes')
                : '';
            });

            $table->editColumn('close_hour', function ($row) {
                return $row->close_hour ? $row->close_hour : '';
            });

            $table->editColumn('box_count', function ($row) {
                return $row->box_count ? $row->box_count : '';
            });
            $table->editColumn('sample_count', function ($row) {
                return $row->sample_count ? $row->sample_count : '';
            });
            $table->editColumn('type', function ($row) {
                return $row->type ? Task::TYPE_SELECT[$row->type] : '';
            });
            $table->editColumn('task_type', function ($row) {
                return $row->task_type ? Task::TASK_TYPE_SELECT[$row->task_type] : '';
            });
            $table->editColumn('confirmed_by_client', function ($row) {
                return $row->confirmed_by_client ? Task::CONFIRMED_BY_CLIENT_SELECT[$row->confirmed_by_client] : '';
            });
            $table->editColumn('ayenati', function ($row) {
                return $row->ayenati ? Task::AYENATI_SELECT[$row->ayenati] : '';
            });
            $table->editColumn('takasi', function ($row) {
                return $row->takasi ? Task::TAKASI_SELECT[$row->takasi] : '';
            });
            $table->editColumn('status', function ($row) {
                $map = [
                    'NEW'         => ['bg-primary',   'NEW'],
                    'COLLECTED'   => ['bg-info',      'COLLECTED'],
                    'IN_FREEZER'  => ['bg-warning',   'IN_FREEZER'],
                    'OUT_FREEZER' => ['bg-warning',   'OUT_FREEZER'],
                    'CLOSED'      => ['bg-success',   'CLOSED'],
                    'NO_SAMPLES'  => ['bg-secondary', 'NO_SAMPLES'],
                ];
                if (isset($map[$row->status])) {
                    return '<span class="badge ' . $map[$row->status][0] . '">' . $map[$row->status][1] . '</span>';
                }
                return $row->status ?? '';
            });
            $table->editColumn('added_by', function ($row) {
                return $row->added_by ? $row->added_by : '';
            });
            $table->editColumn('signature', function ($row) {
                return $row->signature ? $row->signature : '';
            });
            $table->editColumn('deliver_signature', function ($row) {
                return $row->deliver_signature ? $row->deliver_signature : '';
            });
            $table->editColumn('deliver_confirmation_code', function ($row) {
                return $row->deliver_confirmation_code ? $row->deliver_confirmation_code : '';
            });
            $table->editColumn('confirmation_code', function ($row) {
                return $row->confirmation_code ? $row->confirmation_code : '';
            });
            $table->editColumn('description', function ($row) {
                return $row->description ? $row->description : '';
            });

            $table->editColumn('takasi_number', function ($row) {
                return $row->takasi_number ? $row->takasi_number : '';
            });
            $table->editColumn('to_takasi_number', function ($row) {
                return $row->to_takasi_number ? $row->to_takasi_number : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'from_location', 'to_location', 'billing_client', 'driver', 'car', 'status']);

            return $table->make(true);
        } else{
            \Log::error("no ajax");
        }




        if( $logged_id_user->client_id != null)
        {
                $clients = Client::where('id', $logged_id_user->client_id)->get();
                $locations = Location::select('locations.*')
                ->leftJoin('client_location','client_location.location_id','locations.id')
                ->where('client_location.client_id',$logged_id_user->client_id)
                ->get();
                $drivers = Driver::all();
        } else{
            $clients = Client::all();
            $locations = Location::all();
            $drivers = Driver::all();
        }



        return view('admin.tasks.pickupdelayed',[
            'clients' =>  $clients,
            'locations' =>  $locations,
            'drivers' =>  $drivers
        ]);
    }

    public function dropdelayed(Request $request)
    {
        $logged_id_user = auth()->user();
        abort_if(Gate::denies('task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($request->ajax()) {
            $query = Task::with(['from', 'to', 'client', 'driver', 'car'])->select(sprintf('%s.*', (new Task())->table));

            if( $logged_id_user->client_id != null)
            {
                $query = $query->where('billing_client', $logged_id_user->client_id);
            }
            // ->when(request('status'), function ($q) {
            //     return $q->where('status', request('status'));
            // })
            // ->when(request('driver_id'), function ($q) {
            //     return $q->where('driver_id', request('driver_id'));
            // })
            ;
            $table = Datatables::of($query)

            ->filter(function ($query)  use ($request) {
                $query->whereRaw('dropoff_time < close_date');
                if ($request->status !=null) {
                    $query->where('status', '=',  $request->status );

                }
                if ($request->driver_id !=null) {
                    $query->where('driver_id', '=', $request->driver_id );
                }
                if ($request->billing_client !=null) {
                    $query->where('billing_client', '=', $request->billing_client );
                }
                if ($request->from_location !=null) {
                    $query->where('from_location', '=', $request->from_location );
                }
                if ($request->to_location !=null) {
                    $query->where('to_location', '=', $request->to_location );
                }
                if ($request->keyword !=null) {
                    $query->where('tasks.id', '=', $request->keyword );
                }
                if ($request->delayed_reason !=null) {
                    $query->where('tasks.delayed_reason', '=', $request->delayed_reason );
                }
                if($request->date_from !=null && $request->date_to !=null)
                {
                    $query->whereBetween('tasks.created_at',
                        [
                            Carbon::createFromDate($request->date_from )->toDateString(),
                            Carbon::createFromDate($request->date_to)
                            //  ->addDays(1)
                             ->toDateString()
                        ]
                        );
                } else{
                    if ($request->date_from !=null) {
                        $query->where('tasks.created_at', '>=', $request->date_from );
                    }
                    if ($request->date_to !=null) {
                        $query->where('tasks.created_at', '>=', $request->date_to );
                    }
                }

            })
            ;
            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->addColumn('sequence', function ($row) {
                static $index = 0;
                return ++$index;
            });
            $table->editColumn('actions', function ($row) {
                $viewGate = 'task_show';
                $editGate = 'task_edit';
                $deleteGate = 'task_delete';
                $crudRoutePart = 'tasks';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->addColumn('from_location_name', function ($row) {
                return $row->from ? $row->from->name : '';
            });

            $table->addColumn('to_location_name', function ($row) {
                return $row->to ? $row->to->name : '';
            });

            $table->addColumn('billing_client_status', function ($row) {
                return $row->client ? $row->client->english_name : '';
            });

            $table->addColumn('driver_name', function ($row) {
                return $row->driver ? $row->driver->name : '';
            });

            $table->addColumn('close_date', function ($row) {
                return $row->close_date ? $row->close_date : '';
            });

            $table->addColumn('car_imei', function ($row) {
                return $row->car ? $row->car->imei : '';
            });
            $table->addColumn('delayed_reason', function ($row) {
                return $row->delayed_reason ? $row->delayed_reason : '';
            });

            $table->addColumn('hours', function ($row) {
                return $row->close_date ? parent::hoursandmins(Period::make($row->collection_date,$row->close_date,  Precision::MINUTE())->length(), '%02d Hours, %02d Minutes')
                : '';
            });

            $table->editColumn('close_hour', function ($row) {
                return $row->close_hour ? $row->close_hour : '';
            });

            $table->editColumn('box_count', function ($row) {
                return $row->box_count ? $row->box_count : '';
            });
            $table->editColumn('sample_count', function ($row) {
                return $row->sample_count ? $row->sample_count : '';
            });
            $table->editColumn('type', function ($row) {
                return $row->type ? Task::TYPE_SELECT[$row->type] : '';
            });
            $table->editColumn('task_type', function ($row) {
                return $row->task_type ? Task::TASK_TYPE_SELECT[$row->task_type] : '';
            });
            $table->editColumn('confirmed_by_client', function ($row) {
                return $row->confirmed_by_client ? Task::CONFIRMED_BY_CLIENT_SELECT[$row->confirmed_by_client] : '';
            });
            $table->editColumn('ayenati', function ($row) {
                return $row->ayenati ? Task::AYENATI_SELECT[$row->ayenati] : '';
            });
            $table->editColumn('takasi', function ($row) {
                return $row->takasi ? Task::TAKASI_SELECT[$row->takasi] : '';
            });
            $table->editColumn('status', function ($row) {
                $map = [
                    'NEW'         => ['bg-primary',   'NEW'],
                    'COLLECTED'   => ['bg-info',      'COLLECTED'],
                    'IN_FREEZER'  => ['bg-warning',   'IN_FREEZER'],
                    'OUT_FREEZER' => ['bg-warning',   'OUT_FREEZER'],
                    'CLOSED'      => ['bg-success',   'CLOSED'],
                    'NO_SAMPLES'  => ['bg-secondary', 'NO_SAMPLES'],
                ];
                if (isset($map[$row->status])) {
                    return '<span class="badge ' . $map[$row->status][0] . '">' . $map[$row->status][1] . '</span>';
                }
                return $row->status ?? '';
            });
            $table->editColumn('added_by', function ($row) {
                return $row->added_by ? $row->added_by : '';
            });
            $table->editColumn('signature', function ($row) {
                return $row->signature ? $row->signature : '';
            });
            $table->editColumn('deliver_signature', function ($row) {
                return $row->deliver_signature ? $row->deliver_signature : '';
            });
            $table->editColumn('deliver_confirmation_code', function ($row) {
                return $row->deliver_confirmation_code ? $row->deliver_confirmation_code : '';
            });
            $table->editColumn('confirmation_code', function ($row) {
                return $row->confirmation_code ? $row->confirmation_code : '';
            });
            $table->editColumn('description', function ($row) {
                return $row->description ? $row->description : '';
            });

            $table->editColumn('takasi_number', function ($row) {
                return $row->takasi_number ? $row->takasi_number : '';
            });
            $table->editColumn('to_takasi_number', function ($row) {
                return $row->to_takasi_number ? $row->to_takasi_number : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'from_location', 'to_location', 'billing_client', 'driver', 'car', 'status']);

            return $table->make(true);
        } else{
            \Log::error("no ajax");
        }




        if( $logged_id_user->client_id != null)
        {
            // \Log::info("message");
                $clients = Client::where('id', $logged_id_user->client_id)->get();
                $locations = Location::select('locations.*')
                ->leftJoin('client_location','client_location.location_id','locations.id')
                ->where('client_location.client_id',$logged_id_user->client_id)
                ->get();
                $drivers = Driver::all();
        } else{
            $clients = Client::all();
            $locations = Location::all();
            $drivers = Driver::all();
        }



        return view('admin.tasks.dropdelayed',[
            'clients' =>  $clients,
            'locations' =>  $locations,
            'drivers' =>  $drivers
        ]);
    }

    public function collectedDelayed(Request $request)
    {
        $logged_id_user = auth()->user();
        abort_if(Gate::denies('task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($request->ajax()) {
            $query = Task::with(['from', 'to', 'client', 'driver', 'car'])->select(sprintf('%s.*', (new Task())->table));

            if( $logged_id_user->client_id != null)
            {
                $query = $query->where('billing_client', $logged_id_user->client_id);
            }
            // ->when(request('status'), function ($q) {
            //     return $q->where('status', request('status'));
            // })
            // ->when(request('driver_id'), function ($q) {
            //     return $q->where('driver_id', request('driver_id'));
            // })
            ;
            $table = Datatables::of($query)

            ->filter(function ($query)  use ($request) {
                $query ->whereRaw('TIMESTAMPDIFF(MINUTE,  freezer_out_date,NOW() ) > 5')
                ->where('status','OUT_FREEZER');
                if ($request->status !=null) {
                    $query->where('status', '=',  $request->status );
                }
                if ($request->driver_id !=null) {
                    $query->where('driver_id', '=', $request->driver_id );
                }
                if ($request->billing_client !=null) {
                    $query->where('billing_client', '=', $request->billing_client );
                }
                if ($request->from_location !=null) {
                    $query->where('from_location', '=', $request->from_location );
                }
                if ($request->to_location !=null) {
                    $query->where('to_location', '=', $request->to_location );
                }
                if ($request->keyword !=null) {
                    $query->where('tasks.id', '=', $request->keyword );
                }
                if ($request->delayed_reason !=null) {
                    $query->where('tasks.delayed_reason', '=', $request->delayed_reason );
                }
                if($request->date_from !=null && $request->date_to !=null)
                {
                    $query->whereBetween('tasks.created_at',
                        [
                            Carbon::createFromDate($request->date_from )->toDateString(),
                            Carbon::createFromDate($request->date_to)
                            //  ->addDays(1)
                             ->toDateString()
                        ]
                        );
                } else{
                    if ($request->date_from !=null) {
                        $query->where('tasks.created_at', '>=', $request->date_from );
                    }
                    if ($request->date_to !=null) {
                        $query->where('tasks.created_at', '>=', $request->date_to );
                    }
                }

            })
            ;
            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->addColumn('sequence', function ($row) {
                static $index = 0;
                return ++$index;
            });
            $table->editColumn('actions', function ($row) {
                $viewGate = 'task_show';
                $editGate = 'task_edit';
                $deleteGate = 'task_delete';
                $crudRoutePart = 'tasks';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->addColumn('from_location_name', function ($row) {
                return $row->from ? $row->from->name : '';
            });

            $table->addColumn('to_location_name', function ($row) {
                return $row->to ? $row->to->name : '';
            });

            $table->addColumn('billing_client_status', function ($row) {
                return $row->client ? $row->client->english_name : '';
            });

            $table->addColumn('driver_name', function ($row) {
                return $row->driver ? $row->driver->name : '';
            });

            $table->addColumn('close_date', function ($row) {
                return $row->close_date ? $row->close_date : '';
            });

            $table->addColumn('car_imei', function ($row) {
                return $row->car ? $row->car->imei : '';
            });
            $table->addColumn('delayed_reason', function ($row) {
                return $row->delayed_reason ? $row->delayed_reason : '';
            });

            $table->addColumn('hours', function ($row) {
                return $row->close_date ? parent::hoursandmins(Period::make($row->collection_date,$row->close_date,  Precision::MINUTE())->length(), '%02d Hours, %02d Minutes')
                : '';
            });

            $table->editColumn('close_hour', function ($row) {
                return $row->close_hour ? $row->close_hour : '';
            });

            $table->editColumn('box_count', function ($row) {
                return $row->box_count ? $row->box_count : '';
            });
            $table->editColumn('sample_count', function ($row) {
                return $row->sample_count ? $row->sample_count : '';
            });
            $table->editColumn('type', function ($row) {
                return $row->type ? Task::TYPE_SELECT[$row->type] : '';
            });
            $table->editColumn('task_type', function ($row) {
                return $row->task_type ? Task::TASK_TYPE_SELECT[$row->task_type] : '';
            });
            $table->editColumn('confirmed_by_client', function ($row) {
                return $row->confirmed_by_client ? Task::CONFIRMED_BY_CLIENT_SELECT[$row->confirmed_by_client] : '';
            });
            $table->editColumn('ayenati', function ($row) {
                return $row->ayenati ? Task::AYENATI_SELECT[$row->ayenati] : '';
            });
            $table->editColumn('takasi', function ($row) {
                return $row->takasi ? Task::TAKASI_SELECT[$row->takasi] : '';
            });
            $table->editColumn('status', function ($row) {
                $map = [
                    'NEW'         => ['bg-primary',   'NEW'],
                    'COLLECTED'   => ['bg-info',      'COLLECTED'],
                    'IN_FREEZER'  => ['bg-warning',   'IN_FREEZER'],
                    'OUT_FREEZER' => ['bg-warning',   'OUT_FREEZER'],
                    'CLOSED'      => ['bg-success',   'CLOSED'],
                    'NO_SAMPLES'  => ['bg-secondary', 'NO_SAMPLES'],
                ];
                if (isset($map[$row->status])) {
                    return '<span class="badge ' . $map[$row->status][0] . '">' . $map[$row->status][1] . '</span>';
                }
                return $row->status ?? '';
            });
            $table->editColumn('added_by', function ($row) {
                return $row->added_by ? $row->added_by : '';
            });
            $table->editColumn('signature', function ($row) {
                return $row->signature ? $row->signature : '';
            });
            $table->editColumn('deliver_signature', function ($row) {
                return $row->deliver_signature ? $row->deliver_signature : '';
            });
            $table->editColumn('deliver_confirmation_code', function ($row) {
                return $row->deliver_confirmation_code ? $row->deliver_confirmation_code : '';
            });
            $table->editColumn('confirmation_code', function ($row) {
                return $row->confirmation_code ? $row->confirmation_code : '';
            });
            $table->editColumn('description', function ($row) {
                return $row->description ? $row->description : '';
            });

            $table->editColumn('takasi_number', function ($row) {
                return $row->takasi_number ? $row->takasi_number : '';
            });
            $table->editColumn('to_takasi_number', function ($row) {
                return $row->to_takasi_number ? $row->to_takasi_number : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'from_location', 'to_location', 'billing_client', 'driver', 'car', 'status']);

            return $table->make(true);
        } else{
            \Log::error("no ajax");
        }




        if( $logged_id_user->client_id != null)
        {
            // \Log::info("message");
                $clients = Client::where('id', $logged_id_user->client_id)->get();
                $locations = Location::select('locations.*')
                ->leftJoin('client_location','client_location.location_id','locations.id')
                ->where('client_location.client_id',$logged_id_user->client_id)
                ->get();
                $drivers = Driver::all();
        } else{
            $clients = Client::all();
            $locations = Location::all();
            $drivers = Driver::all();
        }



        return view('admin.tasks.collectedDelayed',[
            'clients' =>  $clients,
            'locations' =>  $locations,
            'drivers' =>  $drivers
        ]);
    }

    public function outfreezerdelayed(Request $request)
    {
        $logged_id_user = auth()->user();
        abort_if(Gate::denies('task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($request->ajax()) {
            $query = Task::with(['from', 'to', 'client', 'driver', 'car'])->select(sprintf('%s.*', (new Task())->table));

            if( $logged_id_user->client_id != null)
            {
                $query = $query->where('billing_client', $logged_id_user->client_id);
            }
            // ->when(request('status'), function ($q) {
            //     return $q->where('status', request('status'));
            // })
            // ->when(request('driver_id'), function ($q) {
            //     return $q->where('driver_id', request('driver_id'));
            // })
            ;
            $table = Datatables::of($query)

            ->filter(function ($query)  use ($request) {
                $query->whereRaw('TIMESTAMPDIFF(MINUTE,  collection_date,NOW() ) > 10')->where('status','COLLECTED');
                if ($request->status !=null) {
                    $query->where('status', '=',  $request->status );
                }
                if ($request->driver_id !=null) {
                    $query->where('driver_id', '=', $request->driver_id );
                }
                if ($request->billing_client !=null) {
                    $query->where('billing_client', '=', $request->billing_client );
                }
                if ($request->from_location !=null) {
                    $query->where('from_location', '=', $request->from_location );
                }
                if ($request->to_location !=null) {
                    $query->where('to_location', '=', $request->to_location );
                }
                if ($request->keyword !=null) {
                    $query->where('tasks.id', '=', $request->keyword );
                }
                if ($request->delayed_reason !=null) {
                    $query->where('tasks.delayed_reason', '=', $request->delayed_reason );
                }
                if($request->date_from !=null && $request->date_to !=null)
                {
                    $query->whereBetween('tasks.created_at',
                        [
                            Carbon::createFromDate($request->date_from )->toDateString(),
                            Carbon::createFromDate($request->date_to)
                            //  ->addDays(1)
                             ->toDateString()
                        ]
                        );
                } else{
                    if ($request->date_from !=null) {
                        $query->where('tasks.created_at', '>=', $request->date_from );
                    }
                    if ($request->date_to !=null) {
                        $query->where('tasks.created_at', '>=', $request->date_to );
                    }
                }

            })
            ;
            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->addColumn('sequence', function ($row) {
                static $index = 0;
                return ++$index;
            });
            $table->editColumn('actions', function ($row) {
                $viewGate = 'task_show';
                $editGate = 'task_edit';
                $deleteGate = 'task_delete';
                $crudRoutePart = 'tasks';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->addColumn('from_location_name', function ($row) {
                return $row->from ? $row->from->name : '';
            });

            $table->addColumn('to_location_name', function ($row) {
                return $row->to ? $row->to->name : '';
            });

            $table->addColumn('billing_client_status', function ($row) {
                return $row->client ? $row->client->english_name : '';
            });

            $table->addColumn('driver_name', function ($row) {
                return $row->driver ? $row->driver->name : '';
            });

            $table->addColumn('close_date', function ($row) {
                return $row->close_date ? $row->close_date : '';
            });

            $table->addColumn('car_imei', function ($row) {
                return $row->car ? $row->car->imei : '';
            });
            $table->addColumn('delayed_reason', function ($row) {
                return $row->delayed_reason ? $row->delayed_reason : '';
            });

            $table->addColumn('hours', function ($row) {
                return $row->close_date ? parent::hoursandmins(Period::make($row->collection_date,$row->close_date,  Precision::MINUTE())->length(), '%02d Hours, %02d Minutes')
                : '';
            });

            $table->editColumn('close_hour', function ($row) {
                return $row->close_hour ? $row->close_hour : '';
            });

            $table->editColumn('box_count', function ($row) {
                return $row->box_count ? $row->box_count : '';
            });
            $table->editColumn('sample_count', function ($row) {
                return $row->sample_count ? $row->sample_count : '';
            });
            $table->editColumn('type', function ($row) {
                return $row->type ? Task::TYPE_SELECT[$row->type] : '';
            });
            $table->editColumn('task_type', function ($row) {
                return $row->task_type ? Task::TASK_TYPE_SELECT[$row->task_type] : '';
            });
            $table->editColumn('confirmed_by_client', function ($row) {
                return $row->confirmed_by_client ? Task::CONFIRMED_BY_CLIENT_SELECT[$row->confirmed_by_client] : '';
            });
            $table->editColumn('ayenati', function ($row) {
                return $row->ayenati ? Task::AYENATI_SELECT[$row->ayenati] : '';
            });
            $table->editColumn('takasi', function ($row) {
                return $row->takasi ? Task::TAKASI_SELECT[$row->takasi] : '';
            });
            $table->editColumn('status', function ($row) {
                $map = [
                    'NEW'         => ['bg-primary',   'NEW'],
                    'COLLECTED'   => ['bg-info',      'COLLECTED'],
                    'IN_FREEZER'  => ['bg-warning',   'IN_FREEZER'],
                    'OUT_FREEZER' => ['bg-warning',   'OUT_FREEZER'],
                    'CLOSED'      => ['bg-success',   'CLOSED'],
                    'NO_SAMPLES'  => ['bg-secondary', 'NO_SAMPLES'],
                ];
                if (isset($map[$row->status])) {
                    return '<span class="badge ' . $map[$row->status][0] . '">' . $map[$row->status][1] . '</span>';
                }
                return $row->status ?? '';
            });
            $table->editColumn('added_by', function ($row) {
                return $row->added_by ? $row->added_by : '';
            });
            $table->editColumn('signature', function ($row) {
                return $row->signature ? $row->signature : '';
            });
            $table->editColumn('deliver_signature', function ($row) {
                return $row->deliver_signature ? $row->deliver_signature : '';
            });
            $table->editColumn('deliver_confirmation_code', function ($row) {
                return $row->deliver_confirmation_code ? $row->deliver_confirmation_code : '';
            });
            $table->editColumn('confirmation_code', function ($row) {
                return $row->confirmation_code ? $row->confirmation_code : '';
            });
            $table->editColumn('description', function ($row) {
                return $row->description ? $row->description : '';
            });

            $table->editColumn('takasi_number', function ($row) {
                return $row->takasi_number ? $row->takasi_number : '';
            });
            $table->editColumn('to_takasi_number', function ($row) {
                return $row->to_takasi_number ? $row->to_takasi_number : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'from_location', 'to_location', 'billing_client', 'driver', 'car', 'status']);

            return $table->make(true);
        } else{
            \Log::error("no ajax");
        }




        if( $logged_id_user->client_id != null)
        {
            // \Log::info("message");
                $clients = Client::where('id', $logged_id_user->client_id)->get();
                $locations = Location::select('locations.*')
                ->leftJoin('client_location','client_location.location_id','locations.id')
                ->where('client_location.client_id',$logged_id_user->client_id)
                ->get();
                $drivers = Driver::all();
        } else{
            $clients = Client::all();
            $locations = Location::all();
            $drivers = Driver::all();
        }



        return view('admin.tasks.outfreezerdelayed',[
            'clients' =>  $clients,
            'locations' =>  $locations,
            'drivers' =>  $drivers
        ]);
    }

    public function exportExcelDetails(Request $request)
    {
        // Collect filters
        $filters = [
            'status'         => $request->input('status'),
            'date_from'      => $request->input('date_from'),
            'date_to'        => $request->input('date_to'),
            'billing_client' => $request->input('billing_client'),
            'from_location'  => $request->input('from_location'),
            'to_location'    => $request->input('to_location'),
            'driver_id'      => $request->input('driver_id'),
        ];

        // SECURITY (fail-closed): scope the export to the user's own client OR refuse it
        // entirely if the user is neither an admin nor bound to a client.
        $loggedUser = auth()->user();
        $isAdminUser = $loggedUser && (
            $loggedUser->hasAnyRole(['Admin', 'Super Admin'])
            || method_exists($loggedUser, 'getIsAdminAttribute') && $loggedUser->is_admin
        );
        if ($loggedUser && $loggedUser->client_id) {
            $filters['billing_client'] = $loggedUser->client_id;
        } elseif (!$isAdminUser) {
            // Not bound to a client AND not an admin — refuse to leak data.
            abort(403, 'You are not allowed to export.');
        }

        // Garbage-collect old exports (older than 1 hour) so storage doesn't grow forever.
        $exportsDir = storage_path('app/exports');
        if (is_dir($exportsDir)) {
            $cutoff = time() - 3600;
            foreach ((array) @scandir($exportsDir) as $entry) {
                if ($entry === '.' || $entry === '..' || $entry === false) continue;
                $p = $exportsDir . DIRECTORY_SEPARATOR . $entry;
                if (is_file($p) && @filemtime($p) < $cutoff) {
                    @unlink($p);
                }
            }
        }

        // Generate a unique token for this export
        $token = \Illuminate\Support\Str::random(40);

        // Dispatch strategy depends on queue connection:
        // - sync (typical for local dev): use afterResponse() so the redirect is sent FIRST,
        //   then the job runs in the same PHP process AFTER the connection is closed. No
        //   HTTP timeout to worry about.
        // - database / redis (production with a queue worker): regular dispatch pushes the
        //   job to the worker, which runs it independently of the HTTP request.
        if (config('queue.default') === 'sync') {
            \App\Jobs\GenerateTaskExportJob::dispatch($token, $filters, $loggedUser?->id)->afterResponse();
        } else {
            \App\Jobs\GenerateTaskExportJob::dispatch($token, $filters, $loggedUser?->id);
        }

        // Redirect the user to a polling page that auto-downloads the file when ready
        return redirect()->route('admin.tasks.export.status', ['token' => $token]);
    }

    /**
     * Status / download page for a queued task export.
     * - If the job is still running, shows a "preparing" page that auto-polls.
     * - If the job has finished, serves the .xlsx file as a download.
     * - If the job errored, shows the error.
     */
    public function exportStatus(Request $request, string $token)
    {
        // Validate token shape — prevents path traversal
        if (!preg_match('/^[A-Za-z0-9]{40}$/', $token)) {
            abort(404);
        }

        $dir       = storage_path('app/exports');
        $path      = $dir . DIRECTORY_SEPARATOR . $token . '.xlsx';
        $doneFlag  = $path . '.done';
        $errorFlag = $path . '.error';

        // JSON status endpoint for the polling page
        if ($request->wantsJson() || $request->boolean('status')) {
            if (is_file($errorFlag)) {
                return response()->json([
                    'state' => 'error',
                    'error' => (string) @file_get_contents($errorFlag),
                ]);
            }
            if (is_file($doneFlag) && is_file($path)) {
                return response()->json([
                    'state'    => 'ready',
                    'count'    => (int) @file_get_contents($doneFlag),
                    'download' => route('admin.tasks.export.status', ['token' => $token, 'download' => 1]),
                ]);
            }
            return response()->json(['state' => 'pending']);
        }

        // Download endpoint — serves the file. We keep the file on disk so the user can
        // re-download if needed; old files are garbage-collected by exportExcelDetails().
        if ($request->boolean('download')) {
            if (!is_file($doneFlag) || !is_file($path)) {
                abort(410, 'This export is no longer available. Please generate a new one.');
            }
            $filename = 'task_time_report_' . now()->format('Y-m-d_His') . '.xlsx';
            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        // Default — render the polling page
        return view('admin.tasks.export-pending', ['token' => $token]);
    }


    // public function exportExcelDetails(Request $request){

    //     return Excel::download(new TaskTimeReportExport(), 'task_time_report.xlsx');
    // }

    public function create()
    {
        abort_if(Gate::denies('task_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');




        $logged_id_user = auth()->user();
        if($logged_id_user->client_id != null)
        {
            // $from_locations = Location::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

            $from_locations = Location::select('locations.*')
            ->leftJoin('client_location','client_location.location_id','locations.id')
            ->where('client_location.client_id',$logged_id_user->client_id)
            ->pluck('name', 'id');
            // ->get();
            $to_locations = $from_locations  ;//Location::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

            $billing_clients = Client::where('id',$logged_id_user->client_id)->pluck('english_name', 'id')->prepend(trans('translation.pleaseSelect'), '');

            $drivers = Driver::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

            $cars = Car::pluck('imei', 'id')->prepend(trans('translation.pleaseSelect'), '');
        } else{
            $from_locations = Location::pluck('name', 'id');

            $to_locations = Location::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

            $billing_clients = Client::pluck('english_name', 'id')->prepend(trans('translation.pleaseSelect'), '');

            $drivers = Driver::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

            $cars = Car::pluck('imei', 'id')->prepend(trans('translation.pleaseSelect'), '');
        }


        return view('admin.tasks.create', compact('billing_clients', 'cars', 'drivers', 'from_locations', 'to_locations'));
    }

    public function unUsedTasks(Request $request)
    {
        abort_if(Gate::denies('unused_tasks'), Response::HTTP_FORBIDDEN, '403 Forbidden');
	    $tasks = Task::leftjoin('clients','clients.id','=','tasks.billing_client')
            ->leftjoin('drivers','drivers.id','=','tasks.driver_id')
            ->leftjoin('locations as from','from.id','=','tasks.from_location')
            ->leftjoin('locations as to','to.id','=','tasks.to_location')
            ->where('drivers.status', 1)
            ->select('tasks.*','clients.english_name','drivers.name as dname','from.name as from_name','to.name as to_name')->onlyTrashed()->where('is_unused',true);
        if (!empty($request->input('client_id'))){
            $tasks = $tasks->where('clients.id',$request->input('client_id'));
        }
        if (!empty($request->input('driver_id'))){
            $tasks = $tasks->where('drivers.id',$request->input('driver_id'));
        }
        if($request->date_from !=null && $request->date_to !=null)
        {
            $tasks = $tasks->whereBetween('tasks.created_at', [
                Carbon::createFromFormat('Y-m-d\TH:i', $request->date_from)->toDateTimeString(),
                Carbon::createFromFormat('Y-m-d\TH:i', $request->date_to)->toDateTimeString(),
            ]);
        } else {
            if ($request->date_from !=null) {
                $tasks =  $tasks->where('tasks.created_at', '>=', Carbon::createFromFormat('Y-m-d\TH:i', $request->date_from)->toDateTimeString());
            }
            if ($request->date_to !=null) {
                $tasks =  $tasks->where('tasks.created_at', '<=', Carbon::createFromFormat('Y-m-d\TH:i', $request->date_to)->toDateTimeString());
            }
        }
        if ($request->ajax()) {
            return DataTables::of($tasks)
                ->make(true);
        }

        $clients = Client::all();
        $locations = Location::all();
        $drivers = Driver::all();
        return view('admin.tasks.un_used',['clients' =>  $clients, 'locations' =>  $locations, 'drivers' =>  $drivers]);

    }

    public function scan()
    {
        abort_if(Gate::denies('task_scan'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $logged_id_user = auth()->user();
        if($logged_id_user->client_id != null) {
            $to_locations = Location::leftJoin('client_location','client_location.location_id','locations.id')
            ->where('client_location.client_id',$logged_id_user->client_id)->orderBy('name','asc')->pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');
        } else {
        $to_locations = Location::orderBy('name','asc')->pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');
        }
 
        $drivers = Driver::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

        return view('admin.tasks.scan', compact('drivers', 'to_locations'));
    }

    public function missing()
    {
        abort_if(Gate::denies('task_missing'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.tasks.missing');
    }

    public function store(StoreTaskRequest $request)
    {
        $logged_id_user = auth()->user();
        // $request->added_by = $logged_id_user->email;
        $driver = Driver::find( $request->driver_id);
        for ($i=0; $i < $request->time_of_visit; $i++) {
            foreach ($request->from_location as $from_location) {
                $task = new Task();//::create($request->all());
                $task->to_location = $request->to_location;
                $task->type = $request->type;
                $task->pickup_time = $request->pickup_time;
                $task->dropoff_time = $request->dropoff_time;
                $task->takasi = $request->takasi;
                $task->time_of_visit = $request->time_of_visit;
                $task->task_type = $request->task_type;
                $task->driver_id = $request->driver_id;
                $task->billing_client = $request->billing_client;
                $task->from_location = $from_location;
                $task->added_by = $logged_id_user->email;
                $task->created_at = now();
                
                if ($driver) {
                    $task->eta = $this->calcETA($driver, $from_location, $request->to_location);
                } else {
                    $task->eta = null;
                }

                $task->save();

                if ($driver) {
                    $driver->sendNotification('New Task', 'You have new task', [$driver->fcm_token], $task, 'open_task');
                }
            }
        }


        return redirect()->route('admin.tasks.index');
    }

    // public function calcETA($driver, $fromLocationId, $toLocationId)
    // {
    //     $fromLocation = Location::find($fromLocationId);
    //     $toLocation   = Location::find($toLocationId);

    //     if (!$fromLocation || !$toLocation) {
    //         return 0;
    //     }
        
    //     $lastTask = $driver->driverActiveTasks()
    //     ->whereDate('pickup_time', today())->orderBy('poririty', 'desc')->first();
    //     // \Log::info($lastTask);
    //     if ($lastTask) {
    //         $lastToLocation = Location::find($lastTask->to_location);
    //         $startPoint = $lastToLocation 
    //             ? $lastToLocation->lat . ',' . $lastToLocation->lng 
    //             : ($fromLocation->lat . ',' . $fromLocation->lng);
    //     } else {
    //         $car = $driver->car;
    //         $carTracking = $car?->carTracking()->first();
    //         if ($carTracking) {
    //             $startPoint = $carTracking->lat . ',' . $carTracking->lng;
    //         } else {
    //             $startPoint = $fromLocation->lat . ',' . $fromLocation->lng;
    //         }
    //     }

    //     $fromPoint = $fromLocation->lat . ',' . $fromLocation->lng;
    //     $toPoint   = $toLocation->lat . ',' . $toLocation->lng;

    //     $time1 = $this->getTravelTime($startPoint, $fromPoint);
    //     // \Log::info($time1);

    //     $time2 = $this->getTravelTime($fromPoint, $toPoint);
    //     // \Log::info($time2);

    //     $totalSeconds = $time1 + $time2;
    //     // \Log::info($totalSeconds);

    //     $waitingTime = 0;
    //     if ($lastTask && $lastTask->eta) {
    //         $waitingTime = intval($lastTask->eta) * 60;
    //     }

    //     $totalSeconds += $waitingTime;
    //     // \Log::info($totalSeconds);

    //     return (int) ceil($totalSeconds / 60);
    // }

    public function calcETA($driver, $fromLocationId, $toLocationId)
    {
        $fromLocation = Location::find($fromLocationId);
        $toLocation   = Location::find($toLocationId);

        if (!$fromLocation || !$toLocation || !$driver) {
            return null;
        }

        $lastTask = $driver->driverActiveTasks()
            ->whereDate('pickup_time', today())
            ->orderBy('poririty', 'desc')
            ->first();

        // تحديد نقطة الانطلاق
        if ($lastTask) {
            $lastToLocation = Location::find($lastTask->to_location);

            $startPoint = $lastToLocation
                ? $lastToLocation->lat . ',' . $lastToLocation->lng
                : $fromLocation->lat . ',' . $fromLocation->lng;
        } else {
            $car = $driver->car;
            $carTracking = $car?->carTracking()->first();

            if ($carTracking) {
                $startPoint = $carTracking->lat . ',' . $carTracking->lng;
            } else {
                $startPoint = $fromLocation->lat . ',' . $fromLocation->lng;
            }
        }

        $fromPoint = $fromLocation->lat . ',' . $fromLocation->lng;
        $toPoint   = $toLocation->lat . ',' . $toLocation->lng;

        $time1 = $this->getTravelTime($startPoint, $fromPoint);
        $time2 = $this->getTravelTime($fromPoint, $toPoint);

        $totalSeconds = $time1 + $time2;

        // ===============================
        // حساب ETA المتبقي من pickup_time
        // ===============================
        $waitingTime = 0;

        if ($lastTask && $lastTask->eta && $lastTask->pickup_time) {

            $elapsedSeconds = now()->diffInSeconds(
                \Carbon\Carbon::parse($lastTask->pickup_time),
                false
            );

            if ($elapsedSeconds < 0) {
                $elapsedSeconds = 0;
            }

            $totalEtaSeconds = intval($lastTask->eta) * 60;

            $remainingSeconds = max(
                $totalEtaSeconds - $elapsedSeconds,
                0
            );

            $waitingTime = $remainingSeconds;
        }

        $totalSeconds += $waitingTime;

        // إذا صفر → null
        if ($totalSeconds <= 0) {
            return null;
        }

        return (int) ceil($totalSeconds / 60);
    }


    public function getLocations($clientId, Request $request) {
        try {
            $clientToken = $request->bearerToken();
            $client = Client::find($clientId);
            if (!$client) {
                return response()->json(['success' => false, 'message' => 'No client found'], 404);
            }
            if ($clientToken !== $client->api_token) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $locations = Location::select('locations.*')
            ->leftJoin('client_location','client_location.location_id','locations.id')
            ->where('client_location.client_id',$clientId)
            ->pluck('name', 'id');

            return response()->json([
                'success' => true,
                'message' => 'Get locations',
                'data' => $locations
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'System error'], 500);
        }
    }

    public function createCustomTask($clientId, Request $request) {
        try {
            $clientToken = $request->bearerToken();
            $client = Client::find($clientId);
            if (!$client) {
                return response()->json(['success' => false, 'message' => 'No client found'], 404);
            }
            if ($clientToken !== $client->api_token) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }
            $logged_user = User::where('client_id', $clientId)->latest()->first();
            $logged_id_user = $logged_user;
            if (!$logged_user) {
                return response()->json(['success' => false, 'message' => 'No user found for this client'], 404);
            }

            $validator = \Validator::make($request->all(), [
                'from_location' => 'required|string|max:255',
                'to_location'   => 'required|string|max:255',
                'type'          => 'required|string|max:50',
                'pickup_time'   => 'required|date',
                'dropoff_time'  => 'required|date|after_or_equal:pickup_time',
                'takasi'        => 'nullable|string|max:255',
                'time_of_visit' => 'required|integer|min:1',
                'task_type'     => 'required|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors(),
                ], 422);
            }

            for ($i=0; $i < $request->time_of_visit; $i++) {
                // $task = new Task();
                // $task->from_location = $request->from_location;
                // $task->to_location = $request->to_location;
                // $task->type = $request->type;
                // $task->pickup_time = $request->pickup_time;
                // $task->dropoff_time = $request->dropoff_time;
                // $task->takasi = $request->takasi;
                // $task->time_of_visit = $request->time_of_visit;
                // $task->task_type = $request->task_type;
                // $task->billing_client = $clientId;
                // $task->added_by = $logged_id_user->email;
                // $task->created_at = now();
                // $task->eta = null;
                // $task->save();
            }
            // \Log::info('This from THIRD API');
            return response()->json([
                'success' => true,
                'message' => 'Task created with love',
                // 'data' => $task
                'data' => []
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'System error'], 500);
        }
    }


    private function getTravelTime($origin, $destination)
    {
        // \Log::info("Origin: ");
        // \Log::info($origin);
        // \Log::info("Destination: ");
        // \Log::info($destination);
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'query' => [
                    'origins' => $origin,
                    'destinations' => $destination,
                    'key' => 'AIzaSyCBu_5dYX7nfDtJ1mzrsumkMmhmymoDvN0',
                    // 'key' => 'AIzaSyDf1ht01vFyWcfWS33mmddf30qm5-uyWhM',
                    // 'key' => 'AIzaSyBVDsDozMW1t5KyW1vawIaldLIGhyVAi2c',
                    'mode' => 'driving',
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            // \Log::info("Data: ");
            // \Log::info($data);
            if (!empty($data['rows'][0]['elements'][0]['duration']['value'])) {
                return $data['rows'][0]['elements'][0]['duration']['value'];
            }
        } catch (\Exception $e) {
            \Log::error('Google Maps API error: ' . $e->getMessage());
        }

        return 0;
    }

    public function edit(Task $task)
    {
        abort_if(Gate::denies('task_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $from_locations = Location::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

        $to_locations = Location::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

        $billing_clients = Client::pluck('english_name', 'id')->prepend(trans('translation.pleaseSelect'), '');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

        $cars = Car::pluck('imei', 'id')->prepend(trans('translation.pleaseSelect'), '');

        $task->load('from', 'to', 'client', 'driver', 'car');

        // \Log::info($task);
        return view('admin.tasks.edit', compact('billing_clients', 'cars', 'drivers', 'from_locations', 'task', 'to_locations'));
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $data = $request->all();

        $ldate = now();
        if ($request->has('status') && $request->status == 'CLOSED') {
            $data['closed_by'] = 'admin'; // Add the 'closed_by' field with the value 'admin'
        }

        if (($task->status != 'CLOSED') && ($request->status == 'CLOSED')){
            $data['close_date'] = $ldate;
            $data['to_location_confirmation_timestamp'] = now();
        }
        $task->update($data);

        if (($task->status != 'CLOSED') && ($request->status == 'CLOSED')) {
            $logService = new LogService();
            $from_location = Location::find($task->from_location);
            if (isset($from_location->id)) {
                $with_blazma = $from_location->integration_branch_id ?? false;
                if ($with_blazma && $logService->hasIntegration($task)) {
                    dispatch(new LogData($task, 'delivered', $ldate));
                }
            }
        }

        return redirect()->route('admin.tasks.index');
    }

    public function show(Task $task)
    {

        abort_if(Gate::denies('task_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $task->load('from', 'to', 'client', 'driver', 'car');
        $bags = Sample::where('task_id',$task->id)->get()->groupBy('bag_code');
        $bag_count = Sample::where('task_id',$task->id)->distinct('bag_code')->count('bag_code');
        $sample_count = Sample::where('task_id',$task->id)->count();
	 $carTracking = DB::table('car_tracking')
            ->select(
                DB::raw("count(id) AS cnt"),
                DB::raw("COALESCE(ROUND(SUM(temp5),2),'0') AS total_temp_1"),
                DB::raw("COALESCE(ROUND(SUM(temp6),2),'0') AS total_temp_2"),
                DB::raw("COALESCE(ROUND(SUM(temp7),2),'0') AS total_temp_3"),
            )->where('task_id',$task->id)->first();
$temperatureReadings = DB::table('car_tracking')
    ->select('created_at', 'temp5', 'temp6', 'temp7')
    ->where('task_id', $task->id)
    ->orderBy('created_at')
    ->get();

// Prepare data for chart
$labels = $temperatureReadings->pluck('created_at')->map(function ($time) {
    return \Carbon\Carbon::parse($time)->format('H:i');
});

$temp1 = $temperatureReadings->pluck('temp5');
$temp2 = $temperatureReadings->pluck('temp6');
$temp3 = $temperatureReadings->pluck('temp7');
//dd($carTracking);
        return view('admin.tasks.show', compact('task','bags','bag_count','sample_count','carTracking', 'labels', 'temp1', 'temp2', 'temp3'));
    }
    
        public function newshow($id)
    {

        abort_if(Gate::denies('task_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $task = Task::where('id', $id)->first();
        $task->load('from', 'to', 'client', 'driver', 'car');
        $bags = Sample::where('task_id',$task->id)->get()->groupBy('bag_code');
        $bag_count = Sample::where('task_id',$task->id)->distinct('bag_code')->count('bag_code');
        $sample_count = Sample::where('task_id',$task->id)->count();
	 $carTracking = DB::table('car_tracking')
            ->select(
                DB::raw("count(id) AS cnt"),
                DB::raw("COALESCE(ROUND(SUM(temp5),2),'0') AS total_temp_1"),
                DB::raw("COALESCE(ROUND(SUM(temp6),2),'0') AS total_temp_2"),
                DB::raw("COALESCE(ROUND(SUM(temp7),2),'0') AS total_temp_3"),
            )->where('task_id',$task->id)->first();
$temperatureReadings = DB::table('car_tracking')
    ->select('created_at', 'temp5', 'temp6', 'temp7')
    ->where('task_id', $task->id)
    ->orderBy('created_at')
    ->get();

// Prepare data for chart
$labels = $temperatureReadings->pluck('created_at')->map(function ($time) {
    return \Carbon\Carbon::parse($time)->format('H:i');
});

$temp1 = $temperatureReadings->pluck('temp5');
$temp2 = $temperatureReadings->pluck('temp6');
$temp3 = $temperatureReadings->pluck('temp7');
//dd($carTracking);
        return view('admin.tasks.newshow', compact('task','bags','bag_count','sample_count','carTracking', 'labels', 'temp1', 'temp2', 'temp3'));
    }

    public function destroy(Task $task)
    {
        $this->authorize('can-delete');

        $task->delete();

        return back();
    }

    public function massDestroy(MassDestroyTaskRequest $request)
    {
        $this->authorize('can-delete');
        Task::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }


    public function dailyOperation(Request $request)
    {
        // return ;
        $logged_id_user = auth()->user();
        abort_if(Gate::denies('task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        if ($request->ajax()) {
            $query = Task::with(['from', 'to', 'client', 'driver', 'car'])->select(sprintf('%s.*', (new Task())->table));
            if( $logged_id_user->client_id != null)
            {
                $query = $query->where('billing_client', $logged_id_user->client_id);
            }
            $table = Datatables::of($query)
            ->filter(function ($query)  use ($request) {
                if ($request->status !=null) {
                    $query->where('status', '=',  $request->status );
                }
                if ($request->driver_id !=null) {
                    $query->where('driver_id', '=', $request->driver_id );
                }
                if ($request->billing_client !=null) {
                    $query->where('billing_client', '=', $request->billing_client );
                }
                if ($request->from_location !=null) {
                    $query->where('from_location', '=', $request->from_location );
                }
                if ($request->to_location !=null) {
                    $query->where('to_location', '=', $request->to_location );
                }
                if($request->date_from !=null && $request->date_to !=null)
                {
                    $query->whereBetween('tasks.created_at',
                        [
                            Carbon::createFromDate($request->date_from )->toDateString(),
                            Carbon::createFromDate($request->date_to)
                            //  ->addDays(1)
                             ->toDateString()
                        ]
                        );
                } else{
                    if ($request->date_from !=null) {
                        $query->where('tasks.created_at', '>=', $request->date_from );
                    }
                    if ($request->date_to !=null) {
                        $query->where('tasks.created_at', '>=', $request->date_to );
                    }
                }

            });
            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'task_show_no';
                $editGate = 'task_edit_no';
                $deleteGate = 'task_delete_no';
                $crudRoutePart = 'tasks';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->addColumn('from_location_name', function ($row) {
                return $row->from ? $row->from->name : '';
            });

            $table->addColumn('to_location_name', function ($row) {
                return $row->to ? $row->to->name : '';
            });

            $table->addColumn('billing_client', function ($row) {
                return $row->client ? $row->client->english_name : '';
            });

            $table->addColumn('driver_name', function ($row) {
                return $row->driver ? $row->driver->name : '';
            });

            $table->addColumn('close_date', function ($row) {
                return $row->close_date ? $row->close_date : '';
            });

            $table->addColumn('car_imei', function ($row) {
                return $row->car ? $row->car->imei : '';
            });

            $table->addColumn('start_time', function ($row) {
                return $row->car ? $row->car->imei : '';
            });
            $table->addColumn('end_time', function ($row) {
                return $row->car ? $row->car->imei : '';
            });
            $table->addColumn('hours', function ($row) {
                return $row->close_date ? CarbonPeriod::create($row->close_date, $row->from_location_arrival_time)->duration() : '';
            });

            $table->editColumn('status', function ($row) {
                $map = [
                    'NEW'         => ['bg-primary',   'NEW'],
                    'COLLECTED'   => ['bg-info',      'COLLECTED'],
                    'IN_FREEZER'  => ['bg-warning',   'IN_FREEZER'],
                    'OUT_FREEZER' => ['bg-warning',   'OUT_FREEZER'],
                    'CLOSED'      => ['bg-success',   'CLOSED'],
                    'NO_SAMPLES'  => ['bg-secondary', 'NO_SAMPLES'],
                ];
                if (isset($map[$row->status])) {
                    return '<span class="badge ' . $map[$row->status][0] . '">' . $map[$row->status][1] . '</span>';
                }
                return $row->status ?? '';
            });

            $table->rawColumns(['actions', 'placeholder', 'from_location', 'to_location', 'billing_client', 'driver', 'car', 'status']);

            return $table->make(true);
        } else{
            \Log::error("no ajax");
        }




        if( $logged_id_user->client_id != null)
        {
            // \Log::info("message");
                $clients = Client::where('id', $logged_id_user->client_id)->get();
                $locations = Location::select('locations.*')
                ->leftJoin('client_location','client_location.location_id','locations.id')
                ->where('client_location.client_id',$logged_id_user->client_id)
                ->get();
                $drivers = Driver::all();
        } else{
            $clients = Client::all();
            $locations = Location::all();
            $drivers = Driver::all();
        }



        return view('admin.tasks.index',[
            'clients' =>  $clients,
            'locations' =>  $locations,
            'drivers' =>  $drivers
        ]);
    }


    public function export(Request $request)
    {
        // SECURITY (fail-closed): same scoping rules as the listing — must be either
        // admin or client-bound, otherwise refuse the request.
        $loggedUser = auth()->user();
        $isAdminUser = $loggedUser && (
            $loggedUser->hasAnyRole(['Admin', 'Super Admin'])
            || method_exists($loggedUser, 'getIsAdminAttribute') && $loggedUser->is_admin
        );
        if ($loggedUser && $loggedUser->client_id) {
            $request->merge(['billing_client' => $loggedUser->client_id]);
        } elseif (!$isAdminUser) {
            abort(403, 'You are not allowed to export.');
        }

        // PERFORMANCE: force a date range to avoid scanning the entire tasks table on prod.
        // Defaults to last 30 days when none is provided.
        if (empty($request->date_from) && empty($request->date_to)) {
            $request->merge([
                'date_from' => Carbon::now()->subDays(30)->startOfDay()->toDateTimeString(),
                'date_to'   => Carbon::now()->endOfDay()->toDateTimeString(),
            ]);
        }

        if($request->report_type == 'excel')
        {
            return $this->exportExcel($request);
        }
        set_time_limit(1000);
        ini_set('memory_limit', '9000M');
        // $data = [
        //     'title' => 'My Report',
        //     'date' => date('Y-m-d'),
        //     'items' => [
        //         ['id' => 1, 'name' => 'Item 1', 'quantity' => 10],
        //         ['id' => 2, 'name' => 'Item 2', 'quantity' => 5],
        //         ['id' => 3, 'name' => 'Item 3', 'quantity' => 20],
        //     ],
        // ];

        // return Excel::download(new MyReportExport($data), 'report.xlsx');





        $billing_client = $request->billing_client;
        if($billing_client != null){
            $clint = Client::find($billing_client);
            $client_logo = $clint->logo;
            // dd($client_logo );
        }else{
            $client_logo = null;
        }
        $sample_barcode = '';//$sample_barcode;
        $keyWord = '';//'%'.$keyWord .'%';
        // $to = date('Y-m-d', strtotime($request->date_to)). ' 11:59:59';
        // $from = date('Y-m-d', strtotime($request->date_from)). ' 00:00:00';

        $to = $request->date_to;
        $from = $request->date_from;

        $date_column = $request->search_date ?? 'tasks.created_at';



        $from_location = $request->from_location;
        $to_location = $request->to_location;
        // $task_type = '"SAMPLE"';
        $driver_id = $request->driver_id;
        // \Log::info( $driver_id);
        $status = $request->status;
        // $status = '';
        if($from == $to)
        {
            $reportDate =  $to;
        } else{
            $reportDate = 'From '. $from. '- To '. $to;
        }



        $query = 'select  tasks.id as id  ,  from_location.name as "from_organization_name" , tasks.close_date "close_task",  tasks.from_location_arrival_time as "from_location_arrival_time",
                                    TIMESTAMPDIFF(Minute, tasks.from_location_arrival_time,  tasks.collection_date) as "from_stay_time",
                                    to_location.name as "to_organization_name",
                                    tasks.to_location_arrival_time as "to_location_arrival_time",
                                    /*TIMESTAMPDIFF(Minute, tasks.freezer_out_date, tasks.close_date ) as "to_stay_time",*/
                                      CASE
                                        WHEN tasks.is_swap = 1 THEN TIMESTAMPDIFF(Minute, tasks.swap_freezer_out, tasks.close_date)
                                        ELSE TIMESTAMPDIFF(Minute, tasks.freezer_out_date, tasks.close_date)
                                    END AS "to_stay_time",
                                    TIMESTAMPDIFF(Minute,  tasks.from_location_arrival_time, tasks.close_date) as "trip_duration",
                                    GROUP_CONCAT(samples.bag_code) as "bag_code",
                                    GROUP_CONCAT(samples.temperature_type) as "temperature_type",
                                    count(samples.id) as "bags_count",
                                    tasks.confirmed_by_client,
                                    tasks.confirmation_time
                                    from tasks
                                    left join drivers on drivers.ID = tasks.driver_id
                                    left join locations as from_location on from_location.ID = tasks.from_location
                                    left join locations as to_location on to_location.ID = tasks.to_location
                                    left join samples as samples on samples.task_id = tasks.id
                                    WHERE tasks.deleted_at is null and tasks.id > 1 and drivers.status = 1';


        $billing_client=$request->billing_client;
        if($billing_client !=null)
        {
            $billing_client=$billing_client;
            $query =  $query.' and tasks.billing_client= '.$billing_client;
        }

        if($from_location !=null)
        {
            $query =  $query.' and tasks.from_location= '.$from_location;
        }

        if($to_location !=null)
        {
            $query =  $query.' and tasks.to_location= '.$to_location;
        }
        if($driver_id !=null)
        {
            $query =  $query.' and tasks.driver_id= '.$driver_id;
        }


        // if($task_type !=null)
        // {
        //     $query =  $query.' and tasks.task_type= '.$task_type;
        // }


        // if($from !=null && $to !=null )
        // {
        //     $query =  $query." and tasks.created_at BETWEEN '".date('Y-m-d H:i:s', strtotime( $from))."' and '".date('Y-m-d H:i:s', strtotime( $to))." '";
        // }

        if($from !=null && $to !=null )
        {
            $query =  $query." and ".$date_column." BETWEEN '".date('Y-m-d H:i:s', strtotime( $from))."' and '".date('Y-m-d H:i:s', strtotime( $to))." '";
        }

        if($status !=null)
        {
            $query =  $query." and tasks.status= '".$status."'";
        }

        // \Log::info($query);

        $tasks = DB::select($query.' group by tasks.id order by from_location.name asc, tasks.from_location_arrival_time asc;');
        $roomBags = 0;
        $refBags = 0;
        $frozenBags = 0;

        $roomSamples = 0;
        $refSamples = 0;
        $frozenSamples = 0;


        $summaryReport = '';
        if( $billing_client== 26) // mdlab
        {
            // this is mdlab report request by mtc
            $summaryReport = collect($tasks)
                ->groupBy('from_organization_name')
                ->map(function ($task) {
                    return [
                        'trip_duration' => $task->sum('trip_duration'),
                        'count' => $task->count(),
                    ];
                });
        }


        foreach ($tasks as $task)
        {
            $task->from_stay_time = floor($task->from_stay_time / 60).'H:'.($task->from_stay_time -   floor($task->from_stay_time / 60) * 60).'M';
            $task->to_stay_time = floor($task->to_stay_time / 60).'H:'.($task->to_stay_time -   floor($task->to_stay_time / 60) * 60).'M';
            $task->trip_duration = floor($task->trip_duration / 60).'H:'.($task->trip_duration -   floor($task->trip_duration / 60) * 60).'M';
            if($task->bag_code == null)
            {
                $task->bags = array();
            }else{
                $task->bags2 = array_count_values (explode(',', $task->bag_code));
                $task->bags = array_unique (explode(',', $task->bag_code));
                $task->temperature_types2 = array_count_values (explode(',', $task->temperature_type));
                $task->temperature_types = array_unique (explode(',', $task->temperature_type));
                $tempVar = json_decode(json_encode($task->temperature_types2),true);
                $bagVar = json_decode(json_encode($task->bags2),true);
                $task->data = array();
                foreach ($tempVar as $key => $value){

                    $temp = new Task();
                    $temp->temperature  =$key;
                    $temp->count  =$value;
                    foreach ($bagVar as $key1 => $value1) {
                        if($value == $value1)
                        {
                            $temp->bag  =$key1;
                            break;
                        }
                    }
                    $task->data[]=$temp;
                    switch ($key)
                    {
                        case 'ROOM':
                            $temp->temperature_label = '+15C TO +25C';
                            $roomBags += 1;
                            $roomSamples += $value;
                            break;
                        case 'REFRIGERATE':
                            $temp->temperature_label = '+2C TO +8C';
                            $refSamples += $value;
                            $refBags += 1;
                            break;
                        case 'FROZEN':
                            $temp->temperature_label = '0C TO -18C';
                            $frozenSamples += $value;
                            $frozenBags += 1;
                            break;
                    }
                }
            }

        }





        $pickup_smaple = $roomSamples+$refSamples+$frozenSamples;
        $pickup_container = $frozenBags+$refBags+$roomBags;

        $condition = Task::where(function ($query) use ($keyWord,$sample_barcode) {
            $query->orWhere('from_location', 'LIKE', $keyWord)
                ->orWhereHas('driver', function ($query) use ($keyWord) {
                    $query->where('name', 'LIKE', $keyWord);
                })
                ->orWhereHas('from', function ($query) use ($keyWord) {
                    $query->where('name', 'LIKE', $keyWord);
                })
                ->orWhereHas('to', function ($query) use ($keyWord) {
                    $query->where('name', 'LIKE', $keyWord);
                })
                ->orWhereHas('client', function ($query) use ($keyWord) {
                    $query->where('english_name', 'LIKE', $keyWord);
                })
                ->orWhere('type', 'LIKE', $keyWord);

        })

            ->when($sample_barcode, function  ($query) use ($sample_barcode) {
                $query->whereHas('samples', function ($query) use ($sample_barcode){
                    $query->where('barcode_id', 'LIKE',  $sample_barcode);
                });
            })
            ->when($status, function  ($query)  use ($status){
                $query->where('tasks.status', $status);
            })

            // ->when($task_type, function  ($query) use($task_type) {
            //     $query->where('task_type', $task_type);
            // })
            ->when($from_location, function  ($query) use ($from_location) {
                $query->where('from_location', $from_location);
            })
            ->when($to_location, function  ($query)  use ($to_location){
                $query->where('to_location', $to_location);
            })
            ->when($billing_client, function  ($query) use ($billing_client) {
                $query->where('billing_client', $billing_client);
            })
            ->when($driver_id, function  ($query) use ($driver_id) {
                $query->where('driver_id', $driver_id);
            })
            // if($from !=null && $to !=null )
            // {
            //     $query =  $query." and ".$date_column." BETWEEN '".date('Y-m-d H:i:s', strtotime( $from))."' and '".date('Y-m-d H:i:s', strtotime( $to))." '";
            // }
            ->whereBetween($date_column, [date('Y-m-d H:i:s', strtotime( $from)), date('Y-m-d H:i:s', strtotime( $to))]);
            // ->whereDate($date_column, date('Y-m-d'))
            // ->whereDate('tasks.created_at', date('Y-m-d'))
        ;
        $served_orginization = $condition->whereIn('tasks.status',['CLOSED','NO_SAMPLES'])
            ->distinct('from_location')->count('from_location');

        $visited_orginization = $condition->where('status','NO_SAMPLES')->count();



        $pick_sum_data = array(
            $pickup_container,$pickup_smaple
        );

        //summary data
        $summary = Task::with(['client' => function ($query) {
            $query->select('id', 'english_name');
        }])
            ->when($sample_barcode, function  ($query) use ($sample_barcode) {
                $query->whereHas('samples', function ($query) use ($sample_barcode){
                    $query->where('barcode_id', 'LIKE',  $sample_barcode);
                });
            })
            ->when($status, function  ($query) use ($status) {
                $query->where('status', $status);
            })
            // ->when($task_type, function  ($query)  use ($task_type){
            //     $query->where('task_type', $task_type);
            // })
            ->when($from_location, function  ($query) use ($from_location) {
                $query->where('from_location', $from_location);
            })
            ->when($to_location, function  ($query)  use ($to_location){
                $query->where('to_location', $to_location);
            })
            ->when($billing_client, function  ($query) use ($billing_client) {
                $query->where('billing_client', $billing_client);
            })
            ->when($driver_id, function  ($query) use ($driver_id) {
                $query->where('driver_id', $driver_id);
            })
            ->whereDate('created_at', date('Y-m-d'))

            ->select('status','billing_client',DB::raw('count(*) as total'))->groupBy('status')->get();


            // Generate HTML for PDF report using template file
            $html = View::make('report_template', [
                'tasks' => $tasks,
                'summary' => $summary,
                'pick_sum_data' => $pick_sum_data,
                'client_logo' => $client_logo,
                'billing_client' => $billing_client,
                'reportDate' => $reportDate,
                'summaryReport' => $summaryReport,
                'frozenSamples' => $frozenSamples,
                'visited_orginization'=> $visited_orginization,
                'served_orginization'=> $served_orginization,
                'frozenBags'=> $frozenBags,
                'roomBags'=> $roomBags,
                'refBags'=> $refBags,
                'roomSamples'=> $roomSamples,
                'refSamples' => $refSamples
                ])->render();

            // $css = file_get_contents(public_path('assets/css/export.css'));
            // $image = base64_encode(file_get_contents(public_path('/img/mtc_logo.jpg')));

            // $options->set('isRemoteEnabled', true);

            // Generate PDF using Dompdf
            // $dompdf = new Dompdf();

            $options = new Options();
            $options->setIsRemoteEnabled(true);
            $dompdf = new Dompdf($options);

            $dompdf->loadHtml($html);

            // $dompdf->add_css($css);

            $dompdf->setPaper('A3', 'landscape');
            $dompdf->render();

            // Download PDF file

            // return PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])->loadView('users_report.pdf')->stream();
            return $dompdf->stream('users_report.pdf');



    }


    public function exportExcel(Request $request)
    {
        // SECURITY: scope to logged-in user's client_id so a client user cannot
        // pull data for other clients.
        $loggedUser = auth()->user();
        if ($loggedUser && $loggedUser->client_id) {
            $request->merge(['billing_client' => $loggedUser->client_id]);
        }

        // Allow long-running export
        @set_time_limit(600);
        @ini_set('memory_limit', '1024M');

        $from = $request->date_from;
        $to = $request->date_to;


        $sample_barcode = $request->sample_barcode;
		$keyWord = '%'.$request->keyWord .'%';

        if($request->billing_client){
            $clint = Client::find($request->billing_client);
            $client_logo = $clint->logo;
            // dd($client_logo );
        }else{
            $client_logo = null;
        }
        $sample_barcode = $request->sample_barcode;
        $keyWord = '%'.$request->keyWord .'%';
        // $to =  $request->date_to;
        // $from =  $request->date_from.' 00:00:00';

        $to = date('Y-m-d', strtotime($request->date_to)). ' 11:59:59';

        // $from =  $request->date_from;
        $from = date('Y-m-d', strtotime($request->date_from)). ' 00:00:00';


        if($request->from != null)
        {
            $request->selected_date = $request->date_from;
        } else{
            $request->selected_date = Carbon::now();
        }

        $query = 'select  tasks.id as id  ,  from_location.name as "from_organization_name",  tasks.from_location_arrival_time as "from_location_arrival_time",freezer_date,
                                    close_date,TIMESTAMPDIFF(Minute, tasks.from_location_arrival_time,  tasks.collection_date) as "from_stay_time",
                                    to_location.name as "to_organization_name",
                                    drivers.name as "driverName",
                                    tasks.created_at,
                                    clients.english_name as "clientName",
                                    tasks.to_location_arrival_time as "to_location_arrival_time",
                                    /*TIMESTAMPDIFF(Minute, tasks.to_location_arrival_time, tasks.close_date ) as "to_stay_time",*/
                                    CASE
                                        WHEN tasks.is_swap = 1 THEN TIMESTAMPDIFF(Minute, tasks.swap_freezer_out, tasks.close_date)
                                        ELSE TIMESTAMPDIFF(Minute, tasks.freezer_out_date, tasks.close_date)
                                    END AS "to_stay_time",
                                    TIMESTAMPDIFF(Minute,  tasks.from_location_arrival_time, tasks.close_date) as "trip_duration",
                                    GROUP_CONCAT(samples.bag_code) as "bag_code",
                                    GROUP_CONCAT(samples.barcode_id) as "sample_code",
                                    GROUP_CONCAT(samples.sample_type) as "sample_types",
                                    GROUP_CONCAT(samples.temperature_type) as "temperature_type",
                                    count(samples.id) as "bags_count"
                                    from tasks
                                    left join drivers on drivers.ID = tasks.driver_id
                                    left join clients on clients.ID = tasks.billing_client
                                    left join locations as from_location on from_location.ID = tasks.from_location
                                    left join locations as to_location on to_location.ID = tasks.to_location
                                    left join samples as samples on samples.task_id = tasks.id
                                    WHERE  tasks.id > 1 and drivers.status = 1';

        if($request->billing_client !=null)
        {
            $query =  $query.' and tasks.billing_client= '.$request->billing_client;
        }

        if($request->date_from !=null && $request->date_to !=null )
        {
            $query =  $query." and tasks.created_at BETWEEN '".date('Y-m-d H:i:s', strtotime( $request->date_from))."' and '".date('Y-m-d H:i:s', strtotime( $request->date_to))." '";
        }

        if($request->to_location !=null)
        {
            $query =  $query.' and tasks.to_location= '.$request->to_location;
        }
        if($request->from_location !=null)
        {
            $query =  $query.' and tasks.from_location= '.$request->from_location;
        }
        if($request->driver_id !=null)
        {
            $query =  $query.' and tasks.driver_id= '.$request->driver_id;
        }
        if($request->status !=null)
        {
            $query =  $query." and tasks.status= '".$request->status."'";
        }

        $tasks = DB::select($query.' group by tasks.id  order by from_location.name;');
        $roomBags = 0;
        $refBags = 0;
        $frozenBags = 0;

        $roomSamples = 0;
        $refSamples = 0;
        $frozenSamples = 0;
        foreach ($tasks as $task)
        {
            $task->box_count = 0;
            $task->from_stay_time = floor($task->from_stay_time / 60).' H : '.($task->from_stay_time -   floor($task->from_stay_time / 60) * 60).' M';
            $task->to_stay_time = floor($task->to_stay_time / 60).' H : '.($task->to_stay_time -   floor($task->to_stay_time / 60) * 60).' M';
            $task->trip_duration = floor($task->trip_duration / 60).'H:'.($task->trip_duration -   floor($task->trip_duration / 60) * 60).'M';
            if($task->bag_code == null)
            {
                $task->temperature_types2 = array();
                $task->data = array();
                $task->sample_codes = array();
                $task->bags = array();
            }else{
                $task->sample_codes = explode(',', str_replace(' ', '', $task->sample_code) );
                $task->bags2 = array_count_values (explode(',', $task->bag_code));
                $task->bags = array_unique (explode(',', $task->bag_code));
                $task->temperature_types2 = array_count_values (explode(',', $task->temperature_type));
                $task->temperature_types = array_unique (explode(',', $task->temperature_type));
                $tempVar = json_decode(json_encode($task->temperature_types2),true);
                $bagVar = json_decode(json_encode($task->bags2),true);
                $task->data = array();
                foreach ($tempVar as $key => $value){

                    $temp = new Task();
                    $temp->temperature  =$key;
                    $temp->count  =$value;
                    foreach ($bagVar as $key1 => $value1) {
                        if($value == $value1)
                        {
                            $temp->bag  =$key1;
                            break;
                        }
                    }
                    switch ($key)
                    {
                        case 'ROOM':
                            $temp->temperature_label = '+15C TO +25C';
                            $roomBags += 1;
                            $roomSamples += $value;
                            break;
                        case 'REFRIGERATE':
                            $temp->temperature_label = '+2C TO +8C';
                            $refSamples += $value;
                            $refBags += 1;
                            break;
                        case 'FROZEN':
                            $temp->temperature_label = '0C TO -18C';
                            $frozenSamples += $value;
                            $frozenBags += 1;
                            break;
                    }
                    $task->data[]=$temp;
                }
            }

        }

         $temperatures = Sample::select('temperature_type',DB::raw('count(DISTINCT bag_code) as bcode'),DB::raw('count(*) as total'))
         ->when($request->date_from !='' || $request->date_to !='', function  ($query) use ($from, $to,$request) {
            $query->whereBetween('created_at',
                    [
                        Carbon::createFromDate($request->date_from)->toDateString(),
                        Carbon::createFromDate($request->date_to)->toDateString()
                    ]
            );

        })
         ->groupBy('temperature_type')->get();


        return Excel::download(new TasksExport($tasks,$temperatures), 'report.xlsx');
    }


}
