<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class NewsFeedItem extends Model

{
    use Moderatable;

    protected $fillable = [
        'title',
        'content',
        'image',
        'user_id',
        'category',
        'views',
        'likes',
        'comments',
        'shares',
        'recency_factor',
        'status',
        'latitude',
        'longitude',
    ];

    public function user()
    {    

        return $this->belongsTo(User::class);
    }
   
    public function calculateScore()
{
    return (0.3 * $this->views) + (0.5 * $this->likes) + (0.7 * $this->comments) + (0.8 * $this->shares) + (0.6 * $this->recency_factor);
}

public function likes()
{
    return $this->belongsToMany(User::class, 'likes', 'news_feed_item_id', 'user_id');
}
public function interactions()
{
    return $this->hasMany(UserInteraction::class);
}
public function getImageUrlAttribute()
{
    return $this->image ? url('storage/' . $this->image) : null;
}

}