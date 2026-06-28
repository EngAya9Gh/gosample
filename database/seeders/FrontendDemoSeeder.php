<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * FrontendDemoSeeder — safe, re-runnable demo data for testing the migrated SPA
 * pages locally (dashboards / tasks). Additive & tagged (added_by='demo-seeder',
 * barcodes 'DEMO-…') so it can be re-run and cleaned without touching real data.
 *
 * Run:  php artisan db:seed --class=FrontendDemoSeeder
 *
 * It also inserts an `afaqi` token row so the Car Dashboard renders locally instead
 * of crashing (the controller reads $lastRecord->token; an empty table → null crash).
 * NOTE: real car temperature data still comes from the external Afaqy API, which
 * won't return data locally — the page will render but show no live sensors.
 */
class FrontendDemoSeeder extends Seeder
{
    public function run(): void
    {
        // ---- 0. Afaqy token so the Car Dashboard doesn't crash locally ----
        if (DB::table('afaqi')->count() === 0) {
            DB::table('afaqi')->insert([
                'token'      => 'local-demo-token',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info('Inserted a placeholder afaqi token (car dashboard will render locally).');
        }

        $clients   = DB::table('clients')->pluck('id')->all();
        $drivers   = DB::table('drivers')->pluck('id')->all();
        $locations = DB::table('locations')->pluck('id')->all();

        if (empty($clients) || empty($drivers) || count($locations) < 2) {
            $this->command->warn('Need clients, drivers and ≥2 locations first — skipping task/sample demo data.');
            return;
        }

        // ---- 1. Clean previous demo rows (idempotent) ----
        DB::table('samples')->where('bag_code', 'like', 'DEMO-%')->delete();
        DB::table('tasks')->where('added_by', 'demo-seeder')->delete();

        // Clone an existing task row as a template so every NOT-NULL column is satisfied.
        $template = (array) (DB::table('tasks')->whereNotNull('collection_date')->first()
            ?? DB::table('tasks')->first());
        if (!$template) {
            $this->command->warn('No existing task to use as a template — skipping.');
            return;
        }
        unset($template['id']);

        $pick = fn ($a) => $a[array_rand($a)];
        $types = ['Pickup', 'Delivery', 'Round-trip'];
        $rows = [];

        $make = function (array $o) use ($template, $clients, $drivers, $locations, $pick, $types) {
            return array_merge($template, [
                'billing_client' => $pick($clients),
                'driver_id'      => $pick($drivers),
                'from_location'  => $pick($locations),
                'to_location'    => $pick($locations),
                'task_type'      => $pick($types),
                'added_by'       => 'demo-seeder',
                'eta'            => rand(10, 50),
            ], $o);
        };

        // ---- 2. Monthly trend (last 12 months) for the Task Activity chart ----
        for ($i = 0; $i < 12; $i++) {
            $month = now()->copy()->subMonths($i)->startOfMonth();
            foreach (range(1, rand(4, 11)) as $j) {
                $d = $month->copy()->addDays(rand(0, 26))->setTime(rand(8, 17), rand(0, 59));
                $rows[] = $make([
                    'status'           => 'CLOSED',
                    'created_at'       => $d, 'updated_at' => $d,
                    'collection_date'  => $d->copy()->addMinutes(30),
                    'freezer_date'     => $d->copy()->addMinutes(60),
                    'freezer_out_date' => $d->copy()->addMinutes(90),
                    'close_date'       => $d->copy()->addMinutes(120),
                    'pickup_time'      => $d->copy()->addMinutes(25),
                    'dropoff_time'     => $d->copy()->addMinutes(115),
                ]);
            }
        }

        // ---- 3. Delayed sets (recent, within the 4-day window) ----
        // pickup delayed: pickup_time < collection_date
        foreach (range(1, 6) as $k) {
            $c = now()->copy()->subDays(rand(0, 3))->setTime(rand(8, 15), rand(0, 59));
            $rows[] = $make([
                'status' => 'NEW', 'created_at' => $c, 'updated_at' => $c,
                'pickup_time' => $c->copy()->addMinutes(20), 'collection_date' => $c->copy()->addMinutes(60),
            ]);
        }
        // drop-off delayed: dropoff_time < close_date
        foreach (range(1, 4) as $k) {
            $c = now()->copy()->subDays(rand(0, 3))->setTime(rand(8, 15), rand(0, 59));
            $rows[] = $make([
                'status' => 'CLOSED', 'created_at' => $c, 'updated_at' => $c,
                'collection_date' => $c->copy()->addMinutes(30),
                'dropoff_time' => $c->copy()->addMinutes(90), 'close_date' => $c->copy()->addMinutes(150),
            ]);
        }
        // collected delayed: status COLLECTED, collection_date > 15 min ago
        foreach (range(1, 5) as $k) {
            $c = now()->copy()->subDays(rand(0, 2))->setTime(rand(8, 18), rand(0, 59));
            $rows[] = $make([
                'status' => 'COLLECTED', 'created_at' => $c, 'updated_at' => $c,
                'collection_date' => now()->copy()->subMinutes(rand(20, 240)),
            ]);
        }
        // delivered delayed: status OUT_FREEZER, freezer_out_date > 15 min ago
        foreach (range(1, 3) as $k) {
            $c = now()->copy()->subDays(rand(0, 2))->setTime(rand(8, 18), rand(0, 59));
            $rows[] = $make([
                'status' => 'OUT_FREEZER', 'created_at' => $c, 'updated_at' => $c,
                'freezer_out_date' => now()->copy()->subMinutes(rand(20, 240)),
            ]);
        }

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('tasks')->insert($chunk);
        }
        $this->command->info('Inserted ' . count($rows) . ' demo tasks.');

        // ---- 4. Samples (temperature mix for the donut + some LOST) ----
        $taskIds = DB::table('tasks')->where('added_by', 'demo-seeder')->pluck('id')->all();
        $sTemplate = (array) DB::table('samples')->first();
        $temps = ['Frozen', 'Ambient', 'Cold'];
        $sampleRows = [];
        $n = 0;
        foreach (array_slice($taskIds, 0, 70) as $tid) {
            foreach (range(1, rand(1, 3)) as $s) {
                $n++;
                $base = $sTemplate ? array_diff_key($sTemplate, ['id' => 1]) : [];
                $sampleRows[] = array_merge($base, [
                    'task_id'             => $tid,
                    'location_id'         => $pick($locations),
                    'barcode_id'          => 'DEMO-' . $tid . '-' . $s,
                    'bag_code'            => 'DEMO-BG-' . $tid . $s,
                    'temperature_type'    => $pick($temps),
                    'sample_count'        => rand(1, 5),
                    'box_count'           => rand(1, 3),
                    'confirmed_by_client' => (rand(0, 8) === 0) ? 'LOST' : null,
                    'confirmed_by'        => (rand(0, 8) === 0) ? 'Demo Reviewer' : null,
                    'created_at'          => now()->copy()->subDays(rand(0, 60)),
                    'updated_at'          => now(),
                ]);
            }
        }
        foreach (array_chunk($sampleRows, 100) as $chunk) {
            DB::table('samples')->insert($chunk);
        }
        $this->command->info('Inserted ' . count($sampleRows) . ' demo samples (mixed temperatures, some LOST).');
        $this->command->info('Demo data ready. Clean later: tasks.added_by="demo-seeder" + samples bag_code LIKE "DEMO-%".');
    }
}
