<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyAttendanceRequest;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\Driver;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttendancesController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('attendance_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = Attendance::with(['driver'])->select(sprintf('%s.*', (new Attendance)->getTable()));
            $table = \Yajra\DataTables\Facades\DataTables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $editUrl = route('admin.attendances.edit', $row->id);
                $deleteUrl = route('admin.attendances.destroy', $row->id);
                $csrf = csrf_token();
                
                $buttons = "";
                
                if (Gate::allows('attendance_edit')) {
                    $buttons .= '<a class="btn btn-xs btn-info shadow-sm me-1 text-white" href="' . $editUrl . '"><i class="ri-edit-line"></i></a>';
                }
                if (Gate::allows('can-delete')) {
                    $buttons .= '<form action="' . $deleteUrl . '" method="POST" onsubmit="return confirm(\'' . trans('translation.areYouSure') . '\');" style="display: inline-block;">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <input type="hidden" name="_token" value="' . $csrf . '">
                                    <button type="submit" class="btn btn-xs btn-danger shadow-sm"><i class="ri-delete-bin-line"></i></button>
                                </form>';
                }
                
                return '<div class="text-nowrap">' . $buttons . '</div>';
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });

            $table->addColumn('driver_name', function ($row) {
                return $row->driver ? $row->driver->name : '';
            });

            $table->addColumn('driver_mobile', function ($row) {
                return $row->driver ? $row->driver->mobile : '';
            });

            $table->editColumn('checkin_time', function ($row) {
                return $row->checkin_time ? $row->checkin_time : '';
            });

            $table->editColumn('checkout_time', function ($row) {
                return $row->checkout_time ? $row->checkout_time : '';
            });

            $table->editColumn('is_late', function ($row) {
                if ($row->is_late) {
                    return '<span class="badge badge-danger" style="background-color: #f06548; color: white;">Late</span>';
                }
                return '<span class="badge badge-success" style="background-color: #0ab39c; color: white;">On Time</span>';
            });

            $table->editColumn('delay_minutes', function ($row) {
                return $row->delay_minutes ?? 0;
            });

            $table->editColumn('overtime_minutes', function ($row) {
                if (($row->overtime_minutes ?? 0) > 0) {
                    return '<span class="text-success">+' . $row->overtime_minutes . '</span>';
                } elseif (($row->early_leave_minutes ?? 0) > 0) {
                    return '<span class="text-danger">-' . $row->early_leave_minutes . '</span>';
                }
                return 0;
            });

            $table->editColumn('source', function ($row) {
                return ucfirst($row->source ?? 'manual');
            });

            $table->rawColumns(['actions', 'placeholder', 'is_late', 'overtime_minutes']);

            return $table->make(true);
        }

        return view('admin.attendances.index');
    }

    public function create()
    {
        abort_if(Gate::denies('attendance_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

        return view('admin.attendances.create', compact('drivers'));
    }

    public function store(StoreAttendanceRequest $request)
    {
        $data = $request->all();
        $today = now()->toDateString();
        
        if (!empty($data['checkin_time'])) {
            $data['checkin_time'] = $today . ' ' . $data['checkin_time'];
        }
        
        if (!empty($data['checkout_time'])) {
            $data['checkout_time'] = $today . ' ' . $data['checkout_time'];
        }

        $attendance = Attendance::create($data);

        // Dispatch background calculation
        \App\Jobs\ProcessAttendanceKPIJob::dispatch($attendance);

        return redirect()->route('admin.attendances.index');
    }

    public function edit(Attendance $attendance)
    {
        abort_if(Gate::denies('attendance_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $drivers = Driver::pluck('name', 'id')->prepend(trans('translation.pleaseSelect'), '');

        $attendance->load('driver');

        return view('admin.attendances.edit', compact('attendance', 'drivers'));
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        $data = $request->all();
        $date = $attendance->created_at ? $attendance->created_at->toDateString() : now()->toDateString();

        if (!empty($data['checkin_time']) && strlen($data['checkin_time']) <= 8) {
            $data['checkin_time'] = $date . ' ' . $data['checkin_time'];
        }
        
        if (!empty($data['checkout_time']) && strlen($data['checkout_time']) <= 8) {
            $data['checkout_time'] = $date . ' ' . $data['checkout_time'];
        }

        $attendance->update($data);

        // Dispatch background calculation
        \App\Jobs\ProcessAttendanceKPIJob::dispatch($attendance);

        return redirect()->route('admin.attendances.index');
    }

    public function show(Attendance $attendance)
    {
        abort_if(Gate::denies('attendance_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $attendance->load('driver');

        return view('admin.attendances.show', compact('attendance'));
    }

    public function destroy(Attendance $attendance)
    {
        $this->authorize('can-delete');

        $attendance->delete();

        return back();
    }

    public function massDestroy(MassDestroyAttendanceRequest $request)
    {
        $this->authorize('can-delete');
        Attendance::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
