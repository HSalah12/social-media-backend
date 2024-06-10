<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMediaTrait;

class User extends Authenticatable implements HasMedia
{
    use HasFactory, Notifiable,HasApiTokens, InteractsWithMedia ;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [
      
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function followers()
{
    return $this->belongsToMany(User::class, 'followers', 'follows_user_id', 'user_id');
}

public function follows()
{
    return $this->belongsToMany(User::class, 'followers', 'user_id', 'follows_user_id');
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
    return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followed_id');
}

// Check if a user is following another user
public function isFollowing(User $user)
{
    return $this->followings()->where('followed_id', $user->id)->exists();
}

// Check if a user is followed by another user
public function isFollowedBy(User $user)
{
    return $this->followers()->where('follower_id', $user->id)->exists();
}
public function friends()
{
    return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id');
}

public function isFriendWith(User $user)
{
    return $this->friends()->where('friend_id', $user->id)->exists();
}
}
