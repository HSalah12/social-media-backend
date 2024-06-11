<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Support\Facades\Cache;
use Str;

class User extends Authenticatable implements HasMedia
{
    use HasFactory, Notifiable, HasApiTokens, InteractsWithMedia;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follow_requests', 'user_id','follows_user_id', 'followed_id')
            ->wherePivot('status', 'accepted');
    }

   public function follows()
{
    return $this->belongsToMany(User::class, 'followers', 'followed_id');
}

    public function follow(User $user)
    {
        return $this->follows()->save($user);
    }

    public function unfollow(User $user)
    {
        return $this->follows()->detach($user);
    }

    public function followings()
    {
        return $this->belongsToMany(User::class, 'followers', 'follows_user_id', 'followed_id');
    }

    public function isFollowing(User $user)
    {
        return $this->followings()->where('followed_id', $user->id)->exists();
    }
    public function following()
    {
        return $this->belongsToMany(User::class, 'follow_requests', 'follows_user_id', 'user_id')
            ->wherePivot('status', 'accepted');
    }
    public function isFollowedBy(User $user)
    {
        return $this->followers()->where('follows_user_id', $user->id)->exists();
    }
    public function followRequests()
    {
        return $this->hasMany(FollowRequest::class, 'user_id');
    }
    
    public function followerRequests()
    {
        return $this->hasMany(FollowRequest::class, 'follows_user_id');
    }
    
    public function friends()
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')->withPivot('is_accepted');;
    }

    public function isFriendWith(User $user)
    {
        return $this->friends()->where('friend_id', $user->id)->exists();
    }

    // Method to get mutual friends
    public function mutualFriends($otherUser)
    {
        $userFriends = $this->friends()->pluck('id')->toArray();
        $otherUserFriends = $otherUser->friends()->pluck('id')->toArray();

        return array_intersect($userFriends, $otherUserFriends);
    }

    // Method to suggest friends
    public function suggestFriends()
    {
        // Get the current user's friends
        $friends = $this->friends()->pluck('id')->toArray();

        // Get mutual friends
        $suggestions = User::where('id', '!=', $this->id)
            ->whereHas('followers', function ($query) use ($friends) {
                $query->whereIn('follower_id', $friends);
            })
            ->whereDoesntHave('followers', function ($query) {
                $query->where('follower_id', $this->id);
            })
            ->withCount(['followers' => function ($query) use ($friends) {
                $query->whereIn('follower_id', $friends);
            }])
            ->orderBy('followers_count', 'desc')
            ->get();

        return $suggestions;
    }


    public function sentFriendRequests()
{
    return $this->hasMany(FriendRequest::class, 'sender_id');
}

public function receivedFriendRequests()
{
    return $this->hasMany(FriendRequest::class, 'receiver_id');
}

        public function verify_token(){
            $token = Str::uuid();
            Cache::put('verify_token',$token);
            Cache::put('user_id',$this->id);
            return $token;   
        }



}
