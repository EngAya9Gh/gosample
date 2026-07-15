<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CarDashboardController as LegacyCarDashboard;
use Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inertia Car (Temperature) Dashboard (/app/car-dashboard) — mirrors
 * App\Http\Controllers\CarDashboardController@index. Same `car-dashboard` Gate,
 * same IMEI selection, and it REUSES the legacy controller's Afaqy code path
 * (generateAndSaveToken + getVehicleDataCustom — hard-coded creds, TLS-off,
 * projection) so the telemetry integration is identical. Temperature-only sensors.
 * Returns `available` + `cars`; the page polls this every 15s.
 */
class CarDashboardController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('car-dashboard'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = auth()->user();

        // IMEI list (identical to the legacy controller).
        if (isset($user->client_id)) {
            $imeis = DB::table('cars')
                ->join('drivers', 'drivers.id', '=', 'cars.driver_id')
                ->join('client_driver', 'client_driver.driver_id', '=', 'drivers.id')
                ->where('client_driver.client_id', $user->client_id)
                ->where('cars.afaqi', 1)->where('cars.status', 1)
                ->whereNull('cars.deleted_at')->whereNotNull('cars.imei')
                ->pluck('cars.imei')->toArray();
        } else {
            $imeis = DB::table('cars')
                ->where('afaqi', 1)->where('status', 1)
                ->whereNull('deleted_at')->whereNotNull('imei')
                ->pluck('imei')->toArray();
        }

        $cars = [];
        $available = true;

        try {
            $legacy = new LegacyCarDashboard();
            $token = $legacy->generateAndSaveToken();
            $data = $legacy->getVehicleDataCustom($token, $imeis);

            if ($data && isset($data['data'])) {
                foreach ($data['data'] as $vehicle) {
                    $temp = array_values(array_filter(
                        $vehicle['sensors'] ?? [],
                        fn ($s) => ($s['t'] ?? null) === 'temperature'
                    ));
                    $cars[] = [
                        'id'      => $vehicle['id'] ?? null,
                        'name'    => $vehicle['n'] ?? null,
                        'i'       => $vehicle['i'] ?? null,
                        'profile' => $vehicle['profile'] ?? null,
                        'sensors' => $temp,
                    ];
                }
            } else {
                $available = false;
            }
        } catch (\Throwable $e) {
            \Log::error('Afaqi error (app car dashboard): ' . $e->getMessage());
            $available = false;
        }

        // Attach the LOCAL car id so each dashboard card can link to
        // /app/admin/cars/{id}. Matched by IMEI first (the vehicle's 'i'
        // field), then by plate number as a fallback — both normalized,
        // since Afaqy values may differ in whitespace/case.
        if ($cars) {
            $local = \App\Models\Car::withoutGlobalScope('enabled')
                ->select('id', 'imei', 'plate_number')->get();
            $byImei = [];
            $byPlate = [];
            foreach ($local as $c) {
                if ($c->imei) $byImei[trim((string) $c->imei)] = $c->id;
                if ($c->plate_number) $byPlate[mb_strtolower(trim((string) $c->plate_number))] = $c->id;
            }
            foreach ($cars as &$car) {
                $imei = trim((string) ($car['i'] ?? ''));
                $plate = mb_strtolower(trim((string) ($car['profile']['plate_number'] ?? '')));
                $car['car_id'] = ($imei !== '' ? ($byImei[$imei] ?? null) : null)
                    ?? ($plate !== '' ? ($byPlate[$plate] ?? null) : null);
            }
            unset($car);
        }

        return Inertia::render('Dashboard/CarDashboard', [
            'available' => $available,
            'cars'      => $cars,
        ]);
    }
}
