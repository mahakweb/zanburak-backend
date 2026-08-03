<?php

namespace App\Services\Messenger;

use App\Models\MessengerSetting;
use App\Models\MessengerUploadDaily;
use App\Models\User;
use App\Services\SiteSettingService;
use Illuminate\Support\Carbon;

class MessengerSystemConfig
{
    public const GROUP = 'messenger';

    public const PREFIX = 'messenger.';

    public function __construct(
        protected SiteSettingService $settings
    ) {}

    /**
     * Full schema with defaults (admin form + runtime).
     *
     * @return array<string, array{type: string, default: mixed, label: string, section: string, unit?: string, min?: int|float, max?: int|float, help?: string}>
     */
    public static function schema(): array
    {
        $media = config('messenger.media', []);

        return [
            // —— General ——
            'enabled' => [
                'type' => 'boolean', 'default' => true, 'section' => 'general',
                'label' => 'فعال بودن پیام‌رسان',
                'help' => 'در صورت غیرفعال بودن، هیچ کاربری به پیام‌رسان دسترسی ندارد.',
            ],
            'users_default_access' => [
                'type' => 'boolean', 'default' => true, 'section' => 'general',
                'label' => 'دسترسی پیش‌فرض کاربران',
                'help' => 'اگر برای کاربر دسترسی جداگانه تنظیم نشده باشد، این مقدار اعمال می‌شود.',
            ],
            'require_verified_email' => [
                'type' => 'boolean', 'default' => false, 'section' => 'general',
                'label' => 'الزام تأیید ایمیل',
            ],
            'new_user_cooldown_hours' => [
                'type' => 'integer', 'default' => 0, 'section' => 'general',
                'label' => 'دوره انتظار کاربران جدید (ساعت)',
                'help' => '۰ یعنی بدون محدودیت. کاربران تازه‌ثبت‌نام تا این مدت نمی‌توانند از پیام‌رسان استفاده کنند.',
                'min' => 0, 'max' => 720,
            ],
            'disabled_message' => [
                'type' => 'string', 'default' => 'پیام‌رسان موقتاً غیرفعال است.', 'section' => 'general',
                'label' => 'پیام غیرفعال بودن سامانه',
            ],
            'access_denied_message' => [
                'type' => 'string', 'default' => 'شما اجازه استفاده از پیام‌رسان را ندارید.', 'section' => 'general',
                'label' => 'پیام عدم دسترسی کاربر',
            ],

            // —— Features ——
            'allow_private_chats' => [
                'type' => 'boolean', 'default' => true, 'section' => 'features',
                'label' => 'چت خصوصی',
            ],
            'allow_groups' => [
                'type' => 'boolean', 'default' => true, 'section' => 'features',
                'label' => 'ساخت گروه',
            ],
            'allow_channels' => [
                'type' => 'boolean', 'default' => true, 'section' => 'features',
                'label' => 'ساخت کانال',
            ],
            'allow_saved_messages' => [
                'type' => 'boolean', 'default' => true, 'section' => 'features',
                'label' => 'پیام‌های ذخیره‌شده',
            ],
            'allow_forward' => [
                'type' => 'boolean', 'default' => true, 'section' => 'features',
                'label' => 'فوروارد پیام',
            ],
            'allow_reactions' => [
                'type' => 'boolean', 'default' => true, 'section' => 'features',
                'label' => 'واکنش (ری‌اکشن)',
            ],
            'allow_edit_messages' => [
                'type' => 'boolean', 'default' => true, 'section' => 'features',
                'label' => 'ویرایش پیام',
            ],
            'allow_delete_messages' => [
                'type' => 'boolean', 'default' => true, 'section' => 'features',
                'label' => 'حذف پیام',
            ],
            'allow_pin_messages' => [
                'type' => 'boolean', 'default' => true, 'section' => 'features',
                'label' => 'سنجاق پیام',
            ],
            'allow_voice_messages' => [
                'type' => 'boolean', 'default' => true, 'section' => 'features',
                'label' => 'پیام صوتی (ویس)',
            ],
            'allow_location' => [
                'type' => 'boolean', 'default' => true, 'section' => 'features',
                'label' => 'ارسال موقعیت مکانی',
            ],
            'allow_contacts' => [
                'type' => 'boolean', 'default' => true, 'section' => 'features',
                'label' => 'مخاطبین',
            ],
            'allow_user_search' => [
                'type' => 'boolean', 'default' => true, 'section' => 'features',
                'label' => 'جستجوی کاربران',
            ],
            'allow_wallpapers' => [
                'type' => 'boolean', 'default' => true, 'section' => 'features',
                'label' => 'پس‌زمینه چت',
            ],
            'allow_custom_wallpapers' => [
                'type' => 'boolean', 'default' => true, 'section' => 'features',
                'label' => 'آپلود پس‌زمینه سفارشی',
            ],

            // —— Uploads ——
            'uploads_enabled' => [
                'type' => 'boolean', 'default' => true, 'section' => 'uploads',
                'label' => 'اجازه آپلود برای همه',
                'help' => 'کل آپلودهای پیام‌رسان را یکجا خاموش/روشن می‌کند.',
            ],
            'allow_photo' => [
                'type' => 'boolean', 'default' => true, 'section' => 'uploads',
                'label' => 'آپلود عکس',
            ],
            'allow_video' => [
                'type' => 'boolean', 'default' => true, 'section' => 'uploads',
                'label' => 'آپلود ویدیو',
            ],
            'allow_audio' => [
                'type' => 'boolean', 'default' => true, 'section' => 'uploads',
                'label' => 'آپلود فایل صوتی',
            ],
            'allow_voice' => [
                'type' => 'boolean', 'default' => true, 'section' => 'uploads',
                'label' => 'آپلود ویس',
            ],
            'allow_file' => [
                'type' => 'boolean', 'default' => true, 'section' => 'uploads',
                'label' => 'آپلود فایل/سند',
            ],
            'max_photo_kb' => [
                'type' => 'integer',
                'default' => (int) ($media['max_photo_kb'] ?? 51200),
                'section' => 'uploads', 'label' => 'سقف حجم عکس', 'unit' => 'KB',
                'min' => 100, 'max' => 512000,
            ],
            'max_video_kb' => [
                'type' => 'integer',
                'default' => (int) ($media['max_video_kb'] ?? 51200),
                'section' => 'uploads', 'label' => 'سقف حجم ویدیو', 'unit' => 'KB',
                'min' => 100, 'max' => 1024000,
            ],
            'max_audio_kb' => [
                'type' => 'integer',
                'default' => (int) ($media['max_audio_kb'] ?? 51200),
                'section' => 'uploads', 'label' => 'سقف حجم صوت', 'unit' => 'KB',
                'min' => 100, 'max' => 512000,
            ],
            'max_voice_kb' => [
                'type' => 'integer',
                'default' => (int) ($media['max_voice_kb'] ?? 51200),
                'section' => 'uploads', 'label' => 'سقف حجم ویس', 'unit' => 'KB',
                'min' => 100, 'max' => 512000,
            ],
            'max_file_kb' => [
                'type' => 'integer',
                'default' => (int) ($media['max_file_kb'] ?? 51200),
                'section' => 'uploads', 'label' => 'سقف حجم فایل', 'unit' => 'KB',
                'min' => 100, 'max' => 1024000,
            ],
            'max_wallpaper_kb' => [
                'type' => 'integer', 'default' => 12288, 'section' => 'uploads',
                'label' => 'سقف حجم پس‌زمینه', 'unit' => 'KB',
                'min' => 100, 'max' => 51200,
            ],
            'daily_upload_bytes' => [
                'type' => 'integer', 'default' => 0, 'section' => 'uploads',
                'label' => 'سقف آپلود روزانه هر کاربر', 'unit' => 'bytes',
                'help' => '۰ یعنی نامحدود. مجموع حجم فایل‌های آپلودشده در یک روز.',
                'min' => 0, 'max' => 10737418240,
            ],
            'daily_upload_count' => [
                'type' => 'integer', 'default' => 0, 'section' => 'uploads',
                'label' => 'سقف تعداد فایل روزانه',
                'help' => '۰ یعنی نامحدود.',
                'min' => 0, 'max' => 100000,
            ],
            'max_album_items' => [
                'type' => 'integer', 'default' => 10, 'section' => 'uploads',
                'label' => 'حداکثر آیتم در یک آلبوم',
                'min' => 1, 'max' => 50,
            ],

            // —— Limits ——
            'max_message_length' => [
                'type' => 'integer',
                'default' => (int) config('messenger.max_message_length', 5000),
                'section' => 'limits', 'label' => 'حداکثر طول پیام (کاراکتر)',
                'min' => 100, 'max' => 50000,
            ],
            'max_group_members' => [
                'type' => 'integer',
                'default' => (int) config('messenger_groups.max_members_per_create', 200),
                'section' => 'limits', 'label' => 'حداکثر اعضای گروه در ساخت',
                'min' => 2, 'max' => 10000,
            ],
            'max_channel_subscribers' => [
                'type' => 'integer', 'default' => 0, 'section' => 'limits',
                'label' => 'سقف مشترکین کانال در ساخت',
                'help' => '۰ یعنی نامحدود (فقط هنگام ساخت).',
                'min' => 0, 'max' => 1000000,
            ],
            'edit_window_minutes' => [
                'type' => 'integer', 'default' => 0, 'section' => 'limits',
                'label' => 'پنجره ویرایش پیام (دقیقه)',
                'help' => '۰ یعنی بدون محدودیت زمانی.',
                'min' => 0, 'max' => 10080,
            ],
            'messages_per_page' => [
                'type' => 'integer',
                'default' => (int) config('messenger.messages_per_page', 40),
                'section' => 'limits', 'label' => 'تعداد پیام در هر صفحه',
                'min' => 10, 'max' => 200,
            ],
            'rate_send_per_minute' => [
                'type' => 'integer',
                'default' => (int) config('messenger.rate_limits.send', 60),
                'section' => 'limits', 'label' => 'حد ارسال در دقیقه',
                'min' => 1, 'max' => 600,
            ],
            'rate_search_per_minute' => [
                'type' => 'integer',
                'default' => (int) config('messenger.rate_limits.search', 30),
                'section' => 'limits', 'label' => 'حد جستجو در دقیقه',
                'min' => 1, 'max' => 300,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $out = [];
        foreach (self::schema() as $key => $meta) {
            $out[$key] = $meta['default'];
        }

        return $out;
    }

    /**
     * Merged DB overrides + defaults.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $defaults = $this->defaults();
        $prefixed = [];
        foreach ($defaults as $key => $default) {
            $prefixed[self::PREFIX.$key] = $default;
        }

        $values = $this->settings->getMany($prefixed);
        $out = [];
        foreach ($defaults as $key => $default) {
            $out[$key] = $values[self::PREFIX.$key] ?? $default;
        }

        return $out;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function bool(string $key): bool
    {
        return (bool) $this->get($key, false);
    }

    public function int(string $key): int
    {
        return (int) $this->get($key, 0);
    }

    /**
     * Persist admin form payload (unprefixed keys).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function update(array $payload, ?int $updatedBy = null): array
    {
        $schema = self::schema();
        $items = [];

        foreach ($payload as $key => $value) {
            if (! isset($schema[$key])) {
                continue;
            }
            $meta = $schema[$key];
            $type = $meta['type'];
            $casted = $this->castIncoming($value, $type);

            if (isset($meta['min']) && is_numeric($casted) && $casted < $meta['min']) {
                $casted = $meta['min'];
            }
            if (isset($meta['max']) && is_numeric($casted) && $casted > $meta['max']) {
                $casted = $meta['max'];
            }

            $items[self::PREFIX.$key] = [
                'value' => $casted,
                'type' => $type,
                'group' => self::GROUP,
            ];
        }

        if ($items !== []) {
            $this->settings->putMany($items, $updatedBy);
        }

        return $this->all();
    }

    /**
     * Admin payload with schema metadata for the UI.
     *
     * @return array{settings: array<string, mixed>, schema: array<string, mixed>, sections: array<string, string>}
     */
    public function adminPayload(): array
    {
        return [
            'settings' => $this->all(),
            'schema' => self::schema(),
            'sections' => [
                'general' => 'عمومی و دسترسی',
                'features' => 'قابلیت‌ها',
                'uploads' => 'آپلود و رسانه',
                'limits' => 'محدودیت‌ها و نرخ',
            ],
        ];
    }

    /**
     * Client-facing config (safe for authenticated messenger users).
     *
     * @return array<string, mixed>
     */
    public function clientConfig(?User $user = null): array
    {
        $all = $this->all();
        $access = $user ? $this->assertUserAccess($user, false) : ['allowed' => false, 'reason' => null, 'message' => null];
        $deniedMessage = (string) ($access['message'] ?? $all['access_denied_message']);

        return [
            'enabled' => (bool) $all['enabled'],
            'access_allowed' => (bool) ($access['allowed'] ?? false),
            'access_reason' => $access['reason'] ?? null,
            'disabled_message' => (string) $all['disabled_message'],
            'access_denied_message' => $deniedMessage,
            'features' => [
                'private_chats' => (bool) $all['allow_private_chats'],
                'groups' => (bool) $all['allow_groups'],
                'channels' => (bool) $all['allow_channels'],
                'saved_messages' => (bool) $all['allow_saved_messages'],
                'forward' => (bool) $all['allow_forward'],
                'reactions' => (bool) $all['allow_reactions'],
                'edit_messages' => (bool) $all['allow_edit_messages'],
                'delete_messages' => (bool) $all['allow_delete_messages'],
                'pin_messages' => (bool) $all['allow_pin_messages'],
                'voice_messages' => (bool) $all['allow_voice_messages'],
                'location' => (bool) $all['allow_location'],
                'contacts' => (bool) $all['allow_contacts'],
                'user_search' => (bool) $all['allow_user_search'],
                'wallpapers' => (bool) $all['allow_wallpapers'],
                'custom_wallpapers' => (bool) $all['allow_custom_wallpapers'],
            ],
            'uploads' => [
                'enabled' => (bool) $all['uploads_enabled'],
                'allow_photo' => (bool) $all['allow_photo'],
                'allow_video' => (bool) $all['allow_video'],
                'allow_audio' => (bool) $all['allow_audio'],
                'allow_voice' => (bool) $all['allow_voice'] && (bool) $all['allow_voice_messages'],
                'allow_file' => (bool) $all['allow_file'],
                'max_photo_kb' => (int) $all['max_photo_kb'],
                'max_video_kb' => (int) $all['max_video_kb'],
                'max_audio_kb' => (int) $all['max_audio_kb'],
                'max_voice_kb' => (int) $all['max_voice_kb'],
                'max_file_kb' => (int) $all['max_file_kb'],
                'max_wallpaper_kb' => (int) $all['max_wallpaper_kb'],
                'daily_upload_bytes' => (int) $all['daily_upload_bytes'],
                'daily_upload_count' => (int) $all['daily_upload_count'],
                'max_album_items' => (int) $all['max_album_items'],
            ],
            'limits' => [
                'max_message_length' => (int) $all['max_message_length'],
                'max_group_members' => (int) $all['max_group_members'],
                'max_channel_subscribers' => (int) $all['max_channel_subscribers'],
                'edit_window_minutes' => (int) $all['edit_window_minutes'],
                'messages_per_page' => (int) $all['messages_per_page'],
                'rate_send_per_minute' => (int) $all['rate_send_per_minute'],
                'rate_search_per_minute' => (int) $all['rate_search_per_minute'],
            ],
            'quota' => $user ? $this->userQuotaStatus($user) : null,
        ];
    }

    /**
     * @return array{allowed: bool, reason: string|null, message: string|null}
     */
    public function assertUserAccess(User $user, bool $throw = true): array
    {
        if (! $this->bool('enabled')) {
            $result = [
                'allowed' => false,
                'reason' => 'disabled',
                'message' => (string) $this->get('disabled_message'),
            ];
            if ($throw) {
                throw new \RuntimeException($result['message'], 503);
            }

            return $result;
        }

        if ($user->isSuperUser()) {
            return ['allowed' => true, 'reason' => null, 'message' => null];
        }

        $override = MessengerSetting::query()
            ->where('user_id', $user->id)
            ->value('access_enabled');

        $allowed = $override === null
            ? $this->bool('users_default_access')
            : (bool) $override;

        if (! $allowed) {
            $result = [
                'allowed' => false,
                'reason' => 'user_denied',
                'message' => (string) $this->get('access_denied_message'),
            ];
            if ($throw) {
                throw new \RuntimeException($result['message'], 403);
            }

            return $result;
        }

        if ($this->bool('require_verified_email') && ! $user->hasVerifiedEmail()) {
            $result = [
                'allowed' => false,
                'reason' => 'email_unverified',
                'message' => 'برای استفاده از پیام‌رسان باید ایمیل خود را تأیید کنید.',
            ];
            if ($throw) {
                throw new \RuntimeException($result['message'], 403);
            }

            return $result;
        }

        $cooldown = $this->int('new_user_cooldown_hours');
        if ($cooldown > 0 && $user->created_at) {
            $unlockAt = Carbon::parse($user->created_at)->addHours($cooldown);
            if (now()->lt($unlockAt)) {
                $result = [
                    'allowed' => false,
                    'reason' => 'cooldown',
                    'message' => 'حساب شما هنوز اجازه استفاده از پیام‌رسان را ندارد. لطفاً بعداً تلاش کنید.',
                ];
                if ($throw) {
                    throw new \RuntimeException($result['message'], 403);
                }

                return $result;
            }
        }

        return ['allowed' => true, 'reason' => null, 'message' => null];
    }

    public function mediaMaxKb(string $type): int
    {
        return match ($type) {
            'photo' => $this->int('max_photo_kb'),
            'video' => $this->int('max_video_kb'),
            'audio' => $this->int('max_audio_kb'),
            'voice' => $this->int('max_voice_kb'),
            'file' => $this->int('max_file_kb'),
            default => $this->int('max_file_kb'),
        };
    }

    public function isMediaTypeAllowed(string $type): bool
    {
        if (! $this->bool('uploads_enabled')) {
            return false;
        }

        return match ($type) {
            'photo' => $this->bool('allow_photo'),
            'video' => $this->bool('allow_video'),
            'audio' => $this->bool('allow_audio'),
            'voice' => $this->bool('allow_voice') && $this->bool('allow_voice_messages'),
            'file' => $this->bool('allow_file'),
            default => false,
        };
    }

    /**
     * @return array{bytes_used: int, files_count: int, bytes_limit: int, files_limit: int, bytes_remaining: int|null, files_remaining: int|null}
     */
    public function userQuotaStatus(User $user): array
    {
        $day = now()->toDateString();
        $row = MessengerUploadDaily::query()
            ->where('user_id', $user->id)
            ->whereDate('day', $day)
            ->first();

        $bytesUsed = (int) ($row?->bytes_used ?? 0);
        $filesCount = (int) ($row?->files_count ?? 0);
        $bytesLimit = $this->int('daily_upload_bytes');
        $filesLimit = $this->int('daily_upload_count');

        return [
            'bytes_used' => $bytesUsed,
            'files_count' => $filesCount,
            'bytes_limit' => $bytesLimit,
            'files_limit' => $filesLimit,
            'bytes_remaining' => $bytesLimit > 0 ? max(0, $bytesLimit - $bytesUsed) : null,
            'files_remaining' => $filesLimit > 0 ? max(0, $filesLimit - $filesCount) : null,
        ];
    }

    public function assertUploadAllowed(User $user, string $type, int $sizeBytes): void
    {
        if (! $this->isMediaTypeAllowed($type)) {
            throw new \InvalidArgumentException(
                $this->bool('uploads_enabled')
                    ? 'آپلود این نوع فایل مجاز نیست.'
                    : 'آپلود فایل در پیام‌رسان غیرفعال است.'
            );
        }

        $maxKb = $this->mediaMaxKb($type);
        if ($sizeBytes > $maxKb * 1024) {
            $mb = round($maxKb / 1024, 1);
            throw new \InvalidArgumentException("حجم فایل بیش از حد مجاز است (حداکثر {$mb} مگابایت).");
        }

        $quota = $this->userQuotaStatus($user);
        if ($quota['files_limit'] > 0 && $quota['files_count'] >= $quota['files_limit']) {
            throw new \InvalidArgumentException('سقف تعداد فایل آپلود روزانه شما پر شده است.');
        }
        if ($quota['bytes_limit'] > 0 && ($quota['bytes_used'] + $sizeBytes) > $quota['bytes_limit']) {
            throw new \InvalidArgumentException('سقف حجم آپلود روزانه شما پر شده است.');
        }
    }

    public function recordUpload(User $user, int $sizeBytes): void
    {
        $day = now()->toDateString();
        $row = MessengerUploadDaily::query()->firstOrCreate(
            ['user_id' => $user->id, 'day' => $day],
            ['bytes_used' => 0, 'files_count' => 0]
        );

        $row->increment('bytes_used', max(0, $sizeBytes));
        $row->increment('files_count');
    }

    public function setUserAccess(User $user, ?bool $enabled): void
    {
        $settings = MessengerSetting::query()->firstOrCreate(
            ['user_id' => $user->id],
            MessengerSetting::defaults()
        );
        $settings->access_enabled = $enabled;
        $settings->save();
    }

    protected function castIncoming(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => is_array($value) ? $value : json_decode((string) $value, true),
            default => $value === null ? null : (string) $value,
        };
    }
}
