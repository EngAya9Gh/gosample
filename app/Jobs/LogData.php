<?php

namespace App\Jobs;

use App\Models\Driver;
use App\Models\Sample;
use App\Models\SampleTracking;
use App\Services\LogService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 3; // Retry the job up to 3 times if it fails
    public $backoff = 60; // Wait 60 seconds before retrying

    public $task,$status,$time;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($task,$status,$time)
    {
        $this->task = $task;
        $this->status = $status;
        $date = \Carbon\Carbon::parse($time);
        $this->time = $date->toDateTimeString();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $logService = new LogService();
        if (isset($this->task->id) && $logService->hasIntegration($this->task) && $this->task->is_blazma) {
            if ($this->status == 'picked up') {
                $sampleCheck = \App\Models\Sample::where('task_id', $this->task->id)->where('is_blazma', 0)->pluck('barcode_id')->toArray();
                if (!empty($sampleCheck)) {
                    $sampleTrackingUpdates = \App\Models\SampleTracking::whereIn('sample_id', $sampleCheck)->where('is_collected', 0)->get();
                    if (!empty($sampleTrackingUpdates)) {
                        foreach ($sampleTrackingUpdates as $sampleTrackingUpdate) {
                            $sampleTracking = \App\Models\SampleTracking::where('id', $sampleTrackingUpdate->id)->where('is_collected', 0)->first();
                            if (isset($sampleTracking->id)) {
                                $sampleTracking->is_collected = true;
                                $sampleTracking->task_id = $this->task->id;
                                $sampleTracking->save();
                                $sampleUpdate = \App\Models\Sample::where('barcode_id', $sampleTrackingUpdate->sample_id)->where('task_id', $this->task->id)->first();
                                if (isset($sampleUpdate->id)) {
                                    $sampleUpdate->profile_id = $sampleTrackingUpdate->profile_id;
                                    $sampleUpdate->order_id = $sampleTrackingUpdate->order_id;
                                    $sampleUpdate->hospital_id = $sampleTrackingUpdate->hospital_id;
                                    $sampleUpdate->hospital_name = $sampleTrackingUpdate->hospital_name;
                                    $sampleUpdate->collection_hospital_id = $sampleTrackingUpdate->collection_hospital_id;
                                    $sampleUpdate->collection_hospital_name = $sampleTrackingUpdate->collection_hospital_name;
                                    $sampleUpdate->is_blazma = true;
                                    $sampleUpdate->save();
                                }
                            }

                        }
                    }
                }
                \App\Models\SampleTracking::where('task_id', $this->task->id)->delete();
            }
            $samples = \App\Models\Sample::where('task_id', $this->task->id)->where('is_blazma', 1)->distinct('barcode_id')->get();
            $driver = \App\Models\Driver::find($this->task->driver_id);
            $driver_name = null;
            $driver_id = null;
            if (isset($driver->id)) {
                $driver_name = $driver->name;
                $driver_id = $driver->id;
            }
            $checkSamples = [];
            $data = [];
            foreach ($samples as $sample) {
                if (in_array($sample->barcode_id, $checkSamples)) {
                    continue;
                }
                $checkSamples[] = $sample->barcode_id;
                $data[] = [
                    'PROFILE_ID' => "$sample->profile_id",
                    'END_USER_LAB_ORDER_PACKAGE_ID' => "$sample->barcode_id",
                    'END_USER_LAB_ORDER_ID' => "$sample->order_id" ?? null,
                    'SYSTEM_USER_ID' => "$driver_id",
                    'SYSTEM_USER_NAME' => $driver_name,
                    'UPDATED_COLUMN' => $this->status,
                    'PREUPDATE' => null,
                    'POSTUPDATE' => null,
                    'UPDATE_TIME' => $this->time,
                    'STATUS_ID' => null,
                    'STATUS_NAME' => null,
                    'CLIENT_ID' => null,
                    'CLIENT_NAME' => null,
                    'END_USER_IS_COMPANY' => "0",
                    'LAB_CATEGORY_PACKAGE_TEST_CATEGORY' => null,
                    'LAB_CATEGORY_PACKAGE_ID' => "-1",
                    'LAB_CATEGORY_PACKAGE_NAME' => null,
                    'HOSPITAL_ID' => "$sample->collection_hospital_id" ?? null,
                    'HOSPITAL_NAME' => $sample->collection_hospital_name ?? null,//collection branch
                    'PROFILE_UNIT_ID' => null,
                    'PROFILE_UNIT_NAME' => null,
                    'PROCESSING_HOSPITAL_ID' => "$sample->hospital_id",
                    'PROCESSING_HOSPITAL_NAME' => $sample->hospital_name,
                    'IDENTIFICATION' => null,
                    'PATIENT_NAME' => null,
                    'ANALYZER_ID' => null,
                    'ANALYZER_NAME' => null,
                    'HOSPITAL_BRANCH_ID' => "$sample->hospital_id",
                    'HOSPITAL_BRANCH_NAME' => $sample->hospital_name, //processing branch
                    'ICON' => $this->updateColumn ?? null,
                    'IS_MTC' => true
                ];
            }

            if (!empty($data)) {
                $response = $logService->addLog($data);
                if (!$response->success) {
                    throw new \Exception("External Log API failed: " . json_encode($response->errors));
                }
            }
        }
    }
}
