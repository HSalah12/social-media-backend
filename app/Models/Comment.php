<?php

// app\Models\Comment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'user_id', 'news_feed_item_id', 'content',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function newsFeedItem()
    {
        return $this->belongsTo(NewsFeedItem::class);
    }
}
