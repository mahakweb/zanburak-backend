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
        'auto_download',
        'auto_play',
        // Legacy flat columns kept for older clients / migrations.
        'auto_download_photos',
        'auto_download_videos',
        'auto_download_files',
        'auto_download_voice',
        'auto_download_audio',
        'wallpaper',
        'wallpaper_config',
        'theme',
        'locale',
        'show_online',
        'show_last_seen',
        'show_phone',
        'show_email',
        'privacy_last_seen',
        'privacy_online',
        'privacy_profile_photo',
        'privacy_bio',
        'privacy_phone',
    ];

    protected $casts = [
        'enter_to_send' => 'boolean',
        'quote_with_title' => 'boolean',
        'forward_tap_to_chat' => 'boolean',
        'auto_download' => 'array',
        'auto_play' => 'array',
        'auto_download_photos' => 'boolean',
        'auto_download_videos' => 'boolean',
        'auto_download_files' => 'boolean',
        'auto_download_voice' => 'boolean',
        'auto_download_audio' => 'boolean',
        'wallpaper_config' => 'array',
        'show_online' => 'boolean',
        'show_last_seen' => 'boolean',
        'show_phone' => 'boolean',
        'show_email' => 'boolean',
    ];

    public const MEDIA_KEYS = ['photos', 'videos', 'files', 'voice', 'audio'];

    public const CONTEXTS = ['private', 'groups', 'channels'];

    public const PRIVACY_RULES = ['everybody', 'contacts', 'nobody'];

    public const PRIVACY_KEYS = ['last_seen', 'online', 'profile_photo', 'bio', 'phone'];

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

    public static function emptyMediaFlags(): array
    {
        return [
            'photos' => false,
            'videos' => false,
            'files' => false,
            'voice' => false,
            'audio' => false,
        ];
    }

    public static function defaultAutoDownload(): array
    {
        $empty = self::emptyMediaFlags();

        return [
            'private' => $empty,
            'groups' => $empty,
            'channels' => $empty,
        ];
    }

    public static function defaultAutoPlay(): array
    {
        return [
            'gifs' => true,
            'videos' => false,
        ];
    }

    public static function defaults(): array
    {
        return [
            'enter_to_send' => true,
            'quote_with_title' => true,
            'forward_tap_to_chat' => false,
            'auto_download' => self::defaultAutoDownload(),
            'auto_play' => self::defaultAutoPlay(),
            'auto_download_photos' => false,
            'auto_download_videos' => false,
            'auto_download_files' => false,
            'auto_download_voice' => false,
            'auto_download_audio' => false,
            'wallpaper' => 'default',
            'theme' => null,
            'locale' => null,
            'show_online' => true,
            'show_last_seen' => true,
            'show_phone' => false,
            'show_email' => false,
            'privacy_last_seen' => 'everybody',
            'privacy_online' => 'everybody',
            'privacy_profile_photo' => 'everybody',
            'privacy_bio' => 'everybody',
            'privacy_phone' => 'nobody',
        ];
    }

    /** Normalize + fill missing keys for API responses. */
    public function resolvedAutoDownload(): array
    {
        $base = self::defaultAutoDownload();
        $raw = is_array($this->auto_download) ? $this->auto_download : [];

        // Legacy flat columns → seed all contexts when nested JSON is empty.
        if ($raw === [] && (
            $this->auto_download_photos !== null
            || $this->auto_download_videos !== null
            || $this->auto_download_files !== null
            || $this->auto_download_voice !== null
            || $this->auto_download_audio !== null
        )) {
            $legacy = [
                'photos' => (bool) $this->auto_download_photos,
                'videos' => (bool) $this->auto_download_videos,
                'files' => (bool) $this->auto_download_files,
                'voice' => (bool) $this->auto_download_voice,
                'audio' => (bool) $this->auto_download_audio,
            ];
            foreach (self::CONTEXTS as $ctx) {
                $base[$ctx] = $legacy;
            }

            return $base;
        }

        foreach (self::CONTEXTS as $ctx) {
            $slice = is_array($raw[$ctx] ?? null) ? $raw[$ctx] : [];
            foreach (self::MEDIA_KEYS as $key) {
                $base[$ctx][$key] = (bool) ($slice[$key] ?? false);
            }
        }

        return $base;
    }

    public function resolvedAutoPlay(): array
    {
        $base = self::defaultAutoPlay();
        $raw = is_array($this->auto_play) ? $this->auto_play : [];
        $base['gifs'] = (bool) ($raw['gifs'] ?? true);
        $base['videos'] = (bool) ($raw['videos'] ?? false);

        return $base;
    }

    /**
     * Merge a partial auto_download update into the stored JSON.
     *
     * @param  array<string, mixed>  $incoming
     */
    public static function mergeAutoDownload(?array $current, array $incoming): array
    {
        $out = is_array($current) && $current !== []
            ? $current
            : self::defaultAutoDownload();

        foreach (self::CONTEXTS as $ctx) {
            if (! isset($incoming[$ctx]) || ! is_array($incoming[$ctx])) {
                continue;
            }
            if (! isset($out[$ctx]) || ! is_array($out[$ctx])) {
                $out[$ctx] = self::emptyMediaFlags();
            }
            foreach (self::MEDIA_KEYS as $key) {
                if (array_key_exists($key, $incoming[$ctx])) {
                    $out[$ctx][$key] = (bool) $incoming[$ctx][$key];
                }
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $incoming
     */
    public static function mergeAutoPlay(?array $current, array $incoming): array
    {
        $out = is_array($current) && $current !== []
            ? $current
            : self::defaultAutoPlay();
        foreach (['gifs', 'videos'] as $key) {
            if (array_key_exists($key, $incoming)) {
                $out[$key] = (bool) $incoming[$key];
            }
        }

        return $out;
    }
}
