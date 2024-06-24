<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['conversation_id', 'sender_id','receiver_id', 'message'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

   
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }


    public function groupChat()
    {
        return $this->belongsTo(GroupChat::class);
    }

    public function scopeSearchAndFilter($query, $filters)
    {
        $query->when($filters['keyword'], function ($query, $keyword) {
            $query->where('content', 'like', '%' . $keyword . '%');
        })->when($filters['sender_id'], function ($query, $senderId) {
            $query->where('sender_id', $senderId);
        })->when($filters['receiver_id'], function ($query, $receiverId) {
            $query->where('receiver_id', $receiverId);
        })->when($filters['start_date'], function ($query, $startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        })->when($filters['end_date'], function ($query, $endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        });
    }
}
