<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

/**
 * Generates the task PDF report in the background.
 *
 * Why queued:
 *   The synchronous version at TasksController@export was timing out at
 *   Cloudflare (504) for large datasets — dompdf rendering + GROUP_CONCAT
 *   query are heavy. Mirrors the pattern already used by the green "Export
 *   Excel Report" button (see GenerateTaskExportJob).
 *
 * Scope:
 *   PDF only. The Excel path is handled by GenerateTaskExportJob (the green
 *   button — TasksController@exportExcelDetails).
 *
 * Output (matches GenerateTaskExportJob layout so the polling view works
 * for both):
 *   - File written to storage/app/exports/{token}.pdf
 *   - Sibling .done marker on success
 *   - Sibling .error file with the message on failure
 */
class GenerateTaskReportJob implements ShouldQueue
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
        \Log::info('GenerateTaskReportJob (PDF) started', [
            'token' => $this->token,
            'user_id' => $this->userId,
            'filters' => $this->filters
        ]);

        DB::disableQueryLog();
        @set_time_limit(0);
        @ini_set('memory_limit', '9000M');
        // mPDF parses HTML internally via PCRE. PHP's default
        // pcre.backtrack_limit (1,000,000) is too small for our large report
        // HTML and trips "The HTML code size is larger than pcre.backtrack_limit".
        // Raise both backtrack + recursion to handle multi-megabyte report HTML.
        @ini_set('pcre.backtrack_limit', '100000000');
        @ini_set('pcre.recursion_limit', '100000000');

        $dir = storage_path('app/exports');
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        $path = $dir . DIRECTORY_SEPARATOR . $this->token . '.pdf';

        try {
            $count = $this->generatePdf($path);
            @file_put_contents($path . '.done', (string) $count);

            $duration = microtime(true) - $startTime;
            \Log::info('GenerateTaskReportJob (PDF) completed successfully', [
                'token' => $this->token,
                'duration_seconds' => $duration,
                'count' => $count
            ]);
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;
            \Log::error('GenerateTaskReportJob (PDF) failed', [
                'token' => $this->token,
                'duration_seconds' => $duration,
                'error' => $e->getMessage()
            ]);
            @file_put_contents($path . '.error', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Port of TasksController@export's synchronous PDF path.
     * Builds the same raw SQL, processes rows the same way, renders the
     * report_template view, and writes a dompdf-rendered PDF to disk.
     */
    private function generatePdf(string $path): int
    {
        $f = $this->filters;

        $billing_client = $f['billing_client'] ?? null;
        $client_logo    = null;
        if ($billing_client) {
            $client_logo = optional(Client::find($billing_client))->logo;
        }

        $keyWord        = '';
        $from           = $f['date_from'] ?? null;
        $to             = $f['date_to']   ?? null;
        $date_column    = $f['search_date'] ?? 'tasks.created_at';
        $from_location  = $f['from_location'] ?? null;
        $to_location    = $f['to_location']   ?? null;
        $driver_id      = $f['driver_id']     ?? null;
        $status         = $f['status']        ?? null;

        $reportDate = ($from === $to) ? $to : ('From ' . $from . '- To ' . $to);

        // SQL query — copied verbatim from TasksController@export (line ~2494)
        $query = 'select  tasks.id as id  ,  from_location.name as "from_organization_name" , tasks.close_date "close_task",  tasks.from_location_arrival_time as "from_location_arrival_time",
                                    TIMESTAMPDIFF(Minute, tasks.from_location_arrival_time,  tasks.collection_date) as "from_stay_time",
                                    to_location.name as "to_organization_name",
                                    tasks.to_location_arrival_time as "to_location_arrival_time",
                                    /*TIMESTAMPDIFF(Minute, tasks.freezer_out_date, tasks.close_date ) as "to_stay_time",*/
                                      CASE
                                        WHEN tasks.is_swap = 1 THEN TIMESTAMPDIFF(Minute, tasks.swap_freezer_out, tasks.close_date)
                                        ELSE TIMESTAMPDIFF(Minute, tasks.freezer_out_date, tasks.close_date)
                                    END AS "to_stay_time",
                                    TIMESTAMPDIFF(Minute,  tasks.from_location_arrival_time, tasks.close_date) as "trip_duration",
                                    GROUP_CONCAT(samples.bag_code) as "bag_code",
                                    GROUP_CONCAT(samples.temperature_type) as "temperature_type",
                                    count(samples.id) as "bags_count",
                                    tasks.confirmed_by_client,
                                    tasks.confirmation_time
                                    from tasks
                                    left join drivers on drivers.ID = tasks.driver_id
                                    left join locations as from_location on from_location.ID = tasks.from_location
                                    left join locations as to_location on to_location.ID = tasks.to_location
                                    left join samples as samples on samples.task_id = tasks.id
                                    WHERE tasks.deleted_at is null and tasks.id > 1 and drivers.status = 1';

        if ($billing_client) { $query .= ' and tasks.billing_client= ' . (int) $billing_client; }
        if ($from_location)  { $query .= ' and tasks.from_location= '  . (int) $from_location; }
        if ($to_location)    { $query .= ' and tasks.to_location= '    . (int) $to_location; }
        if ($driver_id)      { $query .= ' and tasks.driver_id= '      . (int) $driver_id; }
        if ($from && $to) {
            $query .= " and " . $date_column . " BETWEEN '" . date('Y-m-d H:i:s', strtotime($from)) . "' and '" . date('Y-m-d H:i:s', strtotime($to)) . " '";
        }
        if ($status) {
            $query .= " and tasks.status= '" . $status . "'";
        }

        $tasks = DB::select($query . ' group by tasks.id order by from_location.name asc, tasks.from_location_arrival_time asc LIMIT 1000;');

        $roomBags = 0;
        $refBags = 0;
        $frozenBags = 0;
        $roomSamples = 0;
        $refSamples = 0;
        $frozenSamples = 0;

        $summaryReport = '';
        if ($billing_client == 26) { // mdlab — special summary
            $summaryReport = collect($tasks)
                ->groupBy('from_organization_name')
                ->map(function ($task) {
                    return [
                        'trip_duration' => $task->sum('trip_duration'),
                        'count' => $task->count(),
                    ];
                });
        }

        foreach ($tasks as $task) {
            $task->from_stay_time = floor($task->from_stay_time / 60) . 'H:' . ($task->from_stay_time - floor($task->from_stay_time / 60) * 60) . 'M';
            $task->to_stay_time   = floor($task->to_stay_time / 60)   . 'H:' . ($task->to_stay_time   - floor($task->to_stay_time / 60) * 60)   . 'M';
            $task->trip_duration  = floor($task->trip_duration / 60)  . 'H:' . ($task->trip_duration  - floor($task->trip_duration / 60) * 60)  . 'M';
            if ($task->bag_code == null) {
                $task->bags = [];
            } else {
                $task->bags2 = array_count_values(explode(',', $task->bag_code));
                $task->bags  = array_unique(explode(',', $task->bag_code));
                $task->temperature_types2 = array_count_values(explode(',', $task->temperature_type));
                $task->temperature_types  = array_unique(explode(',', $task->temperature_type));
                $tempVar = json_decode(json_encode($task->temperature_types2), true);
                $bagVar  = json_decode(json_encode($task->bags2), true);
                $task->data = [];
                foreach ($tempVar as $key => $value) {
                    $temp = new Task();
                    $temp->temperature = $key;
                    $temp->count       = $value;
                    foreach ($bagVar as $key1 => $value1) {
                        if ($value == $value1) {
                            $temp->bag = $key1;
                            break;
                        }
                    }
                    $task->data[] = $temp;
                    switch ($key) {
                        case 'ROOM':
                            $temp->temperature_label = '+15C TO +25C';
                            $roomBags += 1;
                            $roomSamples += $value;
                            break;
                        case 'REFRIGERATE':
                            $temp->temperature_label = '+2C TO +8C';
                            $refSamples += $value;
                            $refBags += 1;
                            break;
                        case 'FROZEN':
                            $temp->temperature_label = '0C TO -18C';
                            $frozenSamples += $value;
                            $frozenBags += 1;
                            break;
                    }
                }
            }
        }

        $pickup_smaple    = $roomSamples + $refSamples + $frozenSamples;
        $pickup_container = $frozenBags + $refBags + $roomBags;
        $pick_sum_data    = [$pickup_container, $pickup_smaple];

        // served_orginization / visited_orginization — matches the original
        // controller's auxiliary query (uses ->whereBetween on $date_column).
        $condition = Task::where(function ($query) use ($keyWord) {
            $query->orWhere('from_location', 'LIKE', $keyWord)
                ->orWhereHas('driver', function ($q) use ($keyWord) { $q->where('name', 'LIKE', $keyWord); })
                ->orWhereHas('from',   function ($q) use ($keyWord) { $q->where('name', 'LIKE', $keyWord); })
                ->orWhereHas('to',     function ($q) use ($keyWord) { $q->where('name', 'LIKE', $keyWord); })
                ->orWhereHas('client', function ($q) use ($keyWord) { $q->where('english_name', 'LIKE', $keyWord); })
                ->orWhere('type', 'LIKE', $keyWord);
        })
            ->when($status,         function ($q) use ($status)         { $q->where('tasks.status', $status); })
            ->when($from_location,  function ($q) use ($from_location)  { $q->where('from_location', $from_location); })
            ->when($to_location,    function ($q) use ($to_location)    { $q->where('to_location', $to_location); })
            ->when($billing_client, function ($q) use ($billing_client) { $q->where('billing_client', $billing_client); })
            ->when($driver_id,      function ($q) use ($driver_id)      { $q->where('driver_id', $driver_id); })
            ->whereBetween($date_column, [date('Y-m-d H:i:s', strtotime($from)), date('Y-m-d H:i:s', strtotime($to))]);

        $served_orginization  = $condition->whereIn('tasks.status', ['CLOSED', 'NO_SAMPLES'])->distinct('from_location')->count('from_location');
        $visited_orginization = $condition->where('status', 'NO_SAMPLES')->count();

        $summary = Task::with(['client' => function ($q) { $q->select('id', 'english_name'); }])
            ->when($status,         function ($q) use ($status)         { $q->where('status', $status); })
            ->when($from_location,  function ($q) use ($from_location)  { $q->where('from_location', $from_location); })
            ->when($to_location,    function ($q) use ($to_location)    { $q->where('to_location', $to_location); })
            ->when($billing_client, function ($q) use ($billing_client) { $q->where('billing_client', $billing_client); })
            ->when($driver_id,      function ($q) use ($driver_id)      { $q->where('driver_id', $driver_id); })
            ->whereDate('created_at', date('Y-m-d'))
            ->select('status', 'billing_client', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        $html = View::make('report_template', [
            'tasks'                => $tasks,
            'summary'              => $summary,
            'pick_sum_data'        => $pick_sum_data,
            'client_logo'          => $client_logo,
            'billing_client'       => $billing_client,
            'reportDate'           => $reportDate,
            'summaryReport'        => $summaryReport,
            'frozenSamples'        => $frozenSamples,
            'visited_orginization' => $visited_orginization,
            'served_orginization'  => $served_orginization,
            'frozenBags'           => $frozenBags,
            'roomBags'             => $roomBags,
            'refBags'              => $refBags,
            'roomSamples'          => $roomSamples,
            'refSamples'           => $refSamples,
        ])->render();

        // Render the PDF with mPDF (replaces dompdf — same HTML input, but
        // ~2–3× faster on large tables and significantly better Arabic/RTL
        // support for the bilingual client report).
        $tempDir = storage_path('app/mpdf_tmp');
        if (!is_dir($tempDir)) { @mkdir($tempDir, 0775, true); }

        $mpdf = new \Mpdf\Mpdf([
            'mode'    => 'utf-8',
            'format'  => 'A3-L',
            'tempDir' => $tempDir,
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output($path, \Mpdf\Output\Destination::FILE);

        return is_array($tasks) ? count($tasks) : 0;
    }
}
