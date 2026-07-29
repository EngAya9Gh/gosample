<?php

namespace Tests\Feature;

use App\Jobs\RemoveOldNewTasks;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Task;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Task 791314 held 75 collected samples and disappeared from the whole
 * application. It had not been deleted: RemoveOldNewTasks had flagged it
 * is_unused = 1 because it was still NEW the morning after it was created, and
 * the Task global scope hides such rows from every query.
 *
 * The flag means "unused", but the job inferred that from status alone. A
 * driver scans barcodes while a task is still NEW, so genuinely used tasks were
 * being hidden - samples still listed, every task-derived column blank.
 */
class HiddenTaskRecoveryTest extends TestCase
{
    use DatabaseTransactions;

    private function makeTask(string $status, int $daysAgo, int $samples = 0): int
    {
        $loc = Location::orderBy('id')->firstOrFail();

        $id = DB::table('tasks')->insertGetId([
            'from_location'  => $loc->id,
            'to_location'    => $loc->id,
            'billing_client' => Client::orderBy('id')->firstOrFail()->id,
            'driver_id'      => Driver::orderBy('id')->firstOrFail()->id,
            'task_type'      => 'SAMPLE',
            'status'         => $status,
            'is_unused'      => 0,
            'created_at'     => now()->subDays($daysAgo),
            'updated_at'     => now()->subDays($daysAgo),
        ]);

        for ($i = 0; $i < $samples; $i++) {
            DB::table('samples')->insert([
                'barcode_id'  => "HIDDEN-TEST-{$id}-{$i}",
                'task_id'     => $id,
                'location_id' => $loc->id,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        return $id;
    }

    private function isHidden(int $id): bool
    {
        return (int) DB::table('tasks')->where('id', $id)->value('is_unused') === 1;
    }

    public function test_a_leftover_task_holding_samples_is_never_hidden(): void
    {
        $withWork = $this->makeTask('NEW', 3, samples: 75);

        (new RemoveOldNewTasks)->handle();

        $this->assertFalse($this->isHidden($withWork), 'a task holding 75 samples must stay visible');
        $this->assertNotNull(Task::find($withWork), 'and must remain reachable through the app');
    }

    public function test_a_genuinely_empty_leftover_task_is_still_hidden(): void
    {
        $empty = $this->makeTask('NEW', 3, samples: 0);

        (new RemoveOldNewTasks)->handle();

        $this->assertTrue($this->isHidden($empty), 'decluttering still works for truly unused tasks');
        $this->assertNull(Task::find($empty), 'and it disappears from application queries');
    }

    public function test_todays_tasks_are_left_alone(): void
    {
        $today = $this->makeTask('NEW', 0, samples: 0);

        (new RemoveOldNewTasks)->handle();

        $this->assertFalse($this->isHidden($today), 'only leftovers from previous days are hidden');
    }

    public function test_non_new_tasks_are_left_alone(): void
    {
        $closed = $this->makeTask('CLOSED', 3, samples: 0);

        (new RemoveOldNewTasks)->handle();

        $this->assertFalse($this->isHidden($closed));
    }

    public function test_the_recovery_command_reports_without_writing(): void
    {
        $id = $this->makeTask('NEW', 3, samples: 5);
        DB::table('tasks')->where('id', $id)->update(['is_unused' => 1]);   // hidden by the old job

        $this->artisan('tasks:hidden')->assertSuccessful();

        $this->assertTrue($this->isHidden($id), 'a dry run must not change anything');
    }

    public function test_the_recovery_command_restores_tasks_holding_samples(): void
    {
        $withWork = $this->makeTask('NEW', 3, samples: 5);
        $empty    = $this->makeTask('NEW', 3, samples: 0);
        DB::table('tasks')->whereIn('id', [$withWork, $empty])->update(['is_unused' => 1]);

        $this->artisan('tasks:hidden', ['mode' => 'restore'])->assertSuccessful();

        $this->assertFalse($this->isHidden($withWork), 'work-bearing tasks come back');
        $this->assertTrue($this->isHidden($empty), 'genuinely empty ones stay hidden');
    }

    public function test_restored_tasks_keep_their_samples(): void
    {
        $id = $this->makeTask('NEW', 3, samples: 12);
        DB::table('tasks')->where('id', $id)->update(['is_unused' => 1]);

        $this->artisan('tasks:hidden', ['mode' => 'restore'])->assertSuccessful();

        $this->assertSame(12, DB::table('samples')->where('task_id', $id)->count());
        $this->assertSame(12, Task::find($id)->samples()->count());
    }
}
