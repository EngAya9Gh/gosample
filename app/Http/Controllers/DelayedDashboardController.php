<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Car;
use App\Models\Notifications;
use App\Models\Driver;
use App\Models\Sample;
use App\Models\Task;
use App\Models\Client;
use App\Models\Location;
use DB;
use Illuminate\Support\Carbon;

use Akaunting\Apexcharts\Chart;

use Dompdf\Dompdf;
use Illuminate\Support\Facades\View;

use Gate;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

class DelayedDashboardController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('delayeddashboard'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $taskModel = new Task();
        $loggedUser = auth()->user();

        // =========================
        // Determine client scope
        // =========================
        $clientId = $loggedUser->client_id ?? null;

        // =========================
        // Delayed tasks
        // =========================
        $pickup_delayedTasks = $taskModel->pickup_delayedTasks($clientId);
        $drop_off_delayedTasks = $taskModel->drop_off_delayedTasks($clientId);
        $delayed_tasks_in_freezer = $taskModel->delayed_tasks_in_freezer($clientId);
        $delayed_tasks_delivered = $taskModel->delayed_tasks_delivered($clientId);

        // =========================
        // Play sound if any delayed tasks exist
        // =========================
        $hasDelayed = ($pickup_delayedTasks->count() > 0) || ($drop_off_delayedTasks->count() > 0);
        $play_sound = $hasDelayed ? 1 : 0;

        // =========================
        // Lost samples with optional caching
        // =========================
        $cacheKey = $clientId ? "lost_samples_client_{$clientId}" : "lost_samples_admin";

        $lost_samples = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($clientId) {
            $query = Sample::leftJoin('tasks','tasks.id','=','task_id')
                ->where('samples.confirmed_by_client','LOST');

            if ($clientId) {
                $query->where('tasks.billing_client', $clientId);
            }

            return $query->get();
        });

        // =========================
        // Other variables
        // =========================
        $clients = 0;
        $cars = 0;

        // =========================
        // Return view
        // =========================
        return view('alerts-dashboard', [
            'pickup_delayedTasks'     => $pickup_delayedTasks,
            'drop_off_delayedTasks'   => $drop_off_delayedTasks,
            'delayed_tasks_in_freezer'=> $delayed_tasks_in_freezer,
            'delayed_tasks_delivered' => $delayed_tasks_delivered,
            'clients'                 => $clients,
            'lost_samples'            => $lost_samples,
            'cars'                    => $cars,
            'play_sound'              => $play_sound,
        ]);
    }


    public function welcome()
    {
        return view('welcome');
    }
    
    
    
    

    
}
