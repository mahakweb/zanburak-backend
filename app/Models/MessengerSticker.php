<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MessengerSticker extends Model
{
    protected $table = 'messenger_stickers';

    protected $fillable = [
        'uuid',
        'pack_id',
        'media_id',
        'emoji',
        'kind',
        'path',
        'url',
        'mime',
        'size',
        'width',
        'height',
        'sort_order',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $sticker) {
            if (! $sticker->uuid) {
                $sticker->uuid = (string) Str::uuid();
            }
        });
    }

    public function pack()
    {
        return $this->belongsTo(MessengerStickerPack::class, 'pack_id');
    }

    public function media()
    {
        return $this->belongsTo(MessengerMedia::class, 'media_id');
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->uuid,
            'packId' => optional($this->pack)->uuid,
            'media_id' => $this->media_id,
            'mediaId' => $this->media_id,
            'emoji' => $this->emoji,
            'kind' => $this->kind ?: 'image',
            'src' => $this->url,
            'url' => $this->url,
            'mime' => $this->mime,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
