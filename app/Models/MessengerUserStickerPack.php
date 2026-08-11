<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessengerUserStickerPack extends Model
{
    protected $table = 'messenger_user_sticker_packs';

    protected $fillable = [
        'user_id',
        'pack_id',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function pack()
    {
        return $this->belongsTo(MessengerStickerPack::class, 'pack_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
