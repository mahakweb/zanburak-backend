<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncedContact extends Model
{
    protected $fillable = [
        'user_id',
        'phone_normalized',
        'name',
        'matched_user_id',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function matchedUser()
    {
        return $this->belongsTo(User::class, 'matched_user_id');
    }
}
