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

    public function show(AuditLog $auditLog)
    {
        abort_if(Gate::denies('audit_log_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.auditLogs.show', compact('auditLog'));
    }
}
