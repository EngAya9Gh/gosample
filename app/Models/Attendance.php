<?php

namespace App\Models;

use \DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use SoftDeletes;
    use HasFactory;

    public $table = 'attendances';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'driver_id',
        'shift_id',
        'checkin_time',
        'checkout_time',
        'expected_start',
        'expected_end',
        'delay_minutes',
        'is_late',
        'early_leave_minutes',
        'overtime_minutes',
        'total_worked_minutes',
        'alert_sent',
        'source',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function shift()
    {
        return $this->belongsTo(DriverShift::class, 'shift_id');
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
