<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \DateTimeInterface;

class Swap extends Model
{
    use HasFactory;

    public const STATUS_SELECT = [
        'new'      => 'New',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
    ];


    public $table = 'swap_requests';

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'task_id',
        'driver_id',
        'accepted_by_receiver',
        'driver_a',
        'status',
        'created_at',
        'updated_at',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
    public function driverA()
    {
        return $this->belongsTo(Driver::class, 'driver_a');
    }

    /**
     * Push a swap notification to one side of the swaps, one push per pair of drivers
     * (texts in resources/lang/{ar,en}/push.php)
     *
     * @param iterable $swaps
     * @param string $key push.php key
     * @param string $to 'driver_a' (driver handing over) or 'driver_id' (driver receiving)
     */
    public static function notifyDrivers($swaps, $key, $to)
    {
        $other = $to === 'driver_a' ? 'driver_id' : 'driver_a';

        collect($swaps)->groupBy(fn ($swap) => $swap->$to . '-' . $swap->$other)
            ->each(function ($group) use ($key, $to, $other) {
                $recipient = Driver::find($group->first()->$to);
                $otherDriver = Driver::find($group->first()->$other);
                if (!$recipient || !$otherDriver) {
                    return;
                }

                // One task can be opened directly, several go to the swap list
                $task = $group->count() === 1 ? Task::find($group->first()->task_id) : null;

                $recipient->pushNotification($key, $task, 'no_action', [
                    'driver' => $otherDriver->name,
                    'count' => $group->count(),
                ]);
            });
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
