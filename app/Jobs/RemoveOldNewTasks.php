<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Task;
use Carbon\Carbon;

/**
 * Hides yesterday's untouched NEW tasks so they stop cluttering task lists.
 *
 * "Untouched" is the important word. Setting is_unused = 1 removes a task from
 * EVERY query in the application, because of the global scope on the Task
 * model - admin search included - and there is no UI to see or undo it. So the
 * test for whether a task is really unused has to be right.
 *
 * status = 'NEW' on its own is not that test. A driver scans barcodes into a
 * task while it is still NEW, so a NEW task can hold real collected work. One
 * production task was hidden this way while holding 75 samples: the samples
 * stayed visible in the samples list while every column drawn from the task
 * rendered blank, because the relation resolved to null.
 *
 * A task with samples attached is therefore never hidden.
 */
class RemoveOldNewTasks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // A single bulk update rather than loading every match and saving it
        // one row at a time. Nothing observes this change: the model's saved()
        // hook only reacts to a status change, and auditing here covers deletes
        // only. The global scope keeps already-hidden rows out of the query.
        Task::where('status', 'NEW')
            ->where('created_at', '<', Carbon::today())
            ->whereDoesntHave('samples')
            ->update(['is_unused' => 1]);
    }
}
