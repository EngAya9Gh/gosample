<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmergencyFlag extends Model
{
    protected $fillable = ['active', 'message'];
}
