<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessengerSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'enter_to_send',
        'quote_with_title',
        'wallpaper',
        'theme',
        'locale',
        'show_online',
        'show_last_seen',
        'show_phone',
        'show_email',
    ];

    protected $casts = [
        'enter_to_send' => 'boolean',
        'quote_with_title' => 'boolean',
        'show_online' => 'boolean',
        'show_last_seen' => 'boolean',
        'show_phone' => 'boolean',
        'show_email' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function defaults(): array
    {
        return [
            'enter_to_send' => true,
            'quote_with_title' => true,
            'wallpaper' => 'default',
            'theme' => null,
            'locale' => null,
            'show_online' => true,
            'show_last_seen' => true,
            'show_phone' => false,
            'show_email' => false,
        ];
    }
}
