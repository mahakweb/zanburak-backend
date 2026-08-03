<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessengerSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'access_enabled',
        'enter_to_send',
        'quote_with_title',
        'forward_tap_to_chat',
        'wallpaper',
        'wallpaper_config',
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
        'forward_tap_to_chat' => 'boolean',
        'wallpaper_config' => 'array',
        'show_online' => 'boolean',
        'show_last_seen' => 'boolean',
        'show_phone' => 'boolean',
        'show_email' => 'boolean',
    ];

    /**
     * Nullable override: null inherits global default.
     */
    public function getAccessEnabledAttribute(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function setAccessEnabledAttribute(mixed $value): void
    {
        if ($value === null) {
            $this->attributes['access_enabled'] = null;

            return;
        }

        $this->attributes['access_enabled'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function defaults(): array
    {
        return [
            'enter_to_send' => true,
            'quote_with_title' => true,
            'forward_tap_to_chat' => false,
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
