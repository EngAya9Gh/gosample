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

    public function show(Notifications $notification)
    {
        abort_if(Gate::denies('notification_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $notification->load('task', 'fromLocation', 'toLocation', 'driver', 'billingClient');

        return view('admin.notifications.show', compact('notification'));
    }
}
