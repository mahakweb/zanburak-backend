<?php

namespace App\Http\Resources\Messenger;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rawMeta = is_array($this->meta) ? $this->meta : [];
        if ($this->relationLoaded('media') && $this->media) {
            $rawMeta = array_merge($this->media->toMessageMeta(), $rawMeta);
        }
        $meta = $this->sanitizeMetaForClient($rawMeta ?: null);

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'user_id' => $this->user_id,
            'client_id' => $this->client_id,
            'body' => $this->body,
            'type' => $this->type,
            'media_id' => $this->media_id,
            'is_encrypted' => (bool) ($this->is_encrypted ?? false),
            'sender_device_id' => $this->sender_device_id,
            'e2e' => $this->e2e,
            'is_silent' => (bool) ($this->is_silent ?? false),
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'auto_delete_at' => $this->auto_delete_at?->toIso8601String(),
            'view_count' => (int) ($this->view_count ?? 0),
            'mentions' => $this->mentions,
            'meta' => $meta,
            'forward_from_chat' => $this->when(
                is_array($this->meta) && ! empty($this->meta['fwd_chat']),
                fn () => $this->meta['fwd_chat']
            ),
            'post_author' => $this->when(
                is_array($this->meta) && ! empty($this->meta['post_author']),
                fn () => $this->meta['post_author']
            ),
            'reply_to_id' => $this->reply_to_id,
            'reply_show_title' => (bool) $this->reply_show_title,
            'read_at' => $this->read_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'edited_at' => $this->edited_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'user' => new UserBriefResource($this->whenLoaded('user')),
            'reactions' => $this->when(
                $this->relationLoaded('reactions'),
                function () {
                    return collect($this->reactions)
                        ->groupBy('emoji')
                        ->map(fn ($rows, $emoji) => [
                            'emoji' => $emoji,
                            'count' => $rows->count(),
                            'me' => $rows->contains(fn ($r) => (int) $r->user_id === (int) optional(request()->user())->id),
                        ])
                        ->values();
                }
            ),
            'forwarded_from' => $this->whenLoaded('forwardedFromUser', fn () => $this->forwardedFromUser
                ? new UserBriefResource($this->forwardedFromUser)
                : null),
            'reply_to' => $this->whenLoaded('replyTo', function () {
                if (! $this->replyTo) {
                    return null;
                }

                $replyRaw = is_array($this->replyTo->meta) ? $this->replyTo->meta : [];
                if ($this->replyTo->relationLoaded('media') && $this->replyTo->media) {
                    $replyRaw = array_merge($this->replyTo->media->toMessageMeta(), $replyRaw);
                }
                $replyMeta = $this->replyTo->trashed()
                    ? null
                    : $this->sanitizeMetaForClient($replyRaw ?: null, $this->replyTo->id);

                return [
                    'id' => $this->replyTo->id,
                    'user_id' => $this->replyTo->user_id,
                    'body' => $this->replyTo->trashed() ? null : $this->replyTo->body,
                    'type' => $this->replyTo->trashed() ? null : $this->replyTo->type,
                    'media_id' => $this->replyTo->trashed() ? null : $this->replyTo->media_id,
                    'is_encrypted' => $this->replyTo->trashed() ? false : (bool) ($this->replyTo->is_encrypted ?? false),
                    'e2e' => $this->replyTo->trashed() ? null : $this->replyTo->e2e,
                    'sender_device_id' => $this->replyTo->trashed() ? null : $this->replyTo->sender_device_id,
                    'meta' => $replyMeta,
                    'deleted' => $this->replyTo->trashed(),
                    'user' => $this->replyTo->relationLoaded('user') && $this->replyTo->user
                        ? new UserBriefResource($this->replyTo->user)
                        : null,
                ];
            }),
        ];
    }

    /**
     * Never leak raw storage paths or public CDN URLs for chat media.
     * Clients always fetch via authenticated /messenger/media/{id}.
     */
    protected function sanitizeMetaForClient(?array $meta, ?int $messageId = null): ?array
    {
        if ($meta === null) {
            return null;
        }

        $id = $messageId ?? $this->id;
        $hasMedia = ! empty($meta['path']) || ! empty($meta['url']) || ! empty($meta['thumb_path']) || ! empty($meta['thumb_url']) || ! empty($meta['media_id']);

        $out = $meta;

        unset($out['_withheld']);

        // Strip absolute storage locations from the client payload.
        unset($out['path'], $out['thumb_path'], $out['cover_path'], $out['hls_path'], $out['disk']);

        // Keep media_id so clients can forward / re-send without re-upload.
        if (! empty($meta['media_id'])) {
            $out['media_id'] = (int) $meta['media_id'];
        }
        if (! empty($meta['media_uuid'])) {
            $out['media_uuid'] = (string) $meta['media_uuid'];
        }

        if ($hasMedia && $id) {
            $publicStickerUrl = null;
            if (! empty($meta['sticker'])
                && ! empty($meta['url'])
                && preg_match('#^https?://#i', (string) $meta['url'])
                && empty($meta['encrypted'])
                && (empty($meta['private']) || $meta['private'] === false)
            ) {
                $publicStickerUrl = (string) $meta['url'];
            }

            $base = url('/api/messenger/media/'.$id);
            $out['url'] = $base;
            $out['stream'] = [
                'progressive' => true,
                'range' => empty($meta['encrypted']),
                'hls' => ($meta['hls_status'] ?? null) === 'ready',
            ];
            // Public stickers also keep CDN url for clients that can load it directly.
            if ($publicStickerUrl) {
                $out['cdn_url'] = $publicStickerUrl;
            }
            if (! empty($meta['thumb_path']) || ! empty($meta['thumb_url'])) {
                $out['thumb_url'] = $base.'?v=thumb';
            } else {
                unset($out['thumb_url']);
            }
            if (! empty($meta['cover_path']) || ! empty($meta['cover_url'])) {
                $out['cover_url'] = $base.'?v=cover';
            } else {
                unset($out['cover_url']);
            }
            if (($meta['hls_status'] ?? null) === 'ready') {
                $out['hls_url'] = $base.'?v=hls';
                $out['hls_status'] = 'ready';
                if (! empty($meta['variants']['hls']) && is_array($meta['variants']['hls'])) {
                    $out['qualities'] = collect($meta['variants']['hls'])
                        ->map(fn ($q) => [
                            'height' => (int) ($q['height'] ?? $q['quality'] ?? 0),
                            'label' => (($q['quality'] ?? $q['height'] ?? '').'p'),
                            'bitrate_kbps' => $q['bitrate_kbps'] ?? null,
                        ])
                        ->values()
                        ->all();
                }
            } elseif (! empty($meta['hls_status'])) {
                $out['hls_status'] = (string) $meta['hls_status'];
            }
            if (! empty($meta['processing_status'])) {
                $out['processing_status'] = (string) $meta['processing_status'];
            }
            if (! empty($meta['shared'])) {
                $out['shared'] = true;
            }
            $out['private'] = $publicStickerUrl ? false : true;
        }

        // Encrypted media: hide cleartext filename hints if present.
        if (! empty($meta['encrypted']) || ! empty($this->is_encrypted)) {
            $out['encrypted'] = true;
            unset($out['name'], $out['ext']);
        }

        return $out;
    }
}
