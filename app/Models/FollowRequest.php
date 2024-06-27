<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowRequest extends Model
{
    use HasFactory;

    protected $fillable = ['follower_id', 'followed_id', 'status'];

    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'followed_id', 'follower_id')
                    ->withPivot('status', 'is_accepted');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'followed_id')
                    ->withPivot('status', 'is_accepted');
    }

    public function isFollowing(User $user)
    {
        return $this->following()->where('followed_id', $user->id)->where('is_accepted', true)->exists();
    }

    public function getFollowStatus($userId)
    {
        $follow = $this->following()->where('followed_id', $userId)->first();

        if ($follow) {
            return $follow->pivot->status;
        }

        return 'not followed';
    }
}
