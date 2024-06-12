<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityFeed extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'activity_type', 'description', 'related_id'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
