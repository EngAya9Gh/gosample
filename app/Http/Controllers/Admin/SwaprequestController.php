<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroySwaprequestRequest;
use App\Http\Requests\StoreSwaprequestRequest;
use App\Http\Requests\UpdateSwaprequestRequest;
use App\Models\Driver;
use App\Models\Swap;
use App\Models\Task;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
class SwaprequestController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('swaprequest_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = Swap::with(['task', 'driver', 'driverA']);

        // Filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }
        if ($request->filled('task_id')) {
            $query->where('task_id', $request->task_id);
        }
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->whereHas('driver', function ($q2) use ($keyword) {
                    $q2->where('name', 'like', "%{$keyword}%");
                })->orWhereHas('driverA', function ($q2) use ($keyword) {
                    $q2->where('name', 'like', "%{$keyword}%");
                })->orWhere('task_id', 'like', "%{$keyword}%");
            });
        }

        // Pagination
        $pageSize = $request->get('pageSize', 25);
        $paginator = $query->orderBy('id', 'desc')->paginate($pageSize);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'rows' => $paginator->items(),
                'total' => $paginator->total(),
            ]);
        }

        $drivers = Driver::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');
        $tasks = Task::whereNotIn('status', ['NO_SAMPLES', 'CLOSED'])->pluck('id', 'id')->prepend(trans('translation.pleaseSelect'), '');

        return \Inertia\Inertia::render('SwapRequests/SwapRequestsList', [
            'initialRows' => $paginator->items(),
            'initialTotal' => $paginator->total(),
            'drivers' => $drivers,
            'tasks' => $tasks
        ]);
    }

    public function create(Request $request)
    {
        abort_if(Gate::denies('swaprequest_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');
        $tasks = Task::whereNotIn('status', ['NO_SAMPLES', 'CLOSED'])->pluck('id', 'id')->prepend(trans('translation.pleaseSelect'), '');

        return \Inertia\Inertia::render('SwapRequests/SwapRequestForm', [
            'drivers' => $drivers,
            'tasks' => $tasks
        ]);
    }

    
    public function getTasksForDriver(Request $request)
    {
        $tasks = \App\Models\Task::with('from')
            ->where('tasks.driver_id', $request->driver_id)
            ->where('tasks.status', '<>', 'NO_SAMPLES')
            ->where('tasks.status', '<>', 'CLOSED')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $tasks
        ]);
    }

    public function store(StoreSwaprequestRequest $request)
    {
        if (!$request->filled('status')) {
            $request->merge(['status' => 'new']);
        }
        if ($request->driver_id == $request->driver_a) {
            return back()->withErrors(['driver_a' => 'Please select a different driver to swap requests']);
        }

        if (!is_array($request->task_id)) {
        $request->task_id = [$request->task_id];
        }
    
        $taskIds = $request->input('task_id');

        foreach ($taskIds as $taskId) {
        $swapRequest = new Swap();
        $swapRequest->task_id = $taskId;
        $swapRequest->status = $request->status;
        $swapRequest->driver_a = $request->driver_a;
        $swapRequest->driver_id = $request->driver_id;
        $swapRequest->save();
        }
    
        return redirect()->route('admin.swaprequests.index');
    }
    
    public function edit(Swap $swaprequest, Request $request)
    {
        abort_if(Gate::denies('swaprequest_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tasks = Task::whereNotIn('status', ['NO_SAMPLES', 'CLOSED'])->pluck('id', 'id')->prepend(trans('translation.pleaseSelect'), '');
        $drivers = Driver::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

        $swaprequest->load('task', 'driver');

        return \Inertia\Inertia::render('SwapRequests/SwapRequestForm', [
            'swaprequest' => $swaprequest,
            'drivers' => $drivers,
            'tasks' => $tasks
        ]);
    }

    public function update(UpdateSwaprequestRequest $request, Swap $swaprequest)
    {
        $swaprequest->update($request->all());
            return redirect()->route('app.admin.swaprequests.index');
        return redirect()->route('admin.swaprequests.index');
    }

    public function show(Swap $swaprequest)
    {
        abort_if(Gate::denies('swaprequest_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $swaprequest->load('task', 'driver');

        return view('admin.swaprequests.show', compact('swaprequest'));
    }

    public function destroy(Swap $swaprequest, Request $request)
    {
        $this->authorize('swaprequest_delete');
        $swaprequest->delete();
            return back();
        return back();
    }

    public function massDestroy(MassDestroySwaprequestRequest $request)
    {
        $this->authorize('swaprequest_delete');
        $swaprequests = Swap::find(request('ids'));

        foreach ($swaprequests as $swaprequest) {
            $swaprequest->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
