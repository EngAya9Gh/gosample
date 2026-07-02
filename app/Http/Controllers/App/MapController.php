<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Driver;

class MapController extends Controller
{
    /**
     * Display the initial Live Map view.
     */
    public function index()
    {
        $logged_id_user = auth()->user();

        // Get dropdown options
        if (!empty($logged_id_user->assigned_client_ids)) {
            $drivers = Driver::leftJoin('client_driver', 'driver_id', 'drivers.id')
                ->where('client_driver.client_id', $logged_id_user->client_id)
                ->select('drivers.id', 'drivers.name')
                ->get();
                
            $plateNumbers = Driver::leftJoin('client_driver', 'driver_id', 'drivers.id')
                ->leftJoin('cars', 'cars.driver_id', 'drivers.id')
                ->where('client_driver.client_id', $logged_id_user->client_id)
                ->whereNotNull('cars.plate_number')
                ->where('cars.plate_number', '!=', '')
                ->select('cars.plate_number')
                ->distinct()
                ->pluck('cars.plate_number')
                ->toArray();
        } else {
            $drivers = Driver::select('id', 'name')->get();
            $plateNumbers = Driver::leftJoin('cars', 'cars.driver_id', '=', 'drivers.id')
                ->whereNotNull('cars.plate_number')
                ->where('cars.plate_number', '!=', '')
                ->select('cars.plate_number')
                ->distinct()
                ->pluck('cars.plate_number')
                ->toArray();
        }

        return Inertia::render('Map/LiveMap', [
            'drivers' => $drivers,
            'plateNumbers' => $plateNumbers,
        ]);
    }

    /**
     * AJAX endpoint to fetch driver locations for the map.
     */
    public function filter(Request $request)
    {
        $logged_id_user = auth()->user();

        $locations = Driver::select('drivers.*', 'imei', 'plate_number', 'model')
            ->leftJoin('cars', 'cars.driver_id', 'drivers.id')
            ->when($logged_id_user->client_id, function($q) use($logged_id_user) {
                $q->leftJoin('client_driver', 'client_driver.driver_id', 'drivers.id')
                  ->where('client_driver.client_id', $logged_id_user->client_id);
            })
            ->whereNotNull('cars.lat')
            ->where('cars.status', 1)
            ->with([
                'driverActiveTasks' => function ($query) use ($logged_id_user) {
                    if (!empty($logged_id_user->assigned_client_ids)) {
                        $query->whereIn('billing_client', $logged_id_user->assigned_client_ids);
                    }
                },
                'driverActiveDelayedTasks',
                'driverActiveTasks.from',
                'driverActiveTasks.to',
                'driverActiveTasks.samples',
                'car',
                'car.carTracking'
            ]);

        if ($request->driver_id) {
            $locations->where('drivers.id', $request->driver_id);
        }
        if ($request->imei) {
            $locations->where('cars.imei', $request->imei);
        }
        if ($request->plate_number) {
            $locations->where('cars.plate_number', $request->plate_number);
        }

        $locations = $locations->get();

        // Get latest tracking coordinates
        foreach($locations as &$loc) {
            if ($loc->car && $loc->car->carTracking->isNotEmpty()) {
                $latestTracking = $loc->car->carTracking->sortByDesc('created_at')->first();
                $loc->lat = $latestTracking?->lat;
                $loc->lng = $latestTracking?->lng;
            }
        }

        return response()->json($locations);
    }
}
