<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedingSchedule extends Model
{
    
    protected $fillable = [
        'waktu_makan',
        'porsi',
        'is_active',
    ];
}