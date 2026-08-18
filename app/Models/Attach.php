<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attach extends Model
{
    use HasFactory;
    protected $fillable = [
        'attachable_id',
        'attachable_type',
        'title',
        'url'
    ];


    public function deleteMediaFiles()
    {
        foreach (['url'] as $field) {
            $url = $this->{$field};

            if (!$url) {
                continue;
            }

            foreach (config('filesystems.disks') as $disk => $config) {
                if (!isset($config['url'])) {
                    continue;
                }

                $baseUrl = rtrim($config['url'], '/');

                if (str_starts_with($url, $baseUrl)) {
                    $relativePath = ltrim(str_replace($baseUrl, '', $url), '/');
                    Storage::disk($disk)->delete($relativePath);
                    break;
                }
            }
        }
    }



    /**
     * Get the parent attachable model (course , section , episode).
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function isUuidLikeName(?string $value): bool
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return true;
        }
        $base = preg_replace('/\.(pdf|txt|zip|csv|png|jpe?g|gif|webp|docx?|xlsx?|pptx?|docm|xlsm|pptm)$/i', '', $raw) ?? $raw;

        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $base);
    }

    public static function prettyTitle(?string $filename, string $fallback = 'فایل پیوست'): string
    {
        $name = str_replace('\\', '/', (string) $filename);
        $base = pathinfo(basename($name), PATHINFO_FILENAME);
        $base = trim(preg_replace('/[_\s]+/u', ' ', $base) ?? '');
        if ($base === '' || self::isUuidLikeName($base)) {
            return $fallback;
        }

        return mb_substr($base, 0, 255);
    }

    public function displayTitle(): string
    {
        $title = trim((string) $this->title);
        if ($title !== '' && !self::isUuidLikeName($title)) {
            return self::prettyTitle($title, $title);
        }

        return self::prettyTitle($this->url);
    }

    public function fileExtension(): string
    {
        $path = parse_url((string) $this->url, PHP_URL_PATH) ?: (string) $this->url;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext !== '') {
            return $ext;
        }
        $fromTitle = strtolower(pathinfo((string) $this->title, PATHINFO_EXTENSION));

        return $fromTitle;
    }
}
