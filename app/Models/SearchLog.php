<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'query',
        'latitude',
        'longitude',
        'radius',
        'results_count',
    ];


}


