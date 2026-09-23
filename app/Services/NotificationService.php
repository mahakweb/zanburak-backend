<?php

namespace App\Services;

use App\Models\User;
use App\Models\Event;
use App\Models\NotificationPreference;
use App\Services\NotificationChannelGate;
use App\Notifications\CustomEventNotification;
use App\Notifications\Channels\SmsNotificationChannel;
use App\Notifications\Channels\SyncDatabaseChannel;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * ارسال اطلاع‌رسانی برای یک رویداد
     *
     * @param User $user کاربری که باید اطلاع‌رسانی دریافت کند
     * @param string $eventSlug slug رویداد
     * @param array $data داده‌های اضافی برای اطلاع‌رسانی
     * @return void
     */
    public function sendNotification(User $user, string $eventSlug, array $data = [])
    {
        try {
            // بررسی اینکه آیا کاربر اطلاع‌رسانی‌ها را غیرفعال کرده است
            // اگر فیلد null باشد، به صورت پیش‌فرض true در نظر می‌گیریم
            if ($user->notifications_enabled === false) {
                Log::info("Notifications disabled for user {$user->id}");
                return;
            }

            // پیدا کردن رویداد
            $event = Event::where('slug', $eventSlug)->first();
            
            if (!$event) {
                Log::warning("Event not found: {$eventSlug}");
                return;
            }

            // بررسی تنظیمات کاربر برای این رویداد
            $preference = NotificationPreference::where('user_id', $user->id)
                ->where('event_id', $event->id)
                ->first();

            // اگر تنظیمات کاربر وجود نداشت، از تنظیمات پیش‌فرض Event استفاده می‌کنیم
            $channels = [];
            
            if ($preference) {
                // بررسی کانال‌های فعال برای کاربر
                if ($preference->via_site && $event->is_site_enabled) {
                    $channels[] = SyncDatabaseChannel::class;
                }
                if ($preference->via_email && $event->is_email_enabled) {
                    $channels[] = 'mail';
                }
                if ($preference->via_sms && $event->is_sms_enabled) {
                    $channels[] = SmsNotificationChannel::class;
                }
                // Telegram فعلا غیرفعال است
                // if ($preference->via_telegram && $event->is_telegram_enabled) {
                //     $channels[] = 'telegram';
                // }
            } else {
                // استفاده از تنظیمات پیش‌فرض Event
                if ($event->is_site_enabled) {
                    $channels[] = SyncDatabaseChannel::class;
                }
                if ($event->is_email_enabled) {
                    $channels[] = 'mail';
                }
                if ($event->is_sms_enabled) {
                    $channels[] = SmsNotificationChannel::class;
                }
            }

            // اگر هیچ کانالی فعال نبود، اطلاع‌رسانی ارسال نمی‌شود
            $channels = app(NotificationChannelGate::class)->filterChannels($user, $channels);

            if (empty($channels)) {
                Log::info("No active channels for user {$user->id} and event {$eventSlug}");
                return;
            }

            // آماده کردن داده‌های اطلاع‌رسانی
            $notificationData = array_merge([
                'event_id' => $event->id,
                'event_title' => $event->title,
                'event_slug' => $event->slug,
                'message' => $data['message'] ?? $event->title,
            ], $data);

            // ارسال اطلاع‌رسانی
            $user->notify(new CustomEventNotification($event, $notificationData, $channels));

        } catch (\Exception $e) {
            Log::error("Error sending notification: " . $e->getMessage(), [
                'user_id' => $user->id,
                'event_slug' => $eventSlug,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * ارسال اطلاع‌رسانی برای چند کاربر
     *
     * @param array $userIds آرایه‌ای از ID کاربران
     * @param string $eventSlug slug رویداد
     * @param array $data داده‌های اضافی
     * @return void
     */
    public function sendBulkNotification(array $userIds, string $eventSlug, array $data = [])
    {
        $users = User::whereIn('id', $userIds)->get();
        
        foreach ($users as $user) {
            $this->sendNotification($user, $eventSlug, $data);
        }
    }

    /**
     * دریافت کانال‌های فعال برای یک کاربر و رویداد (بدون ارسال)
     * این متد فقط کانال‌ها را برمی‌گرداند تا Notification فیزیکی خودش ارسال کند
     *
     * @param User $user کاربری که باید اطلاع‌رسانی دریافت کند
     * @param string $eventSlug slug رویداد
     * @return array آرایه‌ای از کانال‌های فعال
     */
    public function getChannels(User $user, string $eventSlug): array
    {
        try {
            // بررسی اینکه آیا کاربر اطلاع‌رسانی‌ها را غیرفعال کرده است
            if ($user->notifications_enabled === false) {
                Log::info("NotificationService: Notifications disabled for user {$user->id}");
                return [];
            }

            // پیدا کردن رویداد
            $event = Event::where('slug', $eventSlug)->first();
            
            $gate = app(NotificationChannelGate::class);

            if (!$event) {
                Log::warning("NotificationService: Event not found: {$eventSlug} for user {$user->id}");
                return $gate->allows($user, SyncDatabaseChannel::class) ? [SyncDatabaseChannel::class] : [];
            }

            // بررسی تنظیمات کاربر برای این رویداد
            $preference = NotificationPreference::where('user_id', $user->id)
                ->where('event_id', $event->id)
                ->first();

            // اگر تنظیمات کاربر وجود نداشت، از تنظیمات پیش‌فرض Event استفاده می‌کنیم
            $channels = [];
            
            if ($preference) {
                // بررسی کانال‌های فعال برای کاربر
                if ($preference->via_site && $event->is_site_enabled) {
                    $channels[] = SyncDatabaseChannel::class;
                }
                if ($preference->via_email && $event->is_email_enabled) {
                    $channels[] = 'mail';
                }
                if ($preference->via_sms && $event->is_sms_enabled) {
                    $channels[] = SmsNotificationChannel::class;
                }
                // Telegram فعلا غیرفعال است
                // if ($preference->via_telegram && $event->is_telegram_enabled) {
                //     $channels[] = 'telegram';
                // }
            } else {
                // استفاده از تنظیمات پیش‌فرض Event
                if ($event->is_site_enabled) {
                    $channels[] = SyncDatabaseChannel::class;
                }
                if ($event->is_email_enabled) {
                    $channels[] = 'mail';
                }
                if ($event->is_sms_enabled) {
                    $channels[] = SmsNotificationChannel::class;
                }
            }

            Log::info("NotificationService: Channels for user {$user->id} and event '{$eventSlug}'", [
                'channels' => $channels,
                'has_preference' => $preference ? true : false,
                'event_enabled_channels' => [
                    'site' => $event->is_site_enabled,
                    'email' => $event->is_email_enabled,
                    'sms' => $event->is_sms_enabled,
                ]
            ]);

            $channels = $gate->filterChannels($user, $channels);

            if (empty($channels)) {
                Log::info("NotificationService: Every channel is disabled for user {$user->id} and event '{$eventSlug}'");
                return [];
            }

            return $channels;

        } catch (\Exception $e) {
            Log::error("NotificationService: Error getting channels: " . $e->getMessage(), [
                'user_id' => $user->id,
                'event_slug' => $eventSlug,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
}

