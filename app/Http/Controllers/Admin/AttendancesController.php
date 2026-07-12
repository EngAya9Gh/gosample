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

        // SPA (/app) list + its JSON reloads — split by PATH, not headers: the
        // classic page's DataTables AJAX also sends X-Requested-With, so only
        // /app/admin/attendances requests land here. Classic branch untouched.
        if (str_starts_with($request->path(), 'app/')) {
            $query = Attendance::with(['driver']);

            if ($request->filled('keyword')) {
                $kw = $request->keyword;
                $query->where(function ($q) use ($kw) {
                    $q->where('id', 'LIKE', "%{$kw}%")
                      ->orWhereHas('driver', function ($q2) use ($kw) {
                          $q2->where('name', 'LIKE', "%{$kw}%")->orWhere('mobile', 'LIKE', "%{$kw}%");
                      });
                });
            }
            if ($request->filled('driver_id')) {
                $query->where('driver_id', $request->driver_id);
            }
            if ($request->filled('is_late')) {
                $query->where('is_late', (bool) $request->is_late);
            }
            if ($request->filled('source')) {
                $query->where('source', $request->source);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $sortBy = in_array($request->input('sort_by'), ['id', 'checkin_time', 'checkout_time', 'created_at']) ? $request->sort_by : 'id';
            $sortOrder = $request->input('sort_order') === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortBy, $sortOrder);

            $pageSize = max(1, min((int) $request->input('pageSize', 25), 100));
            $page = max(1, (int) $request->input('page', 1));
            $total = (clone $query)->count();
            $offset = ($page - 1) * $pageSize;

            $seq = $offset;
            $rows = $query->offset($offset)->limit($pageSize)->get()->map(function ($a) use (&$seq) {
                $seq++;
                $hm = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('H:i') : null;
                return [
                    'sequence'            => $seq,
                    'id'                  => $a->id,
                    'driver_id'           => $a->driver_id,
                    'driver_name'         => $a->driver ? $a->driver->name : null,
                    'driver_mobile'       => $a->driver ? $a->driver->mobile : null,
                    'checkin_time'        => $a->checkin_time,
                    'checkout_time'       => $a->checkout_time,
                    // H:i values for the edit modal's time pickers (same format
                    // the classic edit form prefills).
                    'checkin_hm'          => $hm($a->checkin_time),
                    'checkout_hm'         => $hm($a->checkout_time),
                    'is_late'             => (bool) $a->is_late,
                    'delay_minutes'       => $a->delay_minutes ?? 0,
                    'overtime_minutes'    => $a->overtime_minutes ?? 0,
                    'early_leave_minutes' => $a->early_leave_minutes ?? 0,
                    'source'              => ucfirst($a->source ?? 'manual'),
                    'created_at'          => $a->created_at ? $a->created_at->format('Y-m-d H:i') : null,
                ];
            });

            if ($request->wantsJson()) {
                return response()->json(['rows' => $rows, 'total' => $total]);
            }

            return \Inertia\Inertia::render('Attendances/AttendancesList', [
                'initialRows'  => $rows,
                'initialTotal' => $total,
                'filters'      => [
                    'drivers' => Driver::select('id', 'name')->get()
                        ->map(fn ($d) => ['value' => $d->id, 'label' => $d->name])->values(),
                ],
            ]);
        }

        if ($request->ajax()) {
            $query = Attendance::with(['driver'])->select(sprintf('%s.*', (new Attendance)->getTable()));
            $table = \Yajra\DataTables\Facades\DataTables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $editUrl = route('admin.attendances.edit', $row->id);
                $deleteUrl = route('admin.attendances.destroy', $row->id);
                $csrf = csrf_token();
                $editTitle = trans('translation.edit');
                $deleteTitle = trans('translation.delete');

                $buttons = "";

                if (Gate::allows('attendance_edit')) {
                    $buttons .= '<a class="btn btn-soft-primary btn-sm" href="' . $editUrl . '" title="' . $editTitle . '"><i class="ri-edit-2-fill"></i></a>';
                }
                if (Gate::allows('can-delete')) {
                    $buttons .= '<form action="' . $deleteUrl . '" method="POST" onsubmit="return confirm(\'' . trans('translation.areYouSure') . '\');" style="display: inline-block;">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <input type="hidden" name="_token" value="' . $csrf . '">
                                    <button type="submit" class="btn btn-soft-danger btn-sm" title="' . $deleteTitle . '"><i class="ri-delete-bin-fill"></i></button>
                                </form>';
                }

                return '<div class="d-flex gap-1 justify-content-center">' . $buttons . '</div>';
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

    /**
     * Create an attendance from the SPA modal. Mirrors store() 1:1 — times
     * get today's date prefixed, source defaults to 'manual', and the KPI
     * recalculation job is dispatched.
     */
    public function storePopup(Request $request)
    {
        abort_if(Gate::denies('attendance_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'driver_id'        => ['required', 'integer'],
            'shift_id'         => ['nullable', 'integer'],
            'checkin_time'     => ['nullable', 'string'],
            'checkout_time'    => ['nullable', 'string'],
            'delay_minutes'    => ['nullable', 'integer', 'min:0'],
            'overtime_minutes' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['source'] = 'manual';

        $today = now()->toDateString();
        if (!empty($data['checkin_time'])) {
            $data['checkin_time'] = $today . ' ' . $data['checkin_time'];
        }
        if (!empty($data['checkout_time'])) {
            $data['checkout_time'] = $today . ' ' . $data['checkout_time'];
        }

        $attendance = Attendance::create($data);

        \App\Jobs\ProcessAttendanceKPIJob::dispatch($attendance);

        return redirect()->back()->with('success', 'Attendance created successfully');
    }

    /**
     * Update an attendance from the SPA modal. Mirrors update() 1:1 — bare
     * H:i times get the record's own date prefixed, and the KPI job re-runs.
     */
    public function updatePopup(Request $request, Attendance $attendance)
    {
        abort_if(Gate::denies('attendance_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'driver_id'        => ['required', 'integer'],
            'checkin_time'     => ['nullable', 'string'],
            'checkout_time'    => ['nullable', 'string'],
            'delay_minutes'    => ['nullable', 'integer', 'min:0'],
            'overtime_minutes' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['source'] = 'manual';

        $date = $attendance->created_at ? $attendance->created_at->toDateString() : now()->toDateString();
        if (!empty($data['checkin_time']) && strlen($data['checkin_time']) <= 8) {
            $data['checkin_time'] = $date . ' ' . $data['checkin_time'];
        }
        if (!empty($data['checkout_time']) && strlen($data['checkout_time']) <= 8) {
            $data['checkout_time'] = $date . ' ' . $data['checkout_time'];
        }

        $attendance->update($data);

        \App\Jobs\ProcessAttendanceKPIJob::dispatch($attendance);

        return redirect()->back()->with('success', 'Attendance updated successfully');
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
