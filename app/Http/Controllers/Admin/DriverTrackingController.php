<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Driver;
use App\Models\Location;
use Gate;
use Symfony\Component\HttpFoundation\Response;

class DriverTrackingController extends Controller
{
    public function clientDashboard(Request $request)
    {
        abort_if(Gate::denies('driver_tracking_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = auth()->user();
        // If it's an admin, they can see all drivers? 
        // The requirement said "العميل يرى فقط التاسكات التابعة له"
        // Let's allow admins to see everything or bypass the client filter.
        $isAdminUser = $user && ($user->hasAnyRole(['Admin', 'Super Admin', 'SuperAdmin']) || (method_exists($user, 'getIsAdminAttribute') && $user->is_admin));
        
        $clientId = $user->client_id;
        
        if (!$clientId && !$isAdminUser) {
            abort(403, 'This page is only accessible for clients.');
        }

        // Get active tasks for this client today or future (or all if admin)
        $clientTasksQuery = Task::whereBetween('pickup_time', [
                \Carbon\Carbon::today()->startOfDay(),
                \Carbon\Carbon::today()->endOfDay(),
            ])
            ->whereNotIn('status', ['CLOSED', 'NO_SAMPLES']);
            
        if ($clientId) {
            $clientTasksQuery->where('billing_client', $clientId);
        }

        $clientTasks = $clientTasksQuery->get();
        $driverIds = $clientTasks->pluck('driver_id')->unique()->filter();

        // Load drivers and eagerly load their car, but NOT all tracking records to avoid memory leak
        $driversQuery = Driver::with(['car'])->whereIn('id', $driverIds);
        
        if ($request->filled('driver_name')) {
            $driversQuery->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->driver_name . '%')
                  ->orWhere('username', 'like', '%' . $request->driver_name . '%');
            });
        }
        
        $drivers = $driversQuery->get();

        // Get all driver IDs
        $fetchedDriverIds = $drivers->pluck('id')->toArray();

        // Load all tasks for these drivers in a SINGLE query
        $allTasks = Task::with(['from', 'to', 'client'])
            ->whereIn('driver_id', $fetchedDriverIds)
            ->whereBetween('pickup_time', [
                \Carbon\Carbon::today()->startOfDay(),
                \Carbon\Carbon::today()->endOfDay(),
            ])
            ->whereNotIn('status', ['CLOSED', 'NO_SAMPLES'])
            ->orderBy('route_order', 'asc')
            ->orderBy('poririty', 'asc')
            ->get()
            ->groupBy('driver_id');

        // Load latest car tracking for cars in a single query
        $carIds = $drivers->pluck('car.id')->filter()->toArray();
        $latestCarTrackings = \App\Models\CarTracking::whereIn('car_id', $carIds)
            ->whereIn('id', function($query) {
                $query->selectRaw('MAX(id)')->from('car_tracking')->groupBy('car_id');
            })
            ->get()
            ->keyBy('car_id');

        $driverData = [];

        foreach ($drivers as $driver) {
            $driverTasks = $allTasks->get($driver->id, collect());

            // Current location of driver
            $lat = null;
            $lng = null;
            if ($driver->car && $latestCarTrackings->has($driver->car->id)) {
                $carTracking = $latestCarTrackings->get($driver->car->id);
                $lat = $carTracking->lat;
                $lng = $carTracking->lng;
            }

            $driverData[] = [
                'driver' => (object)[
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'lat' => $lat,
                    'lng' => $lng,
                ],
                'tasks' => $driverTasks
            ];
        }

        // SPA (/app) page — same data, serialized flat (driver + ordered task
        // steps) instead of whole Eloquent models.
            $drivers = collect($driverData)->map(function ($data) {
                $d = $data['driver'];
                return [
                    'id'   => $d->id,
                    'name' => $d->name,
                    'lat'  => $d->lat,
                    'lng'  => $d->lng,
                    'tasks' => collect($data['tasks'])->map(fn ($t) => [
                        'id'                     => $t->id,
                        'status'                 => $t->status,
                        'client_name'            => $t->client->english_name ?? null,
                        'from_name'              => $t->from->name ?? null,
                        'to_name'                => $t->to->name ?? null,
                        'pickup_time'            => $t->pickup_time ? \Carbon\Carbon::parse($t->pickup_time)->format('Y-m-d h:i A') : null,
                        'estimated_arrival_time' => $t->estimated_arrival_time ? \Carbon\Carbon::parse($t->estimated_arrival_time)->format('h:i A') : null,
                    ])->values(),
                ];
            })->values();

            return \Inertia\Inertia::render('Tasks/DriverTracking', [
                'drivers' => $drivers,
            ]);
    }
}
