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
    ];

    protected $casts = [
        'enter_to_send' => 'boolean',
        'quote_with_title' => 'boolean',
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
        ];
    }
}
