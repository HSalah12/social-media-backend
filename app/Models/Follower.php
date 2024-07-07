<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follower extends Model
{
    use HasFactory;

    protected $fillable = ['follower_id', 'followed_id', 'is_accepted'];


    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    // Define the relationship to the User model for the followed user
    public function followed()
    {
        return $this->belongsTo(User::class, 'followed_id');
    }

    
}


