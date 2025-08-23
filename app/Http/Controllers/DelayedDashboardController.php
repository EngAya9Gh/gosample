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

        $user     = auth()->user();
        $clientId = $user->client_id;

        // المفتاح الديناميكي للكاش
        $cacheKey = $clientId ? "alerts_dashboard_client_{$clientId}" : "alerts_dashboard_all";

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($clientId) {
            // delayed tasks
            $pickup_delayedTasks   = Task::pickup_delayedTasks($clientId);
            $drop_off_delayedTasks = Task::drop_off_delayedTasks($clientId);
            $delayed_in_freezer    = Task::delayed_tasks_in_freezer($clientId);
            $delayed_delivered     = Task::delayed_tasks_delivered($clientId);

            // lost samples
            $lost_samples = Sample::when($clientId, function($q) use ($clientId) {
                    return $q->leftJoin('tasks','tasks.id','=','samples.task_id')
                            ->where('tasks.billing_client', $clientId);
                })
                ->where('samples.confirmed_by_client','LOST')
                ->get(); // إذا بدك العدد فقط: ->count()

            // play sound flag
            $play_sound = ($pickup_delayedTasks->isNotEmpty() || $drop_off_delayedTasks->isNotEmpty()) ? 1 : 0;

            return [
                'pickup_delayedTasks'     => $pickup_delayedTasks,
                'drop_off_delayedTasks'   => $drop_off_delayedTasks,
                'delayed_tasks_in_freezer'=> $delayed_in_freezer,
                'delayed_tasks_delivered' => $delayed_delivered,
                'clients'  => 0, // ثابت
                'cars'     => 0, // ثابت
                'lost_samples' => $lost_samples,
                'play_sound'   => $play_sound,
            ];
        });

        return view('alerts-dashboard', $data);
    }

    public function welcome()
    {
        return view('welcome');
    }
    
    
    
    

    
}
