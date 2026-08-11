<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MessengerStickerPack extends Model
{
    protected $table = 'messenger_sticker_packs';

    protected $fillable = [
        'uuid',
        'user_id',
        'title',
        'title_fa',
        'icon',
        'is_public',
        'is_builtin',
        'sticker_count',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_builtin' => 'boolean',
        'sticker_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $pack) {
            if (! $pack->uuid) {
                $pack->uuid = (string) Str::uuid();
            }
        });
    }

    public function stickers()
    {
        return $this->hasMany(MessengerSticker::class, 'pack_id')->orderBy('sort_order')->orderBy('id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function toApiArray(bool $withStickers = true): array
    {
        $data = [
            'id' => $this->uuid,
            'db_id' => $this->id,
            'title' => $this->title,
            'titleFa' => $this->title_fa ?: $this->title,
            'icon' => $this->icon ?: '⭐',
            'custom' => ! $this->is_builtin,
            'public' => (bool) $this->is_public,
            'sticker_count' => (int) $this->sticker_count,
            'owner_id' => $this->user_id,
        ];
        if ($withStickers) {
            $data['stickers'] = $this->stickers->map(fn (MessengerSticker $s) => $s->toApiArray())->values()->all();
        }

        return $data;
    }
}
