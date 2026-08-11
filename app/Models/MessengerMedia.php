<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MessengerMedia extends Model
{
    use SoftDeletes;

    protected $table = 'messenger_media';

    protected $fillable = [
        'uuid',
        'canonical_media_id',
        'user_id',
        'kind',
        'disk',
        'path',
        'thumb_path',
        'cover_path',
        'hls_path',
        'hls_status',
        'processing_status',
        'variants',
        'metadata',
        'is_private',
        'is_encrypted',
        'url',
        'original_name',
        'extension',
        'mime',
        'size_bytes',
        'sha256',
        'etag',
        'width',
        'height',
        'duration',
        'ref_count',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_encrypted' => 'boolean',
        'size_bytes' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'float',
        'ref_count' => 'integer',
        'variants' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $media) {
            if (! $media->uuid) {
                $media->uuid = (string) Str::uuid();
            }
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'media_id');
    }

    public function stickers()
    {
        return $this->hasMany(MessengerSticker::class, 'media_id');
    }

    public function canonical()
    {
        return $this->belongsTo(self::class, 'canonical_media_id');
    }

    public function aliases()
    {
        return $this->hasMany(self::class, 'canonical_media_id');
    }

    /**
     * Effective storage row (self or shared canonical).
     */
    public function storageRoot(): self
    {
        if ($this->canonical_media_id) {
            $this->loadMissing('canonical');

            return $this->canonical ?: $this;
        }

        return $this;
    }

    /**
     * Build the legacy-compatible meta fragment stored on messages (and used by the proxy).
     *
     * @return array<string, mixed>
     */
    public function toMessageMeta(): array
    {
        $root = $this->storageRoot();

        return array_filter([
            'media_id' => $this->id,
            'media_uuid' => $this->uuid,
            'disk' => $root->disk,
            'path' => $root->path,
            'thumb_path' => $root->thumb_path ?: $this->thumb_path,
            'cover_path' => $root->cover_path ?: $this->cover_path,
            'hls_path' => $root->hls_path,
            'hls_status' => $root->hls_status,
            'processing_status' => $root->processing_status,
            'variants' => $root->variants,
            'private' => (bool) $this->is_private,
            'encrypted' => (bool) $this->is_encrypted,
            'url' => $this->url,
            'mime' => $root->mime ?: $this->mime,
            'size' => $root->size_bytes ?: $this->size_bytes,
            'name' => $this->original_name,
            'ext' => $this->extension,
            'width' => $root->width ?: $this->width,
            'height' => $root->height ?: $this->height,
            'duration' => $root->duration ?: $this->duration,
            'sha256' => $root->sha256 ?: $this->sha256,
            'shared' => (bool) $this->canonical_media_id,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Create a media row from storeMediaFile() meta.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function createFromStoredMeta(?int $userId, string $kind, array $meta): self
    {
        return static::create([
            'user_id' => $userId,
            'kind' => $kind,
            'canonical_media_id' => isset($meta['canonical_media_id']) ? (int) $meta['canonical_media_id'] : null,
            'disk' => (string) ($meta['disk'] ?? config('messenger.media.disk', 'static')),
            'path' => (string) ($meta['path'] ?? ''),
            'thumb_path' => $meta['thumb_path'] ?? null,
            'cover_path' => $meta['cover_path'] ?? null,
            'hls_path' => $meta['hls_path'] ?? null,
            'hls_status' => $meta['hls_status'] ?? 'none',
            'processing_status' => $meta['processing_status'] ?? 'ready',
            'variants' => $meta['variants'] ?? null,
            'metadata' => $meta['metadata'] ?? null,
            'is_private' => array_key_exists('private', $meta) ? (bool) $meta['private'] : true,
            'is_encrypted' => ! empty($meta['encrypted']),
            'url' => $meta['url'] ?? null,
            'original_name' => $meta['name'] ?? null,
            'extension' => $meta['ext'] ?? null,
            'mime' => $meta['mime'] ?? null,
            'size_bytes' => isset($meta['size']) ? (int) $meta['size'] : null,
            'sha256' => $meta['sha256'] ?? null,
            'etag' => $meta['etag'] ?? ($meta['sha256'] ?? null),
            'width' => isset($meta['width']) ? (int) $meta['width'] : null,
            'height' => isset($meta['height']) ? (int) $meta['height'] : null,
            'duration' => isset($meta['duration']) ? (float) $meta['duration'] : null,
            'ref_count' => 0,
        ]);
    }

    /**
     * Find an existing non-encrypted blob to share (deduplication).
     */
    public static function findDedupCandidate(string $sha256, int $sizeBytes, string $kind): ?self
    {
        if ($sha256 === '' || $sizeBytes < 1) {
            return null;
        }

        return static::query()
            ->whereNull('canonical_media_id')
            ->where('is_encrypted', false)
            ->where('sha256', $sha256)
            ->where('size_bytes', $sizeBytes)
            ->where('kind', $kind)
            ->whereNotNull('path')
            ->orderByDesc('id')
            ->first();
    }

    public function bumpRef(int $by = 1): void
    {
        $this->newQuery()->whereKey($this->id)->increment('ref_count', $by);
        $this->ref_count = (int) $this->ref_count + $by;
    }

    public function dropRef(int $by = 1): void
    {
        $this->newQuery()->whereKey($this->id)->where('ref_count', '>', 0)->decrement('ref_count', $by);
        $this->refresh();
    }

    public function liveReferenceCount(): int
    {
        $messages = Message::query()->where('media_id', $this->id)->count();
        $stickers = MessengerSticker::query()->where('media_id', $this->id)->count();

        return $messages + $stickers;
    }

    public function supportsHls(): bool
    {
        $root = $this->storageRoot();

        return $root->hls_status === 'ready' && is_string($root->hls_path) && $root->hls_path !== '';
    }
}
