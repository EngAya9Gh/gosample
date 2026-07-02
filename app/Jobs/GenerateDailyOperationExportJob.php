<?php

namespace App\Jobs;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GenerateDailyOperationExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 1800;

    public function __construct(
        public string $token,
        public array $filters,
        public ?int $userId = null,
    ) {}

    public function handle(): void
    {
        $startTime = microtime(true);
        \Log::info('GenerateDailyOperationExportJob started', [
            'token' => $this->token,
            'user_id' => $this->userId,
            'filters' => $this->filters
        ]);

        DB::disableQueryLog();
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $dir  = storage_path('app/exports');
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        $path = $dir . DIRECTORY_SEPARATOR . $this->token . '.xlsx';

        try {
            $f = $this->filters;

            $query = Task::query()->with([
                'driver:id,name',
                'client:id,english_name',
                'from:id,name',
                'to:id,name',
                'car:id,plate_number'
            ]);

            // Mirror filtering from the DailyOperationController
            $dateColumn = $f['search_date'] ?? 'tasks.created_at';
            $dateFrom = !empty($f['date_from']) ? Carbon::parse($f['date_from'])->startOfDay() : null;
            $dateTo = !empty($f['date_to']) ? Carbon::parse($f['date_to'])->endOfDay() : null;
            
            if (!$dateFrom && !$dateTo && empty($f['keyword'])) {
                $dateFrom = Carbon::now()->subDays(30)->startOfDay();
                $dateTo = Carbon::now()->endOfDay();
            }
            if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }
            if ($dateFrom && $dateTo) {
                $query->whereBetween($dateColumn, [$dateFrom->toDateTimeString(), $dateTo->toDateTimeString()]);
            } elseif ($dateFrom) {
                $query->where($dateColumn, '>=', $dateFrom);
            } elseif ($dateTo) {
                $query->where($dateColumn, '<=', $dateTo);
            }

            if (!empty($f['status']))         { $query->where('status', $f['status']); }
            if (!empty($f['delayed_reason'])) { $query->where('delayed_reason', $f['delayed_reason']); }
            if (!empty($f['billing_client'])) { $query->where('billing_client', $f['billing_client']); }
            if (!empty($f['from_location'])) { $query->where('from_location', $f['from_location']); }
            if (!empty($f['to_location']))   { $query->where('to_location', $f['to_location']); }
            if (!empty($f['driver_id']))     { $query->where('driver_id', $f['driver_id']); }
            if (!empty($f['keyword']))       { $query->where('tasks.id', $f['keyword']); }

            $writer = new \OpenSpout\Writer\XLSX\Writer();
            $writer->openToFile($path);

            $headerStyle = (new \OpenSpout\Common\Entity\Style\Style())
                ->setFontBold()->setFontSize(11);

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                'Task ID', 'From Location', 'To Location', 'Client', 'Driver', 'Car', 
                'Status', 'Delay Reason', 'Arrival Time', 'Close Date', 'Hours',
                'Collection Date', 'Freezer Date', 'Freezer Out Date'
            ], $headerStyle));

            $count = 0;
            $query->orderBy('tasks.id', 'desc')->chunkById(500, function ($tasks) use ($writer, &$count) {
                foreach ($tasks as $t) {
                    if ($count >= 30000) {
                        \Log::info('GenerateDailyOperationExportJob reached maximum limit of 30,000 records.', ['token' => $this->token]);
                        return false;
                    }

                    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                        (string) $t->id,
                        optional($t->from)->name           ?: 'N/A',
                        optional($t->to)->name             ?: 'N/A',
                        optional($t->client)->english_name ?: 'N/A',
                        optional($t->driver)->name         ?: 'N/A',
                        optional($t->car)->plate_number    ?: 'N/A',
                        $t->status                         ?: 'N/A',
                        $t->delayed_reason                 ?: '',
                        $t->from_location_arrival_time     ? Carbon::parse($t->from_location_arrival_time)->format('Y-m-d H:i') : '',
                        $t->close_date                     ? Carbon::parse($t->close_date)->format('Y-m-d H:i') : '',
                        $this->calculateHours($t->collection_date, $t->close_date),
                        $t->collection_date                ? Carbon::parse($t->collection_date)->format('Y-m-d H:i') : '',
                        $t->freezer_date                   ? Carbon::parse($t->freezer_date)->format('Y-m-d H:i') : '',
                        $t->freezer_out_date               ? Carbon::parse($t->freezer_out_date)->format('Y-m-d H:i') : '',
                    ]));
                    $count++;
                }
            });

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Total records exported', $count]));
            $writer->close();

            @file_put_contents($path . '.done', (string) $count);

            $duration = microtime(true) - $startTime;
            \Log::info('GenerateDailyOperationExportJob completed successfully', [
                'token' => $this->token,
                'duration_seconds' => $duration,
                'count' => $count
            ]);
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;
            \Log::error('GenerateDailyOperationExportJob failed', [
                'token' => $this->token,
                'duration_seconds' => $duration,
                'error' => $e->getMessage()
            ]);
            @file_put_contents($path . '.error', $e->getMessage());
            throw $e;
        }
    }

    private function calculateHours($from, $to): string
    {
        if (!$from || !$to) {
            return '';
        }
        try {
            $mins = Carbon::parse($from)->diffInMinutes(Carbon::parse($to));
            return sprintf('%02d Hours, %02d Minutes', intdiv($mins, 60), $mins % 60);
        } catch (\Throwable $e) {
            return '';
        }
    }
}
