<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateDriverETAJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $driverId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($driverId)
    {
        $this->driverId = $driverId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if ($this->driverId) {
                app(\App\Services\DriverRouteService::class)->recalculateAllETAs($this->driverId);
            }
        } catch (\Exception $e) {
            \Log::error('ETA Calculation failed for driver ' . $this->driverId . ': ' . $e->getMessage());
        }
    }
}
