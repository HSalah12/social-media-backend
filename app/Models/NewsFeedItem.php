<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsFeedItem extends Model
{
    protected $fillable = ['title','content', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}