<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FriendRequest extends Model
{
    use HasFactory;

    protected $table = 'friend_requests'; // Ensure this matches your table name

    protected $fillable = ['sender_id', 'receiver_id', 'status'];
}
