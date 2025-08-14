<?php

namespace App\Http\Controllers\Api\Admin\Course;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessVideo;
use App\Models\Course;
use App\Models\Episode;
use Illuminate\Http\Request;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'episode_id' => 'nullable|integer|exists:episodes,id',
            'video' => 'required|file|mimetypes:video/mp4,video/mkv',
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

        if ($episodeId)
            $this->removeFile(Episode::find($episodeId));
        else
            $this->removeFile($course);

        $file = $request->file('video');
        $disk = 'static';
        $folder = "raw/{$course->slug}" . ($episodeId ? "/{$episodeId}" : "");

        $extension = $file->getClientOriginalExtension();
        $hashName = md5_file($file->getRealPath()) . '.' . $extension;
        $filePath = "{$folder}/{$hashName}";

        $stream = fopen($file->getRealPath(), 'rb');
        Storage::disk($disk)->writeStream($filePath, $stream);
        fclose($stream);

        $duration = $this->getVideoDuration($file->getRealPath());

        $video = Video::create([
            'type' => 'raw',
            'path' => $filePath,
            'disk' => $disk,
            'duration' => $duration,
            'videoable_id' => $episodeId ? $episodeId : $courseId,
            'videoable_type' => $episodeId ? get_class($episode) : get_class($course),
        ]);

        return response()->json([
            'message' => 'File uploaded successfully.',
            'video_id' => $video->id,
            'path' => Storage::disk($disk)->url($filePath),
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
            \Log::error("Error getting video duration: " . $e->getMessage());
            return 0;
        }
    }



    public function process(Video $video)
    {
        if ($video->type !== 'raw') {
            return response()->json([
                'message' => 'Invalid video type for processing.',
            ], 400);
        }

        ProcessVideo::dispatch($video);

        return response()->json([
            'message' => 'Video processing ended.',
            'video_id' => $video->id,
        ], 200);
    }



    public function removeFile($model)
    {
        $videos = $model->videos;
        if ($videos) {
            foreach ($videos as $vid) {
                $folderPath = dirname($vid->path);
                $storage = Storage::disk($vid->disk);
                if ($vid->type == 'trailer' || $vid->type == 'stream' && Storage::disk($vid->disk)->exists($vid->path)) {
                    if ($storage->exists($folderPath)) {
                        $files = $storage->allFiles($folderPath);
                        foreach ($files as $file) {
                            $storage->delete($file);
                        }
                        // $storage->deleteDirectory($folderPath);
                    }
                } else if (Storage::disk($vid->disk)->exists($vid->path)) {
                    Storage::disk($vid->disk)->delete($vid->path);
                    // $storage->deleteDirectory($folderPath);
                }
            }
            $model->videos()->delete();
        }
    }
}

