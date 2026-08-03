<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessengerWallpaper extends Model
{
    protected $fillable = [
        'user_id',
        'path',
        'url',
        'thumb_url',
        'mime',
        'size',
        'width',
        'height',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'thumb_url' => $this->thumb_url ?: $this->url,
            'mime' => $this->mime,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
            'created_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}
