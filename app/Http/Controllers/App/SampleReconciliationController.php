<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SampleController;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Sample;
use Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inertia page controller for the merged "Scan & Reconcile" workspace
 * (/app/admin/tasks/reconcile). It unifies the two classic pages:
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

    /** Load the pending-sample batch for a driver + drop-off location. */
    public function loadBatch(Request $request, SampleController $samples)
    {
        abort_if(Gate::denies('task_scan'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        // getConfirmedSamplesPerDriverId() validates task_id is present but never uses it.
        $request->merge(['task_id' => $request->input('task_id', 0)]);

        return $samples->getConfirmedSamplesPerDriverId($request);
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

    /** Single-sample details lookup. */
    public function details(Request $request, SampleController $samples)
    {
        abort_if(Gate::denies('check_receiving_details'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $request->merge(['username' => auth()->user()->email]);

        return $samples->getSampleDetails($request);
    }

    /** Single-sample "Mark as lost" (method = MARK_LOST). */
    public function lost(Request $request, SampleController $samples)
    {
        abort_if(Gate::denies('mark_as_lost'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $request->merge(['marked_by' => auth()->user()->email]);

        return $samples->markSamplesAsLost($request);
    }
}
