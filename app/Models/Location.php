<?php

namespace App\Models;

use \DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;

class Location extends Model
{
    use SoftDeletes;
    use HasFactory;
    use Auditable;

    public const STATUS_SELECT = [
        '1' => 'active',
        '0' => 'inactive',
    ];

    public const SAUDI_CITIES = [
        'Riyadh'         => ['en' => 'Riyadh',         'ar' => 'الرياض'],
        'Jeddah'         => ['en' => 'Jeddah',         'ar' => 'جدة'],
        'Mecca'          => ['en' => 'Mecca',          'ar' => 'مكة المكرمة'],
        'Medina'         => ['en' => 'Medina',         'ar' => 'المدينة المنورة'],
        'Dammam'         => ['en' => 'Dammam',         'ar' => 'الدمام'],
        'Khobar'         => ['en' => 'Khobar',         'ar' => 'الخبر'],
        'Dhahran'        => ['en' => 'Dhahran',        'ar' => 'الظهران'],
        'Taif'           => ['en' => 'Taif',           'ar' => 'الطائف'],
        'Tabuk'          => ['en' => 'Tabuk',          'ar' => 'تبوك'],
        'Buraidah'       => ['en' => 'Buraidah',       'ar' => 'بريدة'],
        'Khamis Mushait' => ['en' => 'Khamis Mushait', 'ar' => 'خميس مشيط'],
        'Abha'           => ['en' => 'Abha',           'ar' => 'أبها'],
        'Hail'           => ['en' => 'Hail',           'ar' => 'حائل'],
        'Najran'         => ['en' => 'Najran',         'ar' => 'نجران'],
        'Jubail'         => ['en' => 'Jubail',         'ar' => 'الجبيل'],
        'Yanbu'          => ['en' => 'Yanbu',          'ar' => 'ينبع'],
        'Hofuf'          => ['en' => 'Hofuf',          'ar' => 'الهفوف'],
        'Qatif'          => ['en' => 'Qatif',          'ar' => 'القطيف'],
        'Arar'           => ['en' => 'Arar',           'ar' => 'عرعر'],
        'Sakaka'         => ['en' => 'Sakaka',         'ar' => 'سكاكا'],
        'Jazan'          => ['en' => 'Jazan',          'ar' => 'جازان'],
        'Bisha'          => ['en' => 'Bisha',          'ar' => 'بيشة'],
        'Al-Baha'        => ['en' => 'Al-Baha',        'ar' => 'الباحة'],
        'Qassim'         => ['en' => 'Qassim',         'ar' => 'القصيم'],
        'Al-Kharj'       => ['en' => 'Al-Kharj',       'ar' => 'الخرج'],
        'Unaizah'        => ['en' => 'Unaizah',        'ar' => 'عنيزة'],
        'Rabigh'         => ['en' => 'Rabigh',         'ar' => 'رابغ'],
        'Al-Ahsa'        => ['en' => 'Al-Ahsa',        'ar' => 'الأحساء'],
    ];

    public $table = 'locations';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'name',
        'arabic_name',
        'pickup_waiting_time',
        'drop_off_waiting_time',
        'description',
        'lat',
        'lng',
        'mobile',
        'city',
        'neighborhood',
        'status',
        'created_by_id',
        'updated_by_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];


    protected static function booted()
    {
        static::addGlobalScope('enabled', function (Builder $builder) {
            $builder->where('status', 1);
        });

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by_id = auth()->id();
                $model->updated_by_id = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by_id = auth()->id();
            }
        });
    }

    public function locationsClients()
    {
        return $this->belongsToMany(Client::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
