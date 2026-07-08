<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class AuditLogsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('audit_log_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->header('X-Inertia')) {
            $query = AuditLog::with([])
                ->when($request->search, fn ($q, $s) =>
                    $q->where('description', 'like', "%{$s}%")
                      ->orWhere('subject_type', 'like', "%{$s}%")
                      ->orWhere('host', 'like', "%{$s}%")
                      ->orWhere('user_id', 'like', "%{$s}%")
                )
                ->when($request->description, fn ($q, $v) =>
                    $q->where('description', $v)
                )
                ->orderBy('id', 'desc')
                ->paginate(50)
                ->withQueryString();

            // Collect unique descriptions for filter dropdown
            $descriptions = AuditLog::select('description')
                ->distinct()
                ->orderBy('description')
                ->pluck('description');

            return inertia('AuditLogs/AuditLogsList', [
                'logs'         => $query,
                'descriptions' => $descriptions,
                'filters'      => $request->only(['search', 'description']),
            ]);
        }

        if ($request->ajax()) {
            $query = AuditLog::query()->select(sprintf('%s.*', (new AuditLog())->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'audit_log_show';
                $editGate = 'audit_log_edit';
                $deleteGate = 'audit_log_delete';
                $crudRoutePart = 'audit-logs';

                return view('partials.datatablesActions', compact(
                'viewGate',
                'editGate',
                'deleteGate',
                'crudRoutePart',
                'row'
            ));
            });

            $table->editColumn('id', fn ($row) => $row->id ?? '');
            $table->editColumn('description', fn ($row) => $row->description ?? '');
            $table->editColumn('subject_id', fn ($row) => $row->subject_id ?? '');
            $table->editColumn('subject_type', fn ($row) => $row->subject_type ?? '');
            $table->editColumn('user_id', fn ($row) => $row->user_id ?? '');
            $table->editColumn('host', fn ($row) => $row->host ?? '');

            $table->rawColumns(['actions', 'placeholder']);

            return $table->make(true);
        }

        return view('admin.auditLogs.index');
    }

    public function show(AuditLog $auditLog)
    {
        abort_if(Gate::denies('audit_log_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.auditLogs.show', compact('auditLog'));
    }
}
