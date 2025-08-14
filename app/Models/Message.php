<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    protected $fillable = [
        'content',
        'read_at',
        'replyed_id',
        'sender_id',
        'receiver_id',
        'edited_at',
        'deleted_by_sender',
        'deleted_by_receiver'
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function replyed()
    {
        return $this->belongsTo(Message::class, 'replyed_id');
    }
}
