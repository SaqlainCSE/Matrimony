<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'age_range',
        'height_range',
        'marital_status',
        'religion',
        'education_level',
        'occupation',
        'others',
    ];
}
