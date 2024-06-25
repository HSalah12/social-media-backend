<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Storage;

class UserInteraction extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'news_feed_item_id', 'interaction_type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function newsFeedItem()
    {
        return $this->belongsTo(NewsFeedItem::class);
    }
}
