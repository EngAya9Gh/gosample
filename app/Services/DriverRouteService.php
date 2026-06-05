<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Task;
use App\Models\Location;
use Carbon\Carbon;
use DB;

class DriverRouteService
{
    /**
     * Smart sort tasks based on geographical proximity
     */
    public function smartSortTasks($driverId)
    {
        $driver = Driver::with(['car.carTracking'])->find($driverId);
        if (!$driver) return;

        // Get all active tasks for today that need sorting
        $tasks = Task::where('driver_id', $driverId)
            ->whereDate('pickup_time', today())
            ->whereNotIn('status', ['CLOSED', 'NO_SAMPLES'])
            ->get();

        if ($tasks->isEmpty()) return;

        // Determine starting point
        $startPoint = $this->getDriverCurrentLocation($driver);

        $unsortedTasks = $tasks->keyBy('id')->toArray();
        $sortedTaskIds = [];
        $currentLocation = $startPoint;

        while (!empty($unsortedTasks)) {
            $nearestTaskId = null;
            $shortestTime = PHP_INT_MAX;

            foreach ($unsortedTasks as $taskId => $task) {
                $fromLocation = Location::find($task['from_location']);
                if (!$fromLocation) continue;

                $targetPoint = $fromLocation->lat . ',' . $fromLocation->lng;
                
                // Calculate distance/time
                $travelTime = $this->getTravelTime($currentLocation, $targetPoint);

                if ($travelTime < $shortestTime) {
                    $shortestTime = $travelTime;
                    $nearestTaskId = $taskId;
                }
            }

            if ($nearestTaskId) {
                $sortedTaskIds[] = $nearestTaskId;
                $nearestFrom = Location::find($unsortedTasks[$nearestTaskId]['from_location']);
                $currentLocation = $nearestFrom->lat . ',' . $nearestFrom->lng;
                unset($unsortedTasks[$nearestTaskId]);
            } else {
                // Fallback if something went wrong
                $sortedTaskIds = array_merge($sortedTaskIds, array_keys($unsortedTasks));
                break;
            }
        }

        // Update database with new order
        foreach ($sortedTaskIds as $index => $taskId) {
            Task::where('id', $taskId)->update([
                'poririty' => $index + 1,
                'route_order' => $index + 1
            ]);
        }

        // Recalculate ETAs after sorting
        $this->recalculateAllETAs($driverId);
    }

    /**
     * Recalculate ETAs for all active tasks sequentially
     */
    public function recalculateAllETAs($driverId)
    {
        $driver = Driver::with(['car.carTracking'])->find($driverId);
        if (!$driver) return;

        $tasks = Task::where('driver_id', $driverId)
            ->whereDate('pickup_time', today())
            ->whereNotIn('status', ['CLOSED', 'NO_SAMPLES'])
            ->orderBy('poririty', 'asc')
            ->get();

        if ($tasks->isEmpty()) return;

        $currentLocation = $this->getDriverCurrentLocation($driver);
        $cumulativeSeconds = 0;
        $now = Carbon::now();

        foreach ($tasks as $index => $task) {
            $fromLocation = Location::find($task->from_location);
            $toLocation = Location::find($task->to_location);

            if (!$fromLocation || !$toLocation) {
                $task->update([
                    'route_order' => $index + 1,
                    'eta' => null,
                    'cumulative_eta' => null,
                    'estimated_arrival_time' => null
                ]);
                continue;
            }

            $pickupPoint = $fromLocation->lat . ',' . $fromLocation->lng;
            $dropoffPoint = $toLocation->lat . ',' . $toLocation->lng;

            if (in_array($task->status, ['COLLECTED', 'IN_FREEZER', 'OUT_FREEZER'])) {
                $timeToPickup = 0;
                $timeToDropoff = $this->getTravelTime($currentLocation, $dropoffPoint);
                
                $cumulativeSeconds += $timeToDropoff;
                $cumulativeEtaMinutes = (int) ceil($cumulativeSeconds / 60);
                $estimatedArrivalTime = $now->copy()->addSeconds($cumulativeSeconds);
                $individualEtaMinutes = (int) ceil($timeToDropoff / 60);
            } else {
                $timeToPickup = $this->getTravelTime($currentLocation, $pickupPoint);
                $timeToDropoff = $this->getTravelTime($pickupPoint, $dropoffPoint);
                
                $cumulativeSeconds += $timeToPickup;
                $cumulativeEtaMinutes = (int) ceil($cumulativeSeconds / 60);
                $estimatedArrivalTime = $now->copy()->addSeconds($cumulativeSeconds);
                $individualEtaMinutes = (int) ceil($timeToPickup / 60);
                
                // Add the time it takes to go from pickup to dropoff so the next task starts after dropoff
                $cumulativeSeconds += $timeToDropoff;
            }

            $task->update([
                'route_order' => $index + 1,
                'eta' => $individualEtaMinutes,
                'cumulative_eta' => $cumulativeEtaMinutes,
                'estimated_arrival_time' => $estimatedArrivalTime
            ]);

            // For the next task, the driver starts from THIS task's dropoff point
            $currentLocation = $dropoffPoint;
        }
    }

    private function getDriverCurrentLocation($driver)
    {
        // 1. Get car tracking first (Most accurate)
        $car = $driver->car;
        if ($car) {
            $carTracking = $car->carTracking()->orderBy('created_at', 'desc')->first();
            if ($carTracking) {
                return $carTracking->lat . ',' . $carTracking->lng;
            }
        }

        // 2. Fallback to last CLOSED task's dropoff location
        $lastCompletedTask = Task::where('driver_id', $driver->id)
            ->whereDate('pickup_time', today())
            ->where('status', 'CLOSED')
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($lastCompletedTask && $lastCompletedTask->to_location) {
            $loc = Location::find($lastCompletedTask->to_location);
            if ($loc) {
                return $loc->lat . ',' . $loc->lng;
            }
        }

        // 3. Fallback to driver's first task from_location
        $firstTask = Task::where('driver_id', $driver->id)
            ->whereDate('pickup_time', today())
            ->first();

        if ($firstTask) {
            $loc = Location::find($firstTask->from_location);
            if ($loc) return $loc->lat . ',' . $loc->lng;
        }

        // 4. Default Riyadh center fallback
        return '24.7136,46.6753';
    }

    private function getTravelTime($origin, $destination)
    {
        if (!$origin || !$destination) return 0;

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'query' => [
                    'origins' => $origin,
                    'destinations' => $destination,
                    'key' => 'AIzaSyCBu_5dYX7nfDtJ1mzrsumkMmhmymoDvN0',
                    'mode' => 'driving',
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if (!empty($data['rows'][0]['elements'][0]['duration']['value'])) {
                return $data['rows'][0]['elements'][0]['duration']['value'];
            }
        } catch (\Exception $e) {
            // Fallback to Haversine
        }

        // --- Fallback: Haversine distance ---
        list($lat1, $lon1) = explode(',', $origin);
        list($lat2, $lon2) = explode(',', $destination);

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $kilometers = $dist * 60 * 1.1515 * 1.609344;

        // Assume average speed in city is 40 km/h (which is 40/3600 km/s)
        // Time in seconds = distance / speed
        // To be safe and realistic for Riyadh traffic, let's assume 30 km/h
        $speedKmH = 30;
        $timeInHours = $kilometers / $speedKmH;
        $timeInSeconds = $timeInHours * 3600;

        // Add 5 minutes (300 seconds) overhead for traffic lights / parking
        return (int) ($timeInSeconds + 300);
    }
}
