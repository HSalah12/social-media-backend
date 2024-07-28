<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Storage; 
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Support\Facades\Cache;
use Str;
use DB;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    use HasFactory, Notifiable, HasApiTokens, InteractsWithMedia, HasRoles;

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
    public function friendsss()
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
        ->withPivot('status')
        ->wherePivot('status', 'friend')
        ->join('user_statuses', 'users.id', '=', 'user_statuses.user_id')
        ->select('users.id', 'users.name', 'users.profile_picture', 'user_statuses.status as is_online');
}

public function notificationSettings()
{
    return $this->hasOne(NotificationSetting::class);
}
    public function status()
    {
        return $this->hasOne(UserStatus::class);
    }
    public function interactions()
    {
        return $this->hasMany(UserInteraction::class);
    }

    public function getProfilePictureUrlAttribute()
    {
        return $this->profile_picture ? asset(Storage::url($this->profile_picture)) : null;
    }
    
    public function getCoverPhotoUrlAttribute()
    {
        return $this->cover_photo ? asset(Storage::url($this->cover_photo)) : null;
    }
    public function newsFeedItems()
    {
        return $this->hasMany(NewsFeedItem::class);
    }

    public function likedNewsFeedItems()
    {
        return $this->belongsToMany(NewsFeedItem::class, 'likes', 'user_id', 'news_feed_item_id');
    }
    
    public function setPrivacySettings($settings)
    {
        $this->privacy_settings = $settings;
        $this->save();
    }

      // Define the relationship to the User model for the follower
     
    
    public function getPrivacySettings()
    {
        return $this->privacy_settings ?? [];
    }

    public function follower()
{
    return $this->belongsToMany(User::class, 'followers', 'followed_id', 'user_id');
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
        return $this->belongsToMany(User::class, 'followers', 'user_id', 'followed_id');
    }

    public function isFollowing($userId)
{
    return Follower::where('follower_id', $this->id)
        ->where('followed_id', $userId)
        ->where('is_accepted', true)
        ->exists();
}
public function getFollowStatus($userId)
{
    $followRequest = FollowRequest::where('follower_id', $this->id)
        ->where('followed_id', $userId)
        ->first();

    if ($followRequest) {
        return $followRequest->status;
    }

    return 'not_following';
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
        return $this->hasMany(FollowRequest::class, 'user_id','followed_id');
    }
    
    public function followerRequests()
    {
        return $this->hasMany(FollowRequest::class, 'follows_user_id');
    }
    public function friendss()
    {
        $friendships = $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
                             ->withPivot('status')
                             ->wherePivot('status', 'friend')
                             ->select('users.id', 'users.name', 'users.profile_picture', 'friendships.user_id as pivot_user_id', 'friendships.friend_id as pivot_friend_id', 'friendships.status as pivot_status');

       
        return $friendships;
    }
    public function friends()
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
                    ->withPivot('status');
    }

    public function getFriendshipStatus($friendId)
    {
        $authUserId = $this->id;

        // Ensure the friend relationship is checked both ways
        $friendship = DB::table('friend_requests')
            ->where(function ($query) use ($authUserId, $friendId) {
                $query->where('sender_id', $authUserId)
                      ->where('receiver_id', $friendId);
            })
            ->orWhere(function ($query) use ($authUserId, $friendId) {
                $query->where('sender_id', $friendId)
                      ->where('receiver_id', $authUserId);
            })
            ->first();

        if ($friendship) {
            return $friendship->status === 'pending' && $friendship->sender_id == $authUserId
                ? 'waiting for accept'
                : $friendship->status;
        }

        return 'not_friend'; // Return 'not_friend' if no friendship found
    }
    public function isFriendWith(User $otherUser)
{
    return $this->friends()->where('friend_id', $otherUser->id)->where('status', 'accepted')->exists() ||
           $otherUser->friends()->where('friend_id', $this->id)->where('status', 'accepted')->exists();
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
    public function friendRequests()
    {
        return $this->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')
                    ->withPivot('status')
                    ->wherePivot('status', 'pending');
    }
    public function getNumberOfFriendsAttribute()
    {
        return $this->friends()->count();
    }
    public function originalUser()
    {
        return $this->belongsTo(User::class, 'original_news_feed_item_id');
    }
    public function followerss()
    {
        return $this->belongsToMany(User::class, 'followers', 'followed_id', 'follower_id')
                    ->withPivot('is_accepted')
                    ->wherePivot('is_accepted', true);
    }

    public function getNumberOfFollowersAttribute()
    {
        return $this->followerss()->count();
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
        public function savedPosts()
        {
            return $this->belongsToMany(NewsFeedItem::class, 'saved_posts')->withTimestamps();
        }
        public function savedNewsFeedItems()
        {
            return $this->belongsToMany(NewsFeedItem::class, 'saved_posts', 'user_id', 'news_feed_item_id')->withTimestamps();
        }
}
