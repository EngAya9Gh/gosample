<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Sample;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Carbon\Carbon;

class SamplesController extends Controller
{
    private function fmt($date): string
    {
        if (!$date) return '';
        try {
            return Carbon::parse($date)->format('Y-m-d H:i');
        } catch (\Throwable $e) {
            return (string) $date;
        }
    }

    public function lost(Request $request)
    {
        abort_if(Gate::denies('sample_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = auth()->user();

        $sortColumn = $request->input('sort_by', 'created_at');
        if (!in_array($sortColumn, ['created_at', 'updated_at'], true)) {
            $sortColumn = 'created_at';
        }
        $sortOrder = $request->input('sort_order', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'desc';
        }

        $query = Sample::with(['location', 'task', 'container'])->select('samples.*');

        if ($user && !empty($user->assigned_client_ids)) {
            $query->join('tasks', 'samples.task_id', '=', 'tasks.id');
            $query->whereIn('tasks.billing_client', $user->assigned_client_ids);
        }

        $status = $request->input('confirmed_by_client', 'LOST');
        if ($status !== 'ALL') {
            $query->where('samples.confirmed_by_client', $status);
        }

        $query->when($request->barcode_id, fn ($q, $v) => $q->where('barcode_id', 'like', "%{$v}%"))
              ->when($request->task_id, fn ($q, $v) => $q->where('task_id', $v));

        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : null;
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : null;
        if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }
        if ($dateFrom && $dateTo) {
            $query->whereBetween('samples.created_at', [$dateFrom->toDateTimeString(), $dateTo->toDateTimeString()]);
        } elseif ($dateFrom) {
            $query->where('samples.created_at', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->where('samples.created_at', '<=', $dateTo);
        }

        $query->orderBy($sortColumn, $sortOrder);

        $pageSize = max(1, min((int) $request->input('pageSize', 25), 1000));
        $page = max(1, (int) $request->input('page', 1));
        $total = (clone $query)->count();
        $offset = ($page - 1) * $pageSize;

        $seq = $offset;
        $rows = $query->offset($offset)->limit($pageSize)->get()->map(function (Sample $s) use (&$seq) {
            return [
                'sequence'           => ++$seq,
                'id'                 => $s->id,
                'barcode_id'         => $s->barcode_id,
                'location_name'      => optional($s->location)->name,
                'task_id'            => $s->task_id,
                'container_imei'     => optional($s->container)->imei,
                'sample_type'        => $s->sample_type,
                'temperature_type'   => $s->temperature_type,
                'bag_code'           => $s->bag_code,
                'confirmed_by_client'=> $s->confirmed_by_client,
                'confirmed_by'       => $s->confirmed_by,
                'created_at'         => $this->fmt($s->created_at),
            ];
        });

        $filters = $request->only(['barcode_id', 'task_id', 'confirmed_by_client', 'date_from', 'date_to', 'sort_by', 'sort_order']);
        if (!isset($filters['confirmed_by_client'])) {
            $filters['confirmed_by_client'] = 'LOST';
        }

        return Inertia::render('Samples/LostSamplesList', [
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'pageSize' => $pageSize,
            'filters'  => $filters,
        ]);
    }
}
