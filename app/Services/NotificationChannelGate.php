<?php

namespace App\Services;

use App\Models\NotificationChannelSetting;
use App\Models\User;
use App\Notifications\Auth\ActiveCodeEmail;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Auth\VerifyEmail;
use App\Notifications\Channels\GhasedakChannel;
use App\Notifications\Channels\MeliPayamakChannel;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\SmsNotificationChannel;
use App\Notifications\Channels\SyncDatabaseChannel;

class NotificationChannelGate
{
    /** Security mail still goes out when the user turns email off, unless the admin has disabled email. */
    private const CRITICAL_MAIL = [
        VerifyEmail::class,
        ActiveCodeEmail::class,
        ResetPasswordNotification::class,
    ];

    public function adminEnabled(string $channelKey): bool
    {
        $column = $this->columnFor($channelKey);

        return $column ? (bool) NotificationChannelSetting::current()->{$column} : true;
    }

    public function allows(?object $notifiable, string $channel, ?string $notificationClass = null): bool
    {
        $channelKey = $this->keyForTransport($channel);

        if (!$channelKey) {
            return true;
        }

        if (!$this->adminEnabled($channelKey)) {
            return false;
        }

        if (!$notifiable instanceof User) {
            return true;
        }

        $userColumn = 'notify_'.$channelKey;
        if ($notifiable->{$userColumn} === false) {
            if ($channelKey === 'email' && in_array($notificationClass, self::CRITICAL_MAIL, true)) {
                return true;
            }

            return false;
        }

        return true;
    }

    public function filterChannels(?object $notifiable, array $channels, ?string $notificationClass = null): array
    {
        return array_values(array_filter(
            $channels,
            fn ($channel) => $this->allows($notifiable, is_string($channel) ? $channel : (string) $channel, $notificationClass)
        ));
    }

    public function keyForPreferenceField(string $field): ?string
    {
        return match ($field) {
            'via_email' => 'email',
            'via_sms' => 'sms',
            'via_telegram' => 'telegram',
            'via_site' => 'site',
            default => null,
        };
    }

    private function columnFor(string $channelKey): ?string
    {
        return match ($channelKey) {
            'email' => 'email_enabled',
            'sms' => 'sms_enabled',
            'telegram' => 'telegram_enabled',
            'site' => 'site_enabled',
            default => null,
        };
    }

    private function keyForTransport(string $channel): ?string
    {
        return match ($channel) {
            'mail' => 'email',
            'database', SyncDatabaseChannel::class => 'site',
            'sms', SmsNotificationChannel::class, SmsChannel::class, GhasedakChannel::class, MeliPayamakChannel::class => 'sms',
            'telegram' => 'telegram',
            default => null,
        };
    }
}
