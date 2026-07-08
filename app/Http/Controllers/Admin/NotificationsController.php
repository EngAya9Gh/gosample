<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notifications;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('notification_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (str_starts_with($request->path(), 'app/')) {
            $query = Notifications::with(['task', 'fromLocation', 'toLocation', 'driver', 'billingClient'])
                ->select('notifications.*');
            
            // Filters
            $query->when($request->keyword, function($q, $v) {
                $q->where(function($q) use ($v) {
                    $q->where('id', $v)
                      ->orWhereHas('task', fn($q) => $q->where('id', $v))
                      ->orWhereHas('fromLocation', fn($q) => $q->where('name', 'like', "%$v%"))
                      ->orWhereHas('toLocation', fn($q) => $q->where('name', 'like', "%$v%"))
                      ->orWhereHas('driver', fn($q) => $q->where('name', 'like', "%$v%"))
                      ->orWhereHas('billingClient', fn($q) => $q->where('english_name', 'like', "%$v%"));
                });
            });

            // Sorting
            $sortColumn = $request->input('sort_by', 'id');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortColumn, $sortOrder);

            $pageSize = max(1, min((int) $request->input('pageSize', 25), 100));
            $paginator = $query->paginate($pageSize);

            $rows = $paginator->map(function ($row) {
                return [
                    'id' => $row->id,
                    'task_id' => $row->task_id,
                    'from_location_name' => optional($row->fromLocation)->name,
                    'to_location_name' => optional($row->toLocation)->name,
                    'driver_name' => optional($row->driver)->name,
                    'billing_client_name' => optional($row->billingClient)->english_name,
                    'type' => $row->type,
                    'notifiable_type' => $row->notifiable_type,
                    'data' => $row->data,
                    'read_at' => $row->read_at ? \Carbon\Carbon::parse($row->read_at)->format('Y-m-d H:i') : null,
                    'created_at' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i') : null,
                ];
            });

            return inertia('Notifications/NotificationsList', [
                'rows' => $rows,
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'pageSize' => $paginator->perPage(),
                'filters' => $request->only(['keyword', 'sort_by', 'sort_order']),
            ]);
        }

        if ($request->ajax()) {
            $query = Notifications::with(['task', 'fromLocation', 'toLocation', 'driver', 'billingClient'])->select(sprintf('notifications.*', (new Notifications())->table));
            
            $totalCount = \Illuminate\Support\Facades\Cache::remember('notifications_total_count', now()->addMinutes(10), function () {
                $row = \Illuminate\Support\Facades\DB::selectOne(
                    "SELECT TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications'"
                );
                return $row ? (int) $row->TABLE_ROWS : \App\Models\Notifications::count();
            });

            $table = Datatables::of($query)
                ->setTotalRecords($totalCount)
                ->setFilteredRecords($totalCount);

            $searchValue = $request->input('search.value', '');
            if (!empty($searchValue)) {
                $table->setFilteredRecords(null);
            }

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'notification_show';
                $editGate = 'notification_edit';
                $deleteGate = 'notification_delete';
                $crudRoutePart = 'notifications';

                return view('partials.datatablesActions', compact('viewGate', 'editGate', 'deleteGate', 'crudRoutePart', 'row'));
            });

            $table->editColumn('id', function ($row) { return $row->id ? $row->id : ''; });
            $table->addColumn('task_id', function ($row) { return $row->task ? $row->task->id : ''; });
            $table->addColumn('from_location_name', function ($row) { return $row->fromLocation ? $row->fromLocation->name : ''; });
            $table->addColumn('to_location_name', function ($row) { return $row->toLocation ? $row->toLocation->name : ''; });
            $table->addColumn('driver_name', function ($row) { return $row->driver ? $row->driver->name : ''; });
            $table->addColumn('billing_client_english_name', function ($row) { return $row->billingClient ? $row->billingClient->english_name : ''; });
            $table->editColumn('type', function ($row) { return $row->type ? $row->type : ''; });
            $table->editColumn('notifiable_type', function ($row) { return $row->notifiable_type ? $row->notifiable_type : ''; });
            $table->editColumn('notifiable', function ($row) { return $row->notifiable ? $row->notifiable : ''; });
            $table->editColumn('data', function ($row) { return $row->data ? $row->data : ''; });

            $table->rawColumns(['actions', 'placeholder', 'task', 'from_location', 'to_location', 'driver', 'billing_client']);

            return $table->make(true);
        }

        return view('admin.notifications.index');
    }

    public function show(Notifications $notification)
    {
        abort_if(Gate::denies('notification_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $notification->load('task', 'fromLocation', 'toLocation', 'driver', 'billingClient');

        return view('admin.notifications.show', compact('notification'));
    }
}
