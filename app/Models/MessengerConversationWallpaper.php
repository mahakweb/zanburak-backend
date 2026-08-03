<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessengerConversationWallpaper extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'wallpaper_id',
        'config',
        'for_both',
        'shared_from_user_id',
    ];

    protected $casts = [
        'config' => 'array',
        'for_both' => 'boolean',
    ];

    public function wallpaper()
    {
        return $this->belongsTo(MessengerWallpaper::class, 'wallpaper_id');
    }
}
