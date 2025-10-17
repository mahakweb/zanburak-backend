<?php

namespace App\Http\Controllers\Api\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\UploadTokenService;

class VideoController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'episode_id' => 'nullable|integer|exists:episodes,id',
            'filename' => 'required|string',
            'mime' => 'required|string',
            'size' => 'required|integer|min:1',
        ]);

        $courseId = $request->input('course_id');
        $episodeId = $request->input('episode_id');
        $course = Course::findOrFail($courseId);

        if ($episodeId) {
            $episode = Episode::findOrFail($episodeId);
            if ($episode->section->course->id != $courseId) {
                return response()->json(['message' => 'Error! The episode does not belong to this course.'], 404);
            }
        }

        $disk = 'static';
        if ($episodeId) {
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

    private function getVideoDuration($filePath)
    {
        try {
            $output = shell_exec("ffprobe -v quiet -print_format json -show_format -show_streams \"$filePath\"");
            $data = json_decode($output, true);

            if (isset($data['format']['duration'])) {
                return round($data['format']['duration']);
            }

            return 0;
        } catch (\Exception $e) {
            Log::error("Error getting video duration: " . $e->getMessage());
            return 0;
        }
    }



    // Proxy to worker reprocess endpoint for backward compatibility
    public function process(\App\Models\Video $video)
    {
        try {
            $worker = rtrim(config('upload.worker_base_url'), '/');
            $url = $worker . '/api/process/' . $video->id;
            $client = new \GuzzleHttp\Client();
            $client->post($url, ['timeout' => 30]);
            return response()->json(['message' => 'Processing started on worker.'], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to start processing', 'error' => $e->getMessage()], 500);
        }
    }



    public function removeFile($model)
    {
        // File removal will be handled by the worker.
        return;
    }

}

