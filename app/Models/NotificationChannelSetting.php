<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationChannelSetting extends Model
{
    protected $fillable = [
        'email_enabled',
        'sms_enabled',
        'telegram_enabled',
        'site_enabled',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'telegram_enabled' => 'boolean',
        'site_enabled' => 'boolean',
    ];

    public static function current(): self
    {
        $setting = static::query()->first();

        if ($setting) {
            return $setting;
        }

        return static::query()->create([
            'email_enabled' => true,
            'sms_enabled' => true,
            'telegram_enabled' => true,
            'site_enabled' => true,
        ]);
    }

    public function flags(): array
    {
        return [
            'email' => (bool) $this->email_enabled,
            'sms' => (bool) $this->sms_enabled,
            'telegram' => (bool) $this->telegram_enabled,
            'site' => (bool) $this->site_enabled,
        ];
    }
}
