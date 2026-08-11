<?php

namespace App\Http\Controllers\Api\Messenger;

use App\Http\Controllers\Controller;
use App\Models\MessengerMedia;
use App\Models\MessengerSticker;
use App\Models\MessengerStickerPack;
use App\Models\MessengerUserStickerPack;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StickerPackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $installedIds = MessengerUserStickerPack::query()
            ->where('user_id', $user->id)
            ->orderBy('sort_order')
            ->pluck('pack_id')
            ->all();

        $packs = MessengerStickerPack::query()
            ->with('stickers')
            ->where(function ($q) use ($user, $installedIds) {
                $q->where('user_id', $user->id)
                    ->orWhereIn('id', $installedIds ?: [0]);
            })
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'packs' => $packs->map(fn (MessengerStickerPack $p) => $p->toApiArray(true))->values(),
            'installed' => $packs->whereIn('id', $installedIds)->pluck('uuid')->values(),
        ]);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        // Any authenticated user may preview a pack from a chat sticker (Telegram-like).
        $pack = MessengerStickerPack::query()->with('stickers')->where('uuid', $uuid)->firstOrFail();

        return response()->json(['pack' => $pack->toApiArray(true)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'title_fa' => 'sometimes|nullable|string|max:120',
            'icon' => 'sometimes|nullable|string|max:32',
            'is_public' => 'sometimes|boolean',
            'stickers' => 'sometimes|array|max:120',
            'stickers.*.emoji' => 'sometimes|nullable|string|max:32',
            'stickers.*.file' => 'sometimes|file|image|max:2048|mimes:jpeg,jpg,png,webp,gif',
            'stickers.*.data_url' => 'sometimes|nullable|string|max:3500000',
        ]);

        /** @var User $user */
        $user = $request->user();

        $pack = DB::transaction(function () use ($data, $user, $request) {
            $pack = MessengerStickerPack::create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'title_fa' => $data['title_fa'] ?? $data['title'],
                'icon' => $data['icon'] ?? '⭐',
                'is_public' => array_key_exists('is_public', $data) ? (bool) $data['is_public'] : true,
                'is_builtin' => false,
                'sticker_count' => 0,
            ]);

            MessengerUserStickerPack::firstOrCreate(
                ['user_id' => $user->id, 'pack_id' => $pack->id],
                ['sort_order' => 0]
            );

            $files = $request->file('stickers') ?: [];
            $metaList = $data['stickers'] ?? [];
            $order = 0;
            foreach ($metaList as $i => $meta) {
                $file = is_array($files) ? ($files[$i]['file'] ?? $files[$i] ?? null) : null;
                if (! $file instanceof UploadedFile && ! empty($meta['data_url'])) {
                    $file = $this->dataUrlToUploadedFile((string) $meta['data_url'], 'sticker.png');
                }
                if (! $file instanceof UploadedFile) {
                    continue;
                }
                $this->storeStickerFile($pack, $file, $meta['emoji'] ?? '⭐', $order);
                $order++;
            }

            $pack->sticker_count = $pack->stickers()->count();
            if ($pack->sticker_count > 0 && ($data['icon'] ?? null) === null) {
                $first = $pack->stickers()->orderBy('sort_order')->first();
                if ($first?->emoji) {
                    $pack->icon = $first->emoji;
                }
            }
            $pack->save();

            return $pack->load('stickers');
        });

        return response()->json(['pack' => $pack->toApiArray(true)], 201);
    }

    public function addStickers(Request $request, string $uuid): JsonResponse
    {
        $pack = MessengerStickerPack::query()->where('uuid', $uuid)->firstOrFail();
        if ((int) $pack->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'stickers' => 'required|array|min:1|max:40',
            'stickers.*.emoji' => 'sometimes|nullable|string|max:32',
            'stickers.*.file' => 'sometimes|file|image|max:2048|mimes:jpeg,jpg,png,webp,gif',
            'stickers.*.data_url' => 'sometimes|nullable|string|max:3500000',
        ]);

        $files = $request->file('stickers') ?: [];
        $order = (int) $pack->stickers()->max('sort_order') + 1;
        foreach ($data['stickers'] as $i => $meta) {
            $file = is_array($files) ? ($files[$i]['file'] ?? $files[$i] ?? null) : null;
            if (! $file instanceof UploadedFile && ! empty($meta['data_url'])) {
                $file = $this->dataUrlToUploadedFile((string) $meta['data_url'], 'sticker.png');
            }
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $this->storeStickerFile($pack, $file, $meta['emoji'] ?? '⭐', $order);
            $order++;
        }
        $pack->sticker_count = $pack->stickers()->count();
        $pack->save();

        return response()->json(['pack' => $pack->fresh('stickers')->toApiArray(true)]);
    }

    public function updatePack(Request $request, string $uuid): JsonResponse
    {
        $pack = MessengerStickerPack::query()->where('uuid', $uuid)->firstOrFail();
        if ((int) $pack->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $data = $request->validate([
            'title' => 'sometimes|string|max:120',
            'title_fa' => 'sometimes|nullable|string|max:120',
            'icon' => 'sometimes|nullable|string|max:32',
            'is_public' => 'sometimes|boolean',
        ]);
        $pack->fill([
            'title' => $data['title'] ?? $pack->title,
            'title_fa' => array_key_exists('title_fa', $data) ? $data['title_fa'] : $pack->title_fa,
            'icon' => $data['icon'] ?? $pack->icon,
            'is_public' => array_key_exists('is_public', $data) ? (bool) $data['is_public'] : $pack->is_public,
        ]);
        $pack->save();

        return response()->json(['pack' => $pack->fresh('stickers')->toApiArray(true)]);
    }

    public function updateSticker(Request $request, string $stickerUuid): JsonResponse
    {
        $sticker = MessengerSticker::query()->with('pack')->where('uuid', $stickerUuid)->firstOrFail();
        if ((int) optional($sticker->pack)->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $data = $request->validate([
            'emoji' => 'sometimes|nullable|string|max:32',
            'file' => 'sometimes|file|image|max:2048|mimes:jpeg,jpg,png,webp,gif',
            'data_url' => 'sometimes|nullable|string|max:3500000',
        ]);
        if (array_key_exists('emoji', $data)) {
            $sticker->emoji = $data['emoji'];
        }
        $file = $request->file('file');
        if (! $file instanceof UploadedFile && ! empty($data['data_url'])) {
            $file = $this->dataUrlToUploadedFile((string) $data['data_url'], 'sticker.png');
        }
        if ($file instanceof UploadedFile) {
            $stored = $this->putStickerAsset($file);
            $media = $this->createStickerMedia($request->user()->id, $stored);
            if ($sticker->media_id) {
                $old = MessengerMedia::query()->find($sticker->media_id);
                if ($old) {
                    $old->dropRef(1);
                    if ($old->liveReferenceCount() === 0) {
                        $old->delete();
                    }
                }
            }
            $sticker->fill($stored);
            $sticker->media_id = $media->id;
            $sticker->kind = 'image';
        }
        $sticker->save();

        return response()->json(['sticker' => $sticker->fresh('pack')->toApiArray()]);
    }

    public function destroySticker(Request $request, string $stickerUuid): JsonResponse
    {
        $sticker = MessengerSticker::query()->with('pack')->where('uuid', $stickerUuid)->firstOrFail();
        $pack = $sticker->pack;
        if ((int) optional($pack)->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $mediaId = $sticker->media_id;
        $sticker->delete();
        if ($mediaId) {
            $media = MessengerMedia::query()->find($mediaId);
            if ($media) {
                $media->dropRef(1);
                if ($media->liveReferenceCount() === 0) {
                    $media->delete();
                }
            }
        }
        if ($pack) {
            $pack->sticker_count = $pack->stickers()->count();
            $pack->save();
        }

        return response()->json(['ok' => true]);
    }

    public function destroyPack(Request $request, string $uuid): JsonResponse
    {
        $pack = MessengerStickerPack::query()->where('uuid', $uuid)->firstOrFail();
        if ((int) $pack->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        MessengerUserStickerPack::query()->where('pack_id', $pack->id)->delete();
        $pack->delete();

        return response()->json(['ok' => true]);
    }

    public function install(Request $request, string $uuid): JsonResponse
    {
        $pack = MessengerStickerPack::query()->where('uuid', $uuid)->firstOrFail();
        if (! $pack->is_public && (int) $pack->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Pack not found'], 404);
        }
        MessengerUserStickerPack::firstOrCreate(
            ['user_id' => $request->user()->id, 'pack_id' => $pack->id],
            ['sort_order' => 0]
        );

        return response()->json(['pack' => $pack->load('stickers')->toApiArray(true)]);
    }

    public function uninstall(Request $request, string $uuid): JsonResponse
    {
        $pack = MessengerStickerPack::query()->where('uuid', $uuid)->firstOrFail();
        MessengerUserStickerPack::query()
            ->where('user_id', $request->user()->id)
            ->where('pack_id', $pack->id)
            ->delete();

        return response()->json(['ok' => true]);
    }

    private function storeStickerFile(MessengerStickerPack $pack, UploadedFile $file, string $emoji, int $order): MessengerSticker
    {
        $stored = $this->putStickerAsset($file);
        $media = $this->createStickerMedia((int) $pack->user_id, $stored);

        return MessengerSticker::create([
            'pack_id' => $pack->id,
            'media_id' => $media->id,
            'emoji' => $emoji ?: '⭐',
            'kind' => 'image',
            'path' => $stored['path'],
            'url' => $stored['url'],
            'mime' => $stored['mime'],
            'size' => $stored['size'],
            'width' => $stored['width'],
            'height' => $stored['height'],
            'sort_order' => $order,
        ]);
    }

    /**
     * @param  array{path:string,url:string,mime:string,size:int,width:?int,height:?int}  $stored
     */
    private function createStickerMedia(int $userId, array $stored): MessengerMedia
    {
        $ext = pathinfo($stored['path'], PATHINFO_EXTENSION) ?: null;
        $media = MessengerMedia::create([
            'user_id' => $userId,
            'kind' => 'sticker',
            'disk' => config('messenger.media.disk', 'static'),
            'path' => $stored['path'],
            'is_private' => false,
            'is_encrypted' => false,
            'url' => $stored['url'],
            'original_name' => basename($stored['path']),
            'extension' => $ext ? Str::lower($ext) : null,
            'mime' => $stored['mime'] ?? null,
            'size_bytes' => $stored['size'] ?? null,
            'width' => $stored['width'] ?? null,
            'height' => $stored['height'] ?? null,
            'ref_count' => 1,
        ]);

        return $media;
    }

    private function putStickerAsset(UploadedFile $file): array
    {
        $diskName = config('messenger.media.disk', 'static');
        $folder = 'images/messenger/stickers/'.date('Y/m/d');
        $path = Storage::disk($diskName)->putFile($folder, $file);
        if (! $path) {
            throw new \RuntimeException('Failed to store sticker');
        }
        $baseUrl = rtrim((string) config("filesystems.disks.{$diskName}.url", ''), '/');
        $url = $baseUrl.'/'.ltrim($path, '/');
        $w = null;
        $h = null;
        try {
            $size = @getimagesize($file->getRealPath());
            if (is_array($size)) {
                $w = $size[0] ?? null;
                $h = $size[1] ?? null;
            }
        } catch (\Throwable $e) {
            /* noop */
        }

        return [
            'path' => mb_substr($path, 0, 500),
            'url' => $url,
            'mime' => $file->getMimeType() ?: 'image/png',
            'size' => (int) $file->getSize(),
            'width' => $w,
            'height' => $h,
        ];
    }

    private function dataUrlToUploadedFile(string $dataUrl, string $name): ?UploadedFile
    {
        if (! preg_match('#^data:(image/[a-zA-Z0-9.+-]+);base64,#', $dataUrl, $m)) {
            return null;
        }
        $mime = $m[1];
        $raw = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
        if ($raw === false) {
            return null;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'stk');
        if ($tmp === false) {
            return null;
        }
        file_put_contents($tmp, $raw);
        $ext = match ($mime) {
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/jpeg', 'image/jpg' => 'jpg',
            default => 'png',
        };

        return new UploadedFile($tmp, $name.'.'.$ext, $mime, null, true);
    }
}
