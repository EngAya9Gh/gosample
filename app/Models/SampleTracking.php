<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SampleTracking extends Model
{
    use HasFactory;

    public $table = 'sample_tracking';
    public $timestamps = false;
    protected $fillable = [
        'sample_id',
        'order_id',
        'profile_id',
        'hospital_id',
        'hospital_name',
        'collection_hospital_id',
        'collection_hospital_name',
        'create_date',
    ];
}
