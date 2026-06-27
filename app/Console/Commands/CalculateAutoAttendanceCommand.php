<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use App\Models\Attendance;

/**
 * Derives driver attendance automatically from task activity.
 *
 * Rules (as specified by the business):
 *   - check-in  = the collection_date of the FIRST task collected that day
 *   - check-out = the close_date (drop-off) of the LAST task, finalized only
 *                 once IDLE minutes (default 90) have passed after that drop-off
 *                 WITHOUT the driver collecting or closing anything newer.
 *
 * The 90-minute window is ONLY a "is the work day finished?" check — it is never
 * added to any stored time. check-out is always stored as the actual drop-off
 * (close_date), so worked time = close_date - collection_date, with nothing inflated.
 *
 * This command writes ONLY checkin_time / checkout_time / source. The KPI fields
 * (delay_minutes, is_late, overtime_minutes, early_leave_minutes, total_worked_minutes)
 * are intentionally kept at 0 for now — the business logic for delay/overtime is still
 * being decided and will be implemented separately. The command never modifies task
 * records or the driver collection/close API flow.
 */
class CalculateAutoAttendanceCommand extends Command
{
    protected $signature = 'attendance:calc-auto
                            {--days=2 : How many days back to (re)evaluate}
                            {--idle=90 : Minutes of inactivity after the last drop-off before check-out is finalized}';

    protected $description = 'Auto-calculate driver check-in/check-out from task collection & drop-off activity';

    public function handle()
    {
        $days = max(1, (int) $this->option('days'));
        $idleMinutes = max(1, (int) $this->option('idle'));
        $lookbackStart = Carbon::now()->subDays($days)->startOfDay();

        // One aggregate row per (driver, work-day). The work-day is bucketed by the
        // DATE of collection_date so a task collected at 23:50 and closed after
        // midnight still belongs to the day it was collected.
        $query = DB::table('tasks')
            ->selectRaw('driver_id,
                         DATE(collection_date) as work_date,
                         MIN(collection_date)  as first_collection,
                         MAX(collection_date)  as last_collection,
                         MAX(close_date)       as last_close')
            ->whereNotNull('driver_id')
            ->whereNotNull('collection_date')
            ->where('collection_date', '>=', $lookbackStart);

        // tasks.deleted_at exists where the Task model uses SoftDeletes, but it was
        // dropped on some branches. Only filter by it when the column is present.
        // if (Schema::hasColumn('tasks', 'deleted_at')) {
        //     $query->whereNull('deleted_at');
        // }

        $groups = $query
            ->groupBy('driver_id', DB::raw('DATE(collection_date)'))
            ->get();

        // [OPTIMIZATION]: Pre-fetch all existing attendances to eliminate the N+1 query problem inside the loop
        $driverIds = $groups->pluck('driver_id')->unique()->toArray();
        $existingAttendances = Attendance::whereIn('driver_id', $driverIds)
            ->where('created_at', '>=', $lookbackStart)
            ->get()
            ->keyBy(function ($item) {
                return $item->driver_id . '_' . $item->created_at->format('Y-m-d');
            });

        $created = 0;
        $updated = 0;
        $finalized = 0;
        $skipped = 0;

        foreach ($groups as $g) {
            try {
                $checkin = Carbon::parse($g->first_collection);
                $lastCollection = Carbon::parse($g->last_collection);
                $lastClose = $g->last_close ? Carbon::parse($g->last_close) : null;

                // Finalize check-out only when: there is a drop-off, no collection
                // happened after it (driver didn't start a new task), and `idle`
                // minutes have elapsed since that drop-off.
                $checkout = null;
                if ($lastClose !== null
                    && $lastCollection->lessThanOrEqualTo($lastClose)
                    && $lastClose->lessThanOrEqualTo(Carbon::now())
                    && $lastClose->diffInMinutes(Carbon::now()) >= $idleMinutes) {
                    $checkout = $lastClose;
                }

                $cacheKey = $g->driver_id . '_' . $g->work_date;
                $existing = $existingAttendances->get($cacheKey);

                // Never overwrite explicit driver-app / admin records.
                if ($existing && in_array($existing->source, ['app', 'manual'], true)) {
                    $skipped++;
                    continue;
                }

                // Only check-in / check-out / source. KPI fields kept at 0 until the
                // delay/overtime business logic is finalized.
                $payload = [
                    'checkin_time'         => $checkin,
                    'checkout_time'        => $checkout,
                    'source'               => 'auto',
                    'delay_minutes'        => 0,
                    'is_late'              => false,
                    'overtime_minutes'     => 0,
                    'early_leave_minutes'  => 0,
                    'total_worked_minutes' => 0,
                ];

                if ($existing) {
                    $existing->fill($payload)->save();
                    $updated++;
                } else {
                    Attendance::create($payload + [
                        'driver_id'  => $g->driver_id,
                        'created_at' => $checkin, // keeps DATE(created_at) == work_date
                        'updated_at' => Carbon::now(),
                    ]);
                    $created++;
                }
                if ($checkout) { $finalized++; }
            } catch (\Throwable $e) {
                Log::error('attendance:calc-auto failed for driver ' . ($g->driver_id ?? '?')
                    . ' day ' . ($g->work_date ?? '?') . ': ' . $e->getMessage());
                $this->error("Skipped driver {$g->driver_id} / {$g->work_date}: {$e->getMessage()}");
            }
        }

        $this->info("Auto-attendance done. created={$created} updated={$updated} finalized_checkout={$finalized} skipped_explicit={$skipped}");
        return self::SUCCESS;
    }
}
