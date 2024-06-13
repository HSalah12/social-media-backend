<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsFeedItem extends Model

{
    use Moderatable;

    protected $fillable = ['title','content', 'user_id','category','views', 'likes', 'comments', 'shares', 'recency_factor','status'];

    public function user()
    {    

        return $this->belongsTo(User::class);
    }
   
    public function calculateScore()
{
    return (0.3 * $this->views) + (0.5 * $this->likes) + (0.7 * $this->comments) + (0.8 * $this->shares) + (0.6 * $this->recency_factor);
}
}