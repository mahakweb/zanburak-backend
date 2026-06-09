<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'inviter_id',
        'identifier',
        'channel',
        'last_sent_at',
        'send_count',
    ];

    protected $casts = [
        'last_sent_at' => 'datetime',
    ];

    public function inviter()
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }
}
