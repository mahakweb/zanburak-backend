<?php

namespace App\Jobs;

use App\Models\MessengerMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use FFMpeg\Format\Video\X264;

/**
 * Extract metadata, generate video thumbnails, and optionally produce HLS
 * adaptive streams for messenger chat videos (non-encrypted only).
 */
class ProcessMessengerMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 1800;

    public function __construct(
        public readonly int $mediaId,
    ) {}

    public function handle(): void
    {
        $media = MessengerMedia::query()->find($this->mediaId);
        if (! $media || $media->is_encrypted) {
            return;
        }

        // Dedup aliases inherit processing from the canonical row.
        if ($media->canonical_media_id) {
            $media->forceFill([
                'processing_status' => 'ready',
                'hls_status' => 'skipped',
            ])->saveQuietly();

            return;
        }

        $media->forceFill(['processing_status' => 'processing'])->saveQuietly();

        try {
            $this->extractMetadata($media);
            if ($media->kind === 'video') {
                $this->generateVideoThumb($media);
                $this->maybeBuildHls($media);
            } elseif (in_array($media->kind, ['audio', 'voice'], true)) {
                $this->extractAudioMetadata($media);
            }
            $media->forceFill(['processing_status' => 'ready'])->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('messenger.media_process_failed', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);
            $media->forceFill([
                'processing_status' => 'failed',
                'hls_status' => $media->hls_status === 'processing' ? 'failed' : $media->hls_status,
            ])->saveQuietly();
        }
    }

    protected function extractMetadata(MessengerMedia $media): void
    {
        $disk = $media->disk ?: config('messenger.media.disk', 'static');
        try {
            $ffmpeg = FFMpeg::fromDisk($disk)->open($media->path);
            $duration = null;
            try {
                $duration = $ffmpeg->getDurationInSeconds();
            } catch (\Throwable $e) {
                // optional
            }

            $meta = is_array($media->metadata) ? $media->metadata : [];
            $meta['probed_at'] = now()->toIso8601String();

            if ($media->kind === 'video') {
                try {
                    $stream = $ffmpeg->getVideoStream();
                    $dims = $stream?->getDimensions();
                    if ($dims) {
                        $media->width = $media->width ?: $dims->getWidth();
                        $media->height = $media->height ?: $dims->getHeight();
                        $meta['width'] = $dims->getWidth();
                        $meta['height'] = $dims->getHeight();
                    }
                    $meta['codec'] = method_exists($stream, 'get') ? ($stream->get('codec_name') ?? null) : null;
                } catch (\Throwable $e) {
                    // optional
                }
            }

            if ($duration !== null && $duration > 0) {
                $media->duration = $media->duration ?: round((float) $duration, 1);
                $meta['duration'] = round((float) $duration, 1);
            }

            if (! $media->etag && $media->sha256) {
                $media->etag = $media->sha256;
            } elseif (! $media->etag) {
                try {
                    $size = Storage::disk($disk)->size($media->path);
                    $media->etag = sha1($media->path.'|'.$size);
                } catch (\Throwable $e) {
                    // optional
                }
            }

            $media->metadata = $meta;
            $media->saveQuietly();
        } catch (\Throwable $e) {
            Log::info('messenger.media_probe_skip', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function extractAudioMetadata(MessengerMedia $media): void
    {
        // Duration already probed in extractMetadata when streams are audio-only.
        $meta = is_array($media->metadata) ? $media->metadata : [];
        $meta['kind'] = $media->kind;
        $media->metadata = $meta;
        $media->saveQuietly();
    }

    protected function generateVideoThumb(MessengerMedia $media): void
    {
        if ($media->thumb_path) {
            return;
        }

        $disk = $media->disk ?: config('messenger.media.disk', 'static');
        $folder = dirname($media->path);
        $thumbPath = $folder.'/'.Str::uuid()->toString().'_vthumb.jpg';

        try {
            FFMpeg::fromDisk($disk)
                ->open($media->path)
                ->getFrameFromSeconds(min(1, max(0, (float) ($media->duration ?: 1) * 0.1)))
                ->export()
                ->toDisk($disk)
                ->save($thumbPath);

            if (Storage::disk($disk)->exists($thumbPath)) {
                $media->forceFill(['thumb_path' => $thumbPath])->saveQuietly();
            }
        } catch (\Throwable $e) {
            Log::info('messenger.video_thumb_skip', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function maybeBuildHls(MessengerMedia $media): void
    {
        $enabled = (bool) config('messenger.media.hls.enabled', true);
        $minBytes = (int) config('messenger.media.hls.min_bytes', 2 * 1024 * 1024);
        if (! $enabled || (int) ($media->size_bytes ?? 0) < $minBytes) {
            $media->forceFill(['hls_status' => 'skipped'])->saveQuietly();

            return;
        }

        $media->forceFill(['hls_status' => 'processing'])->saveQuietly();

        $disk = $media->disk ?: config('messenger.media.disk', 'static');
        $hlsRoot = dirname($media->path).'/hls_'.$media->uuid;
        $masterPlaylist = $hlsRoot.'/playlist.m3u8';

        $maxHeight = (int) ($media->height ?: 720);
        $qualities = collect([720, 480, 360])
            ->filter(fn ($q) => $q <= max(360, $maxHeight))
            ->values();

        if ($qualities->isEmpty()) {
            $qualities = collect([360]);
        }

        set_time_limit(0);

        $exporter = FFMpeg::fromDisk($disk)
            ->open($media->path)
            ->exportForHLS()
            ->toDisk($disk)
            ->setSegmentLength((int) config('messenger.media.hls.segment_length', 6));

        $variants = [];
        foreach ($qualities as $quality) {
            $bitrate = match ($quality) {
                360 => 600,
                480 => 1000,
                720 => 1800,
                default => 1000,
            };
            $dimensions = match ($quality) {
                360 => [640, 360],
                480 => [854, 480],
                720 => [1280, 720],
                default => [640, 360],
            };
            [$width, $height] = $dimensions;
            $format = (new X264('aac'))->setKiloBitrate($bitrate);
            $exporter->addFormat($format, function ($mediaOp) use ($width, $height) {
                $mediaOp->scale($width, $height);
            }, "{$quality}p/media.m3u8");
            $variants[] = [
                'quality' => $quality,
                'height' => $height,
                'width' => $width,
                'bitrate_kbps' => $bitrate,
                'playlist' => "{$quality}p/media.m3u8",
            ];
        }

        $exporter->save($masterPlaylist);

        if (! Storage::disk($disk)->exists($masterPlaylist)) {
            $media->forceFill(['hls_status' => 'failed'])->saveQuietly();

            return;
        }

        $media->forceFill([
            'hls_path' => $masterPlaylist,
            'hls_status' => 'ready',
            'variants' => [
                'hls' => $variants,
                'progressive' => [
                    'mime' => $media->mime,
                    'path' => $media->path,
                ],
            ],
        ])->saveQuietly();
    }
}
