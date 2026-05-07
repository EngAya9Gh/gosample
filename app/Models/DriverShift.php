<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'shift_number',
        'start_time',
        'end_time',
        'days',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected $casts = [
        'days' => 'array',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'shift_id');
    }
}
