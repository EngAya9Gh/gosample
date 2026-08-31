<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SampleController;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Sample;
use App\Models\Task;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inertia page controller for the merged "Scan Sample" workspace
 * (/app/admin/tasks/scan). It unifies the two classic pages:
 *   - Admin\TasksController@scan    (batch scan: driver + location → confirm samples)
 *   - Admin\TasksController@missing (single-sample triage: details / lost / confirm)
 *
 * The batch-load / per-sample-check / confirm-all logic lives in
 * App\Http\Controllers\SampleController but is registered behind the driver-token
 * API guard (auth:drivers). The SPA runs on the web session and cannot hit those
 * /api routes from the browser, so these thin endpoints run under the /app
 * web-auth session and DELEGATE to the exact same SampleController methods —
 * identical behaviour (confirmation_method, task roll-up), zero logic drift.
 */
class SampleReconciliationController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('task_scan'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = auth()->user();

        // Drop-off locations — fail-closed to the user's assigned clients (mirrors classic scan()).
        if (!empty($user->assigned_client_ids)) {
            $locations = Location::leftJoin('client_location', 'client_location.location_id', 'locations.id')
                ->whereIn('client_location.client_id', $user->assigned_client_ids)
                ->orderBy('locations.name', 'asc')
                ->select('locations.id', 'locations.name')
                ->distinct()
                ->get();
        } else {
            $locations = Location::orderBy('name', 'asc')->select('id', 'name')->get();
        }

        $drivers = Driver::orderBy('name', 'asc')->select('id', 'name')->get();

        // Recent missing / pending quick-pick chips (mirrors classic missing()):
        // the 6 most-recently-updated samples the client hasn't confirmed.
        $recentMissingSamples = Sample::whereIn('confirmed_by_client', ['NO', 'LOST'])
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get(['id', 'barcode_id', 'confirmed_by_client', 'updated_at']);

        return Inertia::render('Tasks/SampleReconciliation', [
            'drivers'              => $drivers,
            'locations'            => $locations,
            'recentMissingSamples' => $recentMissingSamples,
            'can'                  => [
                'mark_as_lost'                     => Gate::allows('mark_as_lost'),
                'confirm_all'                      => Gate::allows('confirm_all'),
                'check_receiving_details'          => Gate::allows('check_receiving_details'),
                'check_receiving_details_advanced' => Gate::allows('check_receiving_details_advanced'),
            ],
        ]);
    }

    // ---- Delegating action endpoints (all under the /app web-auth session) ----

    /**
     * Load the pending-sample batch for a driver + drop-off location, PLUS the
     * real reconciliation breakdown (expected / received / pending / lost) for
     * that selection so the SPA stat cards start from live DB numbers.
     */
    public function loadBatch(Request $request, SampleController $samples)
    {
        abort_if(Gate::denies('task_scan'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        // getConfirmedSamplesPerDriverId() validates task_id is present but never uses it.
        $request->merge(['task_id' => $request->input('task_id', 0)]);

        // The base Controller::response() helper returns a plain Illuminate\Http\Response
        // (not a JsonResponse), so decode its JSON content rather than calling getData().
        $payload = json_decode($samples->getConfirmedSamplesPerDriverId($request)->getContent(), true) ?: [];
        if (empty($payload['status'])) {
            return response()->json($payload); // {status:false, message:...}
        }

        $pending = $payload['data'] ?? [];

        return response()->json([
            'status'  => true,
            'message' => 'success',
            'data'    => $pending,
            'stats'   => $this->reconcileStats($request->driver_id, $request->location_id, count($pending)),
        ]);
    }

    /**
     * DB breakdown of samples for a driver + drop-off location, grouped by
     * confirmed_by_client. Scoping mirrors getConfirmedSamplesPerDriverId
     * (same driver + to_location + billing_client resolution).
     */
    private function reconcileStats($driverId, $locationId, int $pendingFallback): array
    {
        $lastTask = Task::where('driver_id', $driverId)->where('status', 'CLOSED')
            ->orderBy('id', 'desc')->first();

        if ($locationId) {
            $toLocation    = $locationId;
            $billingClient = DB::table('client_location')->where('location_id', $locationId)->value('client_id');
        } else {
            $toLocation    = optional($lastTask)->to_location;
            $billingClient = optional($lastTask)->billing_client;
        }

        $counts = Sample::join('tasks', 'tasks.id', '=', 'samples.task_id')
            ->where('tasks.driver_id', $driverId)
            ->where('tasks.to_location', $toLocation)
            ->where('tasks.billing_client', $billingClient)
            ->selectRaw('samples.confirmed_by_client as s, COUNT(*) as c')
            ->groupBy('samples.confirmed_by_client')
            ->pluck('c', 's');

        $received = (int) ($counts['YES'] ?? 0);
        $lost     = (int) ($counts['LOST'] ?? 0);
        $pending  = (int) ($counts['NO'] ?? $pendingFallback);

        return [
            'expected' => $received + $pending + $lost,
            'received' => $received,
            'pending'  => $pending,
            'lost'     => $lost,
        ];
    }

    /** Confirm a single scanned sample as received (method = SCAN). */
    public function checkSample(Request $request, SampleController $samples)
    {
        abort_if(Gate::denies('task_scan'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $request->merge(['confirmed_by' => auth()->user()->name]);

        return $samples->checkSample($request);
    }

    /** Confirm every still-pending sample for the selected driver + location. */
    public function confirmAll(Request $request, SampleController $samples)
    {
        abort_if(Gate::denies('confirm_all'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $request->merge(['confirm_by' => auth()->user()->name]);

        return $samples->confirmAll($request);
    }

    /** Single-sample "Mark as confirmed" (method = MARK_CONFIRMED). */
    public function confirm(Request $request, SampleController $samples)
    {
        $request->merge([
            'confirmed_by' => auth()->user()->name,
            'method'       => $request->input('method', 'MARK_CONFIRMED'),
        ]);

        return $samples->confirmSamples($request);
    }

    public function details(Request $request, SampleController $samples)
    {
        abort_if(Gate::denies('check_receiving_details'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $request->merge(['username' => auth()->user()->email]);

        $response = $samples->getSampleDetails($request);
        $content = json_decode($response->getContent(), true);

        if (!empty($content['status']) && !empty($content['data']['confirmed_by'])) {
            $name = $content['data']['confirmed_by'];
            $user = \App\Models\User::where('name', $name)->first();
            if (!$user) {
                $user = \App\Models\Driver::where('name', $name)->first();
            }
            if ($user && $user->email) {
                $content['data']['confirmed_by_email'] = $user->email;
            }
            return response()->json($content);
        }

        return $response;
    }

    /** Single-sample "Mark as lost" (method = MARK_LOST). */
    public function lost(Request $request, SampleController $samples)
    {
        abort_if(Gate::denies('mark_as_lost'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $request->merge(['marked_by' => auth()->user()->email]);

        return $samples->markSamplesAsLost($request);
    }
}
