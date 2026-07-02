<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DailyOperationController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = auth()->user();

        // sort whitelist
        $sortColumn = $request->input('sort_by');
        if (!in_array($sortColumn, ['created_at', 'updated_at', 'collection_date'], true)) {
            $sortColumn = 'created_at';
        }
        $sortOrder = $request->input('sort_order', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'desc';
        }

        $query = Task::with(['from', 'to', 'client', 'driver', 'car'])->select('tasks.*');

        // fail-closed client scoping
        if ($user && !empty($user->assigned_client_ids)) {
            $query->whereIn('billing_client', $user->assigned_client_ids);
        }

        $query->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->delayed_reason, fn ($q, $v) => $q->where('delayed_reason', $v))
            ->when($request->driver_id, fn ($q, $v) => $q->where('driver_id', $v))
            ->when($request->billing_client, fn ($q, $v) => $q->where('billing_client', $v))
            ->when($request->from_location, fn ($q, $v) => $q->where('from_location', $v))
            ->when($request->to_location, fn ($q, $v) => $q->where('to_location', $v))
            ->when($request->keyword, fn ($q, $v) => $q->where('tasks.id', $v));

        $dateColumn = $request->input('search_date') ?: 'tasks.created_at';
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : null;
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : null;
        if (!$dateFrom && !$dateTo && !$request->keyword) {
            $dateFrom = Carbon::now()->subDays(30)->startOfDay();
            $dateTo = Carbon::now()->endOfDay();
        }
        if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }
        if ($dateFrom && $dateTo) {
            $query->whereBetween($dateColumn, [$dateFrom->toDateTimeString(), $dateTo->toDateTimeString()]);
        } elseif ($dateFrom) {
            $query->where($dateColumn, '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->where($dateColumn, '<=', $dateTo);
        }

        $query->orderBy($sortColumn, $sortOrder);

        $pageSize = max(1, min((int) $request->input('pageSize', 25), 1000));
        $page = max(1, (int) $request->input('page', 1));
        $total = (clone $query)->toBase()->getCountForPagination();
        $offset = ($page - 1) * $pageSize;

        $seq = $offset;
        $rows = $query->offset($offset)->limit($pageSize)->get()->map(function (Task $t) use (&$seq) {
            return [
                'sequence'           => ++$seq,
                'id'                 => $t->id,
                'created_at'         => $this->fmt($t->created_at),
                'client'             => optional($t->client)->english_name,
                'driver_name'        => optional($t->driver)->name,
                'car_plate'          => optional($t->car)->plate_number,
                'from_location_name' => optional($t->from)->name,
                'to_location_name'   => optional($t->to)->name,
                'from_location_arrival_time' => $this->fmt($t->from_location_arrival_time),
                'collection_date'    => $this->fmt($t->collection_date),
                'freezer_date'       => $this->fmt($t->freezer_date),
                'freezer_out_date'   => $this->fmt($t->freezer_out_date),
                'close_date'         => $this->fmt($t->close_date),
                'status'             => $t->status,
                'delayed_reason'     => $t->delayed_reason,
                'hours'              => $this->hours($t->collection_date, $t->close_date),
            ];
        });

        // Echo the filters back so the form stays populated across reloads.
        $filters = $request->only([
            'keyword', 'status', 'delayed_reason', 'driver_id', 'billing_client', 'from_location',
            'to_location', 'date_from', 'date_to', 'search_date', 'sort_by', 'sort_order',
        ]);

        return Inertia::render('DailyOperation/DailyOperationList', [
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'pageSize' => $pageSize,
            'filters'  => $filters,
            'options'  => fn () => $this->options($user),
        ]);
    }

    public function export(Request $request)
    {
        abort_if(Gate::denies('task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $loggedUser = auth()->user();

        // Garbage-collect old exports (older than 1 hour)
        $exportsDir = storage_path('app/exports');
        if (is_dir($exportsDir)) {
            $now = time();
            foreach ((array) @scandir($exportsDir) as $entry) {
                if ($entry === '.' || $entry === '..') continue;
                $p = $exportsDir . DIRECTORY_SEPARATOR . $entry;
                if (is_file($p) && ($now - @filemtime($p) > 3600)) {
                    @unlink($p);
                }
            }
        }

        // Generate a unique token for this export
        $token = Str::random(40);
        $filters = $request->only([
            'keyword', 'status', 'delayed_reason', 'driver_id', 'billing_client', 'from_location',
            'to_location', 'date_from', 'date_to', 'search_date'
        ]);

        // Dispatch background job
        \App\Jobs\GenerateDailyOperationExportJob::dispatch($token, $filters, $loggedUser?->id);

        return response()->json([
            'success' => true,
            'token' => $token,
            'message' => 'Export started in background.'
        ]);
    }

    public function checkExportStatus(string $token)
    {
        abort_if(Gate::denies('task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        
        $dir = storage_path('app/exports');
        $base = $dir . DIRECTORY_SEPARATOR . $token . '.xlsx';

        if (file_exists($base . '.error')) {
            return response()->json(['status' => 'error', 'message' => file_get_contents($base . '.error')]);
        }
        if (file_exists($base . '.done')) {
            return response()->json([
                'status' => 'ready', 
                'download_url' => route('app.daily-operation.export.download', ['token' => $token])
            ]);
        }
        if (file_exists($base)) {
            return response()->json(['status' => 'processing']);
        }
        
        return response()->json(['status' => 'not_found'], 404);
    }

    public function downloadExport(string $token)
    {
        abort_if(Gate::denies('task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        
        $path = storage_path('app/exports/' . $token . '.xlsx');
        if (!file_exists($path) || !file_exists($path . '.done')) {
            abort(404, 'Export file not found or not ready.');
        }

        return response()->download($path, 'Daily_Operation_Report.xlsx')->deleteFileAfterSend(true);
    }

    private function options($user): array
    {
        if ($user && !empty($user->assigned_client_ids)) {
            $clients = Client::whereIn('id', $user->assigned_client_ids)->get();
            $locations = Location::select('locations.*')
                ->leftJoin('client_location', 'client_location.location_id', 'locations.id')
                ->whereIn('client_location.client_id', $user->assigned_client_ids)
                ->distinct()
                ->get();
        } else {
            $clients = Client::all();
            $locations = Location::all();
        }
        $drivers = Driver::all();

        return [
            'drivers'   => $drivers->map(fn ($d) => ['value' => $d->id, 'label' => $d->name])->values(),
            'clients'   => $clients->map(fn ($c) => ['value' => $c->id, 'label' => $c->english_name])->values(),
            'locations' => $locations->map(fn ($l) => ['value' => $l->id, 'label' => $l->name])->values(),
        ];
    }

    private function fmt($value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return Carbon::parse($value)->format('d M H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function hours($from, $to): string
    {
        if (!$from || !$to) {
            return '';
        }
        try {
            $mins = Carbon::parse($from)->diffInMinutes(Carbon::parse($to));
            return sprintf('%02d Hours, %02d Minutes', intdiv($mins, 60), $mins % 60);
        } catch (\Throwable $e) {
            return '';
        }
    }
}
