<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Sample;
use App\Models\Task;
use Carbon\Carbon;
use Gate;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inertia Delayed (Alerts) dashboard (/app/delayeddashboard) — mirrors
 * App\Http\Controllers\DelayedDashboardController@index EXACTLY: real server Gate
 * `delayeddashboard`, scope by `client_id` (NOT assigned_client_ids), the 4-day
 * lookback in the Task model methods, the 2-min lost-samples cache, and the
 * KPI label↔count CROSS-WIRING preserved verbatim (see render() comments).
 */
class DelayedDashboardController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('delayeddashboard'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $taskModel = new Task();
        $clientId = auth()->user()->client_id ?? null;

        // Reuse the exact Task model delayed-set methods (4-day window inside them).
        $pickup    = $taskModel->pickup_delayedTasks($clientId)->load('to:id,name', 'driver:id,name');
        $dropoff   = $taskModel->drop_off_delayedTasks($clientId)->load('to:id,name', 'driver:id,name');
        $inFreezer = $taskModel->delayed_tasks_in_freezer($clientId)->load('to:id,name', 'driver:id,name'); // NOT client-scoped (model)
        $delivered = $taskModel->delayed_tasks_delivered($clientId)->load('to:id,name', 'driver:id,name');

        $playSound = ($pickup->count() > 0 || $dropoff->count() > 0) ? 1 : 0;

        // Lost samples — 2-min cache, same keys as the classic controller.
        $cacheKey = $clientId ? "lost_samples_client_{$clientId}" : 'lost_samples_admin';
        $lostSamples = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($clientId) {
            $q = Sample::leftJoin('tasks', 'tasks.id', '=', 'task_id')
                ->where('samples.confirmed_by_client', 'LOST')
                ->select('samples.*');
            if ($clientId) {
                $q->where('tasks.billing_client', $clientId);
            }
            return $q->get();
        });

        $taskRow = fn ($t, $dateField) => [
            'id'     => $t->id,
            'date'   => optional($t->{$dateField})?->format('Y-m-d H:i:s'),
            'to'     => optional($t->to)->name,
            'driver' => $t->driver ? ['id' => $t->driver->id, 'name' => $t->driver->name] : null,
        ];

        return Inertia::render('Dashboard/DelayedDashboard', [
            'playSound' => $playSound,
            // KPI counts. NOTE the intentional CROSS-WIRING preserved from the Blade:
            //  • the card labelled "Collected/In-Freezer" shows the DELIVERED count
            //  • the card labelled "Closed/Delivered"   shows the IN-FREEZER count
            'counts' => [
                'lost_samples'     => $lostSamples->count(),
                'pickup_delayed'   => $pickup->count(),
                'drop_off_delayed' => $dropoff->count(),
                'in_freezer_card'  => $delivered->count(), // labelled "Collected Delayed" (cross-wired)
                'delivered_card'   => $inFreezer->count(), // labelled "Closed Delayed"    (cross-wired)
            ],
            // Lists are labelled correctly (only the KPI cards are cross-wired).
            'lostSamples' => $lostSamples->map(fn ($s) => [
                'id'           => $s->id,
                'barcode_id'   => $s->barcode_id,
                'bag_code'     => $s->bag_code,
                'task_id'      => $s->task_id,
                'confirmed_by' => $s->confirmed_by,
                'updated_at'   => optional($s->updated_at)?->format('Y-m-d H:i:s'),
            ])->values(),
            'pickupDelayed'  => $pickup->map(fn ($t) => $taskRow($t, 'pickup_time'))->values(),
            'dropOffDelayed' => $dropoff->map(fn ($t) => $taskRow($t, 'dropoff_time'))->values(),
            'collected'      => $inFreezer->map(fn ($t) => $taskRow($t, 'collection_date'))->values(),
            'closed'         => $delivered->map(fn ($t) => $taskRow($t, 'freezer_out_date'))->values(),
        ]);
    }
}
