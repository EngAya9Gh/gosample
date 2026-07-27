<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduledTask extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'scheduled_tasks';

    public const TASK_TYPE_SELECT = [
        'SAMPLE' => 'SAMPLE',
        'BOX'    => 'BOX',
    ];

    // protected $casts = [
    //     'days' => 'array'
    // ];

    public const STATUS_SELECT = [
        'enabled'  => 'enabled',
        'disabled' => 'disabled',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'name',
        'status',
        'start_date',
        'selected_hour',
        'day',
        'end_date',
        'driver_id',
        'from_location_id',
        'to_location_id',
        'client_id',
        'task_type',
        'added_by',
        'status',
        'execution_status',
        'executed_at',
        'last_checked_at',
        'last_generated_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'executed_at'       => 'datetime',
        'last_checked_at'   => 'datetime',
        'last_generated_at' => 'datetime',
    ];

    /**
     * Fields that describe the schedule as a whole rather than a single
     * occurrence. Editing any of these on one row must apply to the entire
     * family.
     *
     * Deliberately excluded - these vary legitimately between rows of the same
     * family and are written per-occurrence only:
     *   day, selected_hour  - one row per weekday, quick-create adds one per hour
     *   from_location_id    - one row per pickup location in a multi-location schedule
     */
    public const SHARED_FIELDS = [
        'name',
        'status',
        'start_date',
        'end_date',
        'driver_id',
        'client_id',
        'to_location_id',
        'task_type',
    ];

    /** Written on a single row, never propagated across the family. */
    public const OCCURRENCE_FIELDS = [
        'day',
        'selected_hour',
        'from_location_id',
    ];

    protected static function booted()
    {
        // A schedule is stored as one parent row plus one child row per
        // (from_location x weekday). Deleting the parent must take the whole
        // family with it, otherwise the children survive as invisible rows -
        // no list query can reach them - while the cron keeps generating tasks
        // from them. Handled here rather than in a controller so that every
        // code path, present and future, inherits the behaviour.
        static::deleting(function (self $scheduledTask) {
            if (! is_null($scheduledTask->parent_id)) {
                return; // deleting a single occurrence is legitimate
            }

            $children = static::where('parent_id', $scheduledTask->id)->get();

            foreach ($children as $child) {
                $scheduledTask->isForceDeleting() ? $child->forceDelete() : $child->delete();
            }
        });
    }


    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function from_location()
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function to_location()
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function children()
    {
        return $this->hasMany(ScheduledTask::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(ScheduledTask::class, 'parent_id');
    }

    public function generatedTasks()
    {
        return $this->hasMany(Task::class, 'scheduled_task_id');
    }

    /** The id of the row that owns this family (the parent). */
    public function familyRootId(): int
    {
        return $this->parent_id ?: $this->id;
    }

    /**
     * Every row belonging to the same schedule - the parent and all of its
     * children - regardless of which row we were handed.
     */
    public function familyQuery()
    {
        $rootId = $this->familyRootId();

        return static::where('id', $rootId)->orWhere('parent_id', $rootId);
    }
}
