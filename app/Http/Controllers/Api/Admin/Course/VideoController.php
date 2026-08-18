<?php

namespace App\Http\Controllers\Api\Admin\Course;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesAdminCourses;
use App\Models\Course;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\UploadTokenService;

class VideoController extends Controller
{
    use AuthorizesAdminCourses;

    public function upload(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'episode_id' => 'nullable|integer|exists:episodes,id',
            'filename' => 'required|string',
            'mime' => 'required|string',
            'size' => 'required|integer|min:1',
            'storage_disk' => 'nullable|string|in:dl,static,media',
        ]);

        $courseId = $request->input('course_id');
        $episodeId = $request->input('episode_id');
        $course = Course::findOrFail($courseId);
        $this->contentScope()->authorizeAction('videos', 'upload', null);
        $this->authorizeCourse($course, 'view');

        if ($episodeId) {
            $episode = Episode::findOrFail($episodeId);
            if ($episode->section->course->id != $courseId) {
                return response()->json(['message' => 'Error! The episode does not belong to this course.'], 404);
            }
        }

        $disk = strtolower((string) $request->input('storage_disk', config('upload.video_disk', 'media')));
        if (!in_array($disk, ['dl', 'static', 'media'], true)) {
            $disk = 'media';
        }
        if (!config("filesystems.disks.{$disk}")) {
            return response()->json(['message' => "Storage disk [{$disk}] is not configured."], 422);
        }        if ($episodeId) {
            $folder = "raw/course/{$course->slug}/episodes/{$episodeId}";
        } else {
            $folder = "raw/course/{$course->slug}/trailer";
        }

        $ext = pathinfo($request->input('filename'), PATHINFO_EXTENSION);
        $generated = Str::uuid()->toString();
        $filePath = "{$folder}/{$generated}.{$ext}";

        $claims = [
            'sub' => 'upload',
            'type' => 'video',
            'disk' => $disk,
            'path' => $filePath,
            'mime' => $request->input('mime'),
            'size' => (int) $request->input('size'),
            'courseId' => $courseId,
            'episodeId' => $episodeId,
            'userId' => optional(auth('api')->user())->id,
        ];

        $tokenData = UploadTokenService::generate($claims);

        return response()->json([
            'message' => 'Upload initialized. Use worker to upload the file.',
            'uploadPath' => $filePath,
            'uploadToken' => $tokenData['token'],
            'workerUploadUrl' => rtrim(config('upload.worker_base_url'), '/') . '/api/upload/video',
            'expiresAt' => $tokenData['expires_at'],
        ], 200);
    }

    /**
     * Proxy to worker reprocess endpoint (authenticated via short-lived process JWT).
     */
    public function process(Request $request, \App\Models\Video $video)
    {
        $course = $this->resolveVideoCourse($video);
        if ($course) {
            $this->contentScope()->authorizeAction('videos', 'process', null);
            $this->authorizeCourse($course, 'view');
        } else {
            return response()->json(['message' => 'Video course could not be resolved.'], 404);
        }

        if ($video->type !== 'raw') {
            $raw = \App\Models\Video::query()
                ->where('videoable_id', $video->videoable_id)
                ->where('videoable_type', $video->videoable_type)
                ->where('type', 'raw')
                ->first();
            if (!$raw) {
                return response()->json(['message' => 'Raw video not found for reprocess.'], 404);
            }
            $video = $raw;
        }

        $claims = [
            'sub' => 'process',
            'type' => 'process',
            'videoId' => $video->id,
            'disk' => $video->disk,
            'path' => $video->path,
            'courseId' => $course->id,
            'userId' => optional(auth('api')->user())->id,
        ];

        if ($video->videoable_type === Episode::class || $video->videoable_type === 'App\\Models\\Episode') {
            $claims['episodeId'] = $video->videoable_id;
        }

        $tokenData = UploadTokenService::generate($claims);

        try {
            $worker = rtrim(config('upload.worker_base_url'), '/');
            $url = $worker . '/api/process/' . $video->id;
            $client = new \GuzzleHttp\Client();

            $normalizeList = function ($value): array {
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    $value = is_array($decoded) ? $decoded : array_filter(array_map('intval', explode(',', $value)));
                }
                if (!is_array($value)) {
                    return [];
                }
                return array_values(array_unique(array_filter(array_map('intval', $value))));
            };

            $qualities = $normalizeList($request->input('qualities', []));
            $streamQualities = $normalizeList($request->input('stream_qualities', $qualities));
            $downloadQualities = $normalizeList($request->input('download_qualities', $qualities));
            $outputs = $request->input('outputs', ['stream']);
            if (is_string($outputs)) {
                $decoded = json_decode($outputs, true);
                $outputs = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $outputs)));
            }
            if (!is_array($outputs) || $outputs === []) {
                $outputs = ['stream'];
            }

            $storageDisk = (string) $request->input('storage_disk', config('upload.video_disk', 'dl'));
            $watermark = $request->input('watermark');
            if (!is_array($watermark)) {
                $watermark = [
                    'enabled' => $request->boolean('watermark_enabled'),
                    'type' => (string) $request->input('watermark_type', 'text'),
                    'text' => (string) $request->input('watermark_text', ''),
                    'position' => (string) $request->input('watermark_position', 'bottom-right'),
                    'opacity' => (float) $request->input('watermark_opacity', 0.7),
                    'font_size' => (int) $request->input('watermark_font_size', 32),
                    'image_scale' => (float) $request->input('watermark_image_scale', 0.18),
                    'font' => (string) $request->input('watermark_font', 'tahoma'),
                    'font_color' => (string) $request->input('watermark_font_color', '#FFFFFF'),
                    'bg_enabled' => $request->boolean('watermark_bg_enabled'),
                    'bg_color' => (string) $request->input('watermark_bg_color', '#000000'),
                    'bg_opacity' => (float) $request->input('watermark_bg_opacity', 0.45),
                    'bg_padding' => (int) $request->input('watermark_bg_padding', 10),
                    'bg_radius' => (int) $request->input('watermark_bg_radius', 8),
                    'image_radius' => (int) $request->input('watermark_image_radius', 0),
                    'border_enabled' => $request->boolean('watermark_border_enabled', true),
                    'border_color' => (string) $request->input('watermark_border_color', '#000000'),
                    'border_width' => (int) $request->input('watermark_border_width', 2),
                ];
            }

            $commonHeaders = [
                'Authorization' => 'Bearer ' . $tokenData['token'],
                'Accept' => 'application/json',
            ];

            if ($request->hasFile('watermark_image')) {
                $file = $request->file('watermark_image');
                $multipart = [
                    ['name' => 'force', 'contents' => $request->boolean('force') ? '1' : '0'],
                    ['name' => 'storage_disk', 'contents' => $storageDisk],
                    ['name' => 'watermark_enabled', 'contents' => !empty($watermark['enabled']) ? '1' : '0'],
                    ['name' => 'watermark_type', 'contents' => (string) ($watermark['type'] ?? 'image')],
                    ['name' => 'watermark_text', 'contents' => (string) ($watermark['text'] ?? '')],
                    ['name' => 'watermark_position', 'contents' => (string) ($watermark['position'] ?? 'bottom-right')],
                    ['name' => 'watermark_opacity', 'contents' => (string) ($watermark['opacity'] ?? 0.7)],
                    ['name' => 'watermark_font_size', 'contents' => (string) ($watermark['font_size'] ?? 32)],
                    ['name' => 'watermark_image_scale', 'contents' => (string) ($watermark['image_scale'] ?? 0.18)],
                    ['name' => 'watermark_font', 'contents' => (string) ($watermark['font'] ?? 'tahoma')],
                    ['name' => 'watermark_font_color', 'contents' => (string) ($watermark['font_color'] ?? '#FFFFFF')],
                    ['name' => 'watermark_bg_enabled', 'contents' => !empty($watermark['bg_enabled']) ? '1' : '0'],
                    ['name' => 'watermark_bg_color', 'contents' => (string) ($watermark['bg_color'] ?? '#000000')],
                    ['name' => 'watermark_bg_opacity', 'contents' => (string) ($watermark['bg_opacity'] ?? 0.45)],
                    ['name' => 'watermark_bg_padding', 'contents' => (string) ($watermark['bg_padding'] ?? 10)],
                    ['name' => 'watermark_bg_radius', 'contents' => (string) ($watermark['bg_radius'] ?? 8)],
                    ['name' => 'watermark_image_radius', 'contents' => (string) ($watermark['image_radius'] ?? 0)],
                    ['name' => 'watermark_border_enabled', 'contents' => (($watermark['border_enabled'] ?? true) ? '1' : '0')],
                    ['name' => 'watermark_border_color', 'contents' => (string) ($watermark['border_color'] ?? '#000000')],
                    ['name' => 'watermark_border_width', 'contents' => (string) ($watermark['border_width'] ?? 2)],
                    [
                        'name' => 'watermark_image',
                        'contents' => fopen($file->getRealPath(), 'r'),
                        'filename' => $file->getClientOriginalName() ?: 'watermark.png',
                    ],
                ];
                foreach ($qualities as $q) {
                    $multipart[] = ['name' => 'qualities[]', 'contents' => (string) $q];
                }
                foreach ($streamQualities as $q) {
                    $multipart[] = ['name' => 'stream_qualities[]', 'contents' => (string) $q];
                }
                foreach ($downloadQualities as $q) {
                    $multipart[] = ['name' => 'download_qualities[]', 'contents' => (string) $q];
                }
                foreach ($outputs as $o) {
                    $multipart[] = ['name' => 'outputs[]', 'contents' => (string) $o];
                }

                $response = $client->post($url, [
                    'timeout' => 60,
                    'headers' => $commonHeaders,
                    'multipart' => $multipart,
                    'http_errors' => false,
                ]);
            } else {
                $response = $client->post($url, [
                    'timeout' => 30,
                    'headers' => $commonHeaders,
                    'json' => [
                        'force' => $request->boolean('force'),
                        'qualities' => $qualities,
                        'stream_qualities' => $streamQualities,
                        'download_qualities' => $downloadQualities,
                        'outputs' => $outputs,
                        'storage_disk' => $storageDisk,
                        'watermark' => $watermark,
                    ],
                    'http_errors' => false,
                ]);
            }

            $status = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true) ?: [];

            if ($status >= 200 && $status < 300) {
                return response()->json([
                    'message' => $body['message'] ?? 'Processing started on worker.',
                    'video_id' => $video->id,
                    'status' => $body['status'] ?? 'queued',
                ], 200);
            }

            return response()->json([
                'message' => $body['message'] ?? 'Failed to start processing',
            ], $status >= 400 ? $status : 500);
        } catch (\Throwable $e) {
            Log::error('Failed to proxy video process to worker', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to start processing'], 500);
        }
    }

    /**
     * Proxy cancel of in-flight / queued processing to the worker.
     */
    public function cancel(Request $request, \App\Models\Video $video)
    {
        $course = $this->resolveVideoCourse($video);
        if ($course) {
            $this->contentScope()->authorizeAction('videos', 'process', null);
            $this->authorizeCourse($course, 'view');
        } else {
            return response()->json(['message' => 'Video course could not be resolved.'], 404);
        }

        if ($video->type !== 'raw') {
            $raw = \App\Models\Video::query()
                ->where('videoable_id', $video->videoable_id)
                ->where('videoable_type', $video->videoable_type)
                ->where('type', 'raw')
                ->first();
            if (!$raw) {
                return response()->json(['message' => 'Raw video not found for cancel.'], 404);
            }
            $video = $raw;
        }

        $claims = [
            'sub' => 'process',
            'type' => 'process',
            'videoId' => $video->id,
            'disk' => $video->disk,
            'path' => $video->path,
            'courseId' => $course->id,
            'userId' => optional(auth('api')->user())->id,
        ];

        if ($video->videoable_type === Episode::class || $video->videoable_type === 'App\\Models\\Episode') {
            $claims['episodeId'] = $video->videoable_id;
        }

        $tokenData = UploadTokenService::generate($claims);

        try {
            $worker = rtrim(config('upload.worker_base_url'), '/');
            $url = $worker . '/api/process/' . $video->id . '/cancel';
            $client = new \GuzzleHttp\Client();
            $response = $client->post($url, [
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $tokenData['token'],
                    'Accept' => 'application/json',
                ],
                'http_errors' => false,
            ]);

            $status = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true) ?: [];

            if ($status >= 200 && $status < 300) {
                return response()->json([
                    'message' => $body['message'] ?? 'Processing cancelled.',
                    'video_id' => $video->id,
                    'status' => $body['status'] ?? 'uploaded',
                ], 200);
            }

            return response()->json([
                'message' => $body['message'] ?? 'Failed to cancel processing',
            ], $status >= 400 ? $status : 500);
        } catch (\Throwable $e) {
            Log::error('Failed to proxy video cancel to worker', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to cancel processing'], 500);
        }
    }

    /**
     * Read processing status from the shared DB (source of truth).
     */
    public function status(\App\Models\Video $video)
    {
        $course = $this->resolveVideoCourse($video);
        if ($course) {
            $this->contentScope()->authorizeAction('videos', 'upload', null);
            $this->authorizeCourse($course, 'view');
        } else {
            return response()->json(['message' => 'Video course could not be resolved.'], 404);
        }

        $raw = $video;
        if ($video->type !== 'raw') {
            $raw = \App\Models\Video::query()
                ->where('videoable_id', $video->videoable_id)
                ->where('videoable_type', $video->videoable_type)
                ->where('type', 'raw')
                ->first();
        }

        if (!$raw) {
            return response()->json(['message' => 'Raw video not found'], 404);
        }

        $status = $raw->status ?? 'pending';
        $progress = $raw->progress;

        if ($status === 'cancelled') {
            $status = 'uploaded';
            $progress = null;
        }

        // Normalize: progress advancing means encoding is live even if status lagged on "queued".
        if (in_array($status, ['queued', 'uploaded', 'pending'], true) && (int) ($progress ?? 0) > 0) {
            $status = 'processing';
        }

        return response()->json([
            'video_id' => $raw->id,
            'status' => $status,
            'progress' => $progress,
            'error_message' => $status === 'failed'
                ? ($raw->error_message ?: 'پردازش ویدیو ناموفق بود.')
                : null,
        ], 200);
    }

    public function removeFile($model)
    {
        // File removal will be handled by the worker.
        return;
    }

    protected function resolveVideoCourse(\App\Models\Video $video): ?Course
    {
        $video->loadMissing('videoable');

        if ($video->videoable instanceof Course) {
            return $video->videoable;
        }

        if ($video->videoable instanceof Episode) {
            $video->videoable->loadMissing('section.course');

            return $video->videoable->section?->course;
        }

        return null;
    }
}
