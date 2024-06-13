<?php

// app/Models/FollowRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowRequest extends Model
{
    protected $fillable = [

        'user_id',

        'follower_id',

        'followed_id',

        'status'

        ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }
}
