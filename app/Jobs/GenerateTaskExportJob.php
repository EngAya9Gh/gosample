<?php

namespace App\Jobs;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Generates the task time-report XLSX file in the background.
 *
 * Why queued:
 *   - For large datasets (50K+ rows) the export takes >100s, which trips Cloudflare's
 *     gateway timeout. By running in a worker, the HTTP request returns instantly with
 *     a "preparing your export" page that polls until the file is ready.
 *
 * Output:
 *   - File written to storage/app/exports/{token}.xlsx
 *   - A sibling .done marker file is created on success
 *   - A sibling .error file with the message is created on failure
 */
class GenerateTaskExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** No retry on failure — the user will see the error and can retry from the UI. */
    public $tries = 1;

    /** Allow the worker up to 30 minutes. */
    public $timeout = 1800;

    public function __construct(
        public string $token,
        public array $filters,
        public ?int $userId = null,
    ) {}

    public function handle(): void
    {
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
            ]);

            if (!empty($f['date_from']) && !empty($f['date_to'])) {
                $query->whereBetween('collection_date', [$f['date_from'], $f['date_to']]);
            } elseif (!empty($f['date_from'])) {
                $query->where('collection_date', '>=', $f['date_from']);
            } elseif (!empty($f['date_to'])) {
                $query->where('collection_date', '<=', $f['date_to']);
            }
            if (!empty($f['status']))         { $query->where('status', $f['status']); }
            if (!empty($f['billing_client'])) { $query->where('billing_client', $f['billing_client']); }
            if (!empty($f['from_location'])) { $query->where('from_location', $f['from_location']); }
            if (!empty($f['to_location']))   { $query->where('to_location', $f['to_location']); }
            if (!empty($f['driver_id']))     { $query->where('driver_id', $f['driver_id']); }

            $writer = new \OpenSpout\Writer\XLSX\Writer();
            $writer->openToFile($path);

            $headerStyle = (new \OpenSpout\Common\Entity\Style\Style())
                ->setFontBold()->setFontSize(11);

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                'Task ID', 'Created Date', 'Reach Time', 'Started Time',
                'Collected Date', 'Collected Time', 'Close Date', 'Close Time',
                'Driver', 'Client', 'From Location', 'To Location',
            ], $headerStyle));

            $count = 0;
            $query->orderBy('tasks.id')->chunkById(500, function ($tasks) use ($writer, &$count) {
                foreach ($tasks as $t) {
                    $collected = $t->collection_date ? Carbon::parse($t->collection_date) : null;
                    $closed    = $t->close_date      ? Carbon::parse($t->close_date)      : null;
                    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                        (string) $t->id,
                        (string) $t->created_at,
                        (string) ($t->from_location_confirmation_timestamp ?? ''),
                        (string) ($t->driver_start_date ?? ''),
                        $collected ? $collected->format('Y-m-d') : '',
                        $collected ? $collected->format('H:i:s') : '',
                        $closed    ? $closed->format('Y-m-d')    : '',
                        $closed    ? $closed->format('H:i:s')    : '',
                        optional($t->driver)->name         ?: 'N/A',
                        optional($t->client)->english_name ?: 'N/A',
                        optional($t->from)->name           ?: 'N/A',
                        optional($t->to)->name             ?: 'N/A',
                    ]));
                    $count++;
                }
            });

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Number of tasks', $count]));
            $writer->close();

            // Mark complete
            @file_put_contents($path . '.done', (string) $count);
        } catch (\Throwable $e) {
            @file_put_contents($path . '.error', $e->getMessage());
            throw $e;
        }
    }
}
