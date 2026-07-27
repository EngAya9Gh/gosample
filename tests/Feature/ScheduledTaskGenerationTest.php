<?php

namespace Tests\Feature;

use App\Jobs\CheckScheduledTasks;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Location;
use App\Models\ScheduledTask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Covers the two production defects reported by the admin:
 *
 *   1. Deleting a schedule only removed the parent row. Its children stayed
 *      alive, unreachable from every screen, and kept generating tasks.
 *   2. The duplicate guard matched on driver_id and pickup_time - the two
 *      fields dispatchers edit - so any edit or deletion produced a duplicate
 *      on the next cron tick.
 *
 * Runs against the configured database inside a transaction, so nothing is
 * left behind.
 */
class ScheduledTaskGenerationTest extends TestCase
{
    use DatabaseTransactions;

    private Driver $driverA;
    private Driver $driverB;
    private Client $client;
    private $locations;
    private User $admin;
    private string $today;

    protected function setUp(): void
    {
        parent::setUp();

        // Authorisation is guard-name based in this app and is not what these
        // tests are about; the scheduling logic is.
        Gate::before(fn () => true);

        $this->admin     = User::firstOrFail();
        $this->driverA   = Driver::orderBy('id')->firstOrFail();
        $this->driverB   = Driver::orderBy('id')->skip(1)->firstOrFail();
        $this->client    = Client::orderBy('id')->firstOrFail();
        $this->locations = Location::orderBy('id')->take(4)->get();
        $this->today     = now()->format('l');

        $this->assertGreaterThanOrEqual(4, $this->locations->count(), 'need 4 locations as fixtures');
    }

    /**
     * Builds a schedule the same way Admin/App ScheduledTasksController@store
     * does: one row per (from_location x weekday), linked by parent_id.
     */
    private function makeSchedule(array $locationIds, array $days, string $hour = '00:00', ?int $driverId = null): ScheduledTask
    {
        $parentId = null;

        foreach ($locationIds as $locationId) {
            foreach ($days as $day) {
                $row = new ScheduledTask([
                    'name'           => 'TEST SCHEDULE',
                    'status'         => 'enabled',
                    'start_date'     => now()->subMonth()->toDateString(),
                    'end_date'       => now()->addMonth()->toDateString(),
                    'driver_id'      => $driverId ?? $this->driverA->id,
                    'client_id'      => $this->client->id,
                    'to_location_id' => $this->locations->last()->id,
                    'task_type'      => 'SAMPLE',
                ]);
                $row->parent_id        = $parentId;
                $row->from_location_id = $locationId;
                $row->day              = $day;
                $row->selected_hour    = $hour;
                $row->save();

                $parentId ??= $row->id;
            }
        }

        return ScheduledTask::findOrFail($parentId);
    }

    private function runCron(): void
    {
        (new CheckScheduledTasks)->handle();
    }

    /**
     * Today's weekday plus $extra other weekdays, never repeating one - the test
     * must behave the same whichever day of the week it runs on.
     */
    private function daysIncludingToday(int $extra): array
    {
        $all = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $others = array_values(array_diff($all, [$this->today]));

        return array_merge([$this->today], array_slice($others, 0, $extra));
    }

    private function familyIds(ScheduledTask $schedule)
    {
        return $schedule->familyQuery()->pluck('id');
    }

    private function generatedCount(ScheduledTask $schedule): int
    {
        return Task::withoutGlobalScopes()
            ->whereIn('scheduled_task_id', $this->familyIds($schedule))
            ->count();
    }

    // ---------------------------------------------------------------- dedupe

    public function test_repeated_cron_ticks_generate_each_occurrence_once(): void
    {
        // 3 pickup locations on today's weekday - the shape of the schedule in
        // the admin's screenshot.
        $schedule = $this->makeSchedule($this->locations->take(3)->pluck('id')->all(), [$this->today]);

        $this->runCron();
        $this->assertSame(3, $this->generatedCount($schedule), 'one task per pickup location');

        $this->runCron();
        $this->runCron();
        $this->runCron();
        $this->assertSame(3, $this->generatedCount($schedule), 'further ticks must not add tasks');
    }

    public function test_reassigning_the_driver_does_not_regenerate(): void
    {
        $schedule = $this->makeSchedule([$this->locations[0]->id], [$this->today]);
        $this->runCron();

        Task::withoutGlobalScopes()
            ->whereIn('scheduled_task_id', $this->familyIds($schedule))
            ->first()
            ->update(['driver_id' => $this->driverB->id]);

        $this->runCron();

        $this->assertSame(1, $this->generatedCount($schedule));
        $this->assertSame(
            0,
            Task::withoutGlobalScopes()
                ->whereIn('scheduled_task_id', $this->familyIds($schedule))
                ->where('driver_id', $this->driverA->id)
                ->count(),
            'the original driver must not get the task back'
        );
    }

    public function test_changing_the_pickup_time_does_not_regenerate(): void
    {
        $schedule = $this->makeSchedule([$this->locations[0]->id], [$this->today]);
        $this->runCron();

        Task::withoutGlobalScopes()
            ->whereIn('scheduled_task_id', $this->familyIds($schedule))
            ->first()
            ->update(['pickup_time' => now()->setTime(3, 15)->format('Y-m-d H:i:s')]);

        $this->runCron();

        $this->assertSame(1, $this->generatedCount($schedule));
    }

    public function test_a_deleted_task_stays_deleted(): void
    {
        $schedule = $this->makeSchedule([$this->locations[0]->id], [$this->today]);
        $this->runCron();

        Task::withoutGlobalScopes()->whereIn('scheduled_task_id', $this->familyIds($schedule))->delete();
        $this->runCron();

        $this->assertSame(0, $this->generatedCount($schedule), 'a deleted task must not come back');
    }

    public function test_the_next_day_generates_again(): void
    {
        $schedule = $this->makeSchedule([$this->locations[0]->id], [$this->today]);
        $this->runCron();
        $this->assertSame(1, $this->generatedCount($schedule));

        // Simulate the clock rolling over to the next occurrence.
        DB::table('scheduled_tasks')
            ->whereIn('id', $this->familyIds($schedule))
            ->update(['last_generated_at' => now()->subDay()]);

        $this->runCron();
        $this->assertSame(2, $this->generatedCount($schedule), 'the schedule must still recur');
    }

    public function test_generated_tasks_are_linked_back_to_their_schedule(): void
    {
        $schedule = $this->makeSchedule([$this->locations[0]->id], [$this->today]);
        $this->runCron();

        $task = Task::withoutGlobalScopes()
            ->whereIn('scheduled_task_id', $this->familyIds($schedule))
            ->firstOrFail();

        $this->assertContains($task->scheduled_task_id, $this->familyIds($schedule)->all());
        $this->assertInstanceOf(ScheduledTask::class, $task->scheduledTask);
    }

    public function test_concurrent_ticks_cannot_both_generate(): void
    {
        $schedule = $this->makeSchedule([$this->locations[0]->id], [$this->today]);

        for ($i = 0; $i < 5; $i++) {
            $this->runCron();
        }

        $this->assertSame(1, $this->generatedCount($schedule));
    }

    // ---------------------------------------------------------------- delete

    public function test_deleting_a_schedule_removes_the_whole_family(): void
    {
        $schedule = $this->makeSchedule(
            $this->locations->take(2)->pluck('id')->all(),
            ['Monday', 'Tuesday', 'Wednesday']
        );
        $this->assertSame(6, $schedule->familyQuery()->count(), '2 locations x 3 days');

        $this->actingAs($this->admin)
            ->delete('/admin/scheduled-tasks/' . $schedule->id)
            ->assertOk();

        $this->assertSame(0, $schedule->familyQuery()->count(), 'no row may survive the delete');
    }

    public function test_deleting_the_schedule_from_a_child_row_also_removes_the_family(): void
    {
        $schedule = $this->makeSchedule([$this->locations[0]->id], ['Monday', 'Tuesday', 'Wednesday']);
        $child    = ScheduledTask::where('parent_id', $schedule->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->delete('/admin/scheduled-tasks/' . $child->id)
            ->assertOk();

        $this->assertSame(0, $schedule->familyQuery()->count());
    }

    public function test_deleting_a_single_occurrence_keeps_the_rest(): void
    {
        $schedule = $this->makeSchedule([$this->locations[0]->id], ['Monday', 'Tuesday', 'Wednesday']);
        $child    = ScheduledTask::where('parent_id', $schedule->id)->firstOrFail();

        // massDestroyChildren removes one weekday without touching the schedule.
        $child->delete();

        $this->assertSame(2, $schedule->familyQuery()->count());
    }

    public function test_orphaned_children_never_generate(): void
    {
        $schedule = $this->makeSchedule([$this->locations[0]->id], $this->daysIncludingToday(1));

        // Reproduce what the old buggy controller left in production: the parent
        // soft-deleted directly, children untouched.
        DB::table('scheduled_tasks')->where('id', $schedule->id)->update(['deleted_at' => now()]);
        $this->assertSame(1, ScheduledTask::where('parent_id', $schedule->id)->count());

        $this->runCron();

        $this->assertSame(0, $this->generatedCount($schedule), 'orphans must never produce tasks');
    }

    // ------------------------------------------------------------------ edit

    public function test_editing_propagates_to_the_family_but_preserves_days_and_locations(): void
    {
        $schedule = $this->makeSchedule(
            $this->locations->take(2)->pluck('id')->all(),
            ['Monday', 'Tuesday', 'Wednesday']
        );

        $this->actingAs($this->admin)
            ->putJson('/admin/scheduled-tasks/' . $schedule->id, [
                'id'        => $schedule->id,   // the SPA sends this; it must be ignored
                'status'    => 'enabled',
                'task_type' => 'SAMPLE',
                'driver_id' => $this->driverB->id,
            ])
            ->assertOk();

        $family = $schedule->familyQuery()->get();

        $this->assertSame(6, $family->count());
        $this->assertSame([$this->driverB->id], $family->pluck('driver_id')->unique()->values()->all(),
            'the new driver must reach every row');
        $this->assertCount(3, $family->pluck('day')->unique(),
            'weekdays must survive the edit');
        $this->assertCount(2, $family->pluck('from_location_id')->unique(),
            'pickup locations must survive the edit');
    }

    public function test_disabling_a_schedule_stops_every_occurrence(): void
    {
        $schedule = $this->makeSchedule($this->locations->take(2)->pluck('id')->all(), [$this->today]);

        $this->actingAs($this->admin)
            ->putJson('/admin/scheduled-tasks/' . $schedule->id, [
                'status'    => 'disabled',
                'task_type' => 'SAMPLE',
            ])
            ->assertOk();

        $this->runCron();

        $this->assertSame(0, $this->generatedCount($schedule), 'a disabled schedule must not generate');
    }

    // ------------------------------------------------- end-to-end regression

    public function test_the_reported_production_scenario(): void
    {
        // A driver with ONE schedule: 3 pickup points, today + two other days.
        $schedule = $this->makeSchedule(
            $this->locations->take(3)->pluck('id')->all(),
            $this->daysIncludingToday(2),
            '00:00'
        );

        // Day 1 - the cron runs all day.
        for ($i = 0; $i < 10; $i++) {
            $this->runCron();
        }
        $this->assertSame(3, $this->generatedCount($schedule), 'exactly one task per pickup point');

        // Dispatchers correct the day's work.
        $tasks = Task::withoutGlobalScopes()->whereIn('scheduled_task_id', $this->familyIds($schedule))->get();
        $tasks[0]->update(['driver_id' => $this->driverB->id]);
        $tasks[1]->update(['pickup_time' => now()->setTime(21, 0)->format('Y-m-d H:i:s')]);
        $tasks[2]->delete();

        for ($i = 0; $i < 10; $i++) {
            $this->runCron();
        }
        $this->assertSame(2, $this->generatedCount($schedule),
            'edits and a deletion must not resurrect anything');

        // The admin deletes the schedule.
        $this->actingAs($this->admin)
            ->delete('/admin/scheduled-tasks/' . $schedule->id)
            ->assertOk();
        $this->assertSame(0, $schedule->familyQuery()->count());

        // Following days: nothing may be generated ever again.
        DB::table('scheduled_tasks')
            ->whereIn('id', $this->familyIds($schedule))
            ->update(['last_generated_at' => now()->subDays(3)]);

        $before = Task::withoutGlobalScopes()->count();
        for ($i = 0; $i < 5; $i++) {
            $this->runCron();
        }
        $this->assertSame($before, Task::withoutGlobalScopes()->count(),
            'a deleted schedule must be completely inert');
    }
}
