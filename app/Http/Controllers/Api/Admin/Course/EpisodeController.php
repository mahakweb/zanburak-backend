<?php

namespace App\Http\Controllers\Api\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Section;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\UploadTokenService;

class EpisodeController extends Controller
{


    public function createEpisodeData(Course $course)
    {
        $course->load([
            'section.episode' => function ($query) {
                $query->select('id', 'section_id', 'title', 'slug', 'order')->orderBy('order');
            }
        ]);

        return response()->json([
            'message' => 'Success',
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'english_title' => $course->english_title,
                'slug' => $course->slug,
                'poster' => $course->poster,
                'type' => $course->type,
                'sections' => $course->section->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'title' => $section->title,
                        'english_title' => $section->english_title,
                        'slug' => $section->slug,
                        'episodes' => $section->episode->map(function ($ep) {
                            return [
                                'id' => $ep->id,
                                'title' => $ep->title,
                                'english_title' => $ep->english_title,
                                'slug' => $ep->slug,
                                'order' => $ep->order,
                            ];
                        })
                    ];
                }),
            ]
        ], 200);
    }

    public function getEpisodeForEdit(Course $course, Section $section, Episode $episode)
    {
        // Verify the episode belongs to the section and course
        if ($episode->section_id !== $section->id || $section->course_id !== $course->id) {
            return response()->json(['message' => 'Episode not found in this course section'], 404);
        }

        // Load episode with videos and attachments
        $episode->load(['videos', 'attachs']);

        // Load course with all sections and episodes for ordering context
        $course->load([
            'section.episode' => function ($query) {
                $query->select('id', 'section_id', 'title', 'slug', 'order')->orderBy('order');
            }
        ]);

        // Get episode video data (similar to course trailer)
        $rawVideo = $episode->videos
            ->where('type', 'raw')
            ->first();

        $videoUrl = null;
        $videoStatus = null;
        $videoId = null;
        if ($rawVideo) {
            $diskUrl = config("filesystems.disks.{$rawVideo->disk}.url");
            if ($diskUrl) {
                $videoUrl = rtrim($diskUrl, '/') . '/' . ltrim($rawVideo->path, '/');
            } else {
                $videoUrl = $rawVideo->path;
            }
            $videoStatus = $rawVideo->status ?? 'queued';
            $videoId = $rawVideo->id;
        }

        // Get episode attachment data
        $rowAttach = $episode->attachs->first();
        $attach = null;
        if ($rowAttach) {
            $attach = [
                'url' => $rowAttach->url,
                'size' => $this->urlDetails($rowAttach->url)['size']
            ];
        }

        return response()->json([
            'message' => 'Success',
            'episode' => [
                'id' => $episode->id,
                'title' => $episode->title,
                'english_title' => $episode->english_title,
                'slug' => $episode->slug,
                'description' => $episode->description,
                'meta_keywords' => $episode->meta_keywords,
                'order' => $episode->order,
                'publish' => $episode->publish,
                'lock' => $episode->lock,
                'publish_date' => $episode->publish_date,
                'section_id' => $episode->section_id,
                'video' => $videoUrl,
                'video_status' => $videoStatus,
                'video_id' => $videoId,
                'attach' => $attach,
            ],
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'english_title' => $course->english_title,
                'slug' => $course->slug,
                'poster' => $course->poster,
                'type' => $course->type,
                'sections' => $course->section->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'title' => $section->title,
                        'english_title' => $section->english_title,
                        'slug' => $section->slug,
                        'episodes' => $section->episode->map(function ($ep) {
                            return [
                                'id' => $ep->id,
                                'title' => $ep->title,
                                'english_title' => $ep->english_title,
                                'slug' => $ep->slug,
                                'order' => $ep->order,
                            ];
                        })
                    ];
                }),
            ]
        ], 200);
    }

    public function createNullEpisode(Request $request, Course $course)
    {
        $validator = Validator::make($request->all(), [
            'order' => ['required', 'numeric'],
            'section_id' => ['required', "exists:sections,id"],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validData = $validator->validated();
            $section = Section::find($request->input('section_id'));
            if (!$section || $section->course_id != $course->id) {
                return response()->json(['message' => 'Validation error!', 'errors' => ['section_id' => ['the selected section not belong to this course.']]], 422);
            }

            $episode = Episode::create([
                'section_id' => $validData['section_id'],
                'order' => $validData['order'],
                'publish' => false,
                'lock' => $course->type == 'free' ? false : true,
            ]);

            $this->reorderCourseEpisodes($course->id);

            return response()->json(['message' => 'Success', 'episode' => $episode], 200);
        }
    }

    public function updateEpisode(Request $request, Course $course)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'min:5', 'max:255'],
            'english_title' => ['required', 'min:5', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/'],
            'description' => ['nullable'],
            'meta_keywords' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $keywords = array_filter(array_map('trim', explode(',', $value)));
                        $count = count($keywords);
                        
                        if ($count < 3) {
                            $fail('کلمات کلیدی باید حداقل 3 کلمه باشد.');
                        }
                        
                        if ($count > 10) {
                            $fail('کلمات کلیدی نباید بیشتر از 10 کلمه باشد.');
                        }
                        
                        // Check for empty keywords
                        foreach ($keywords as $keyword) {
                            if (empty($keyword)) {
                                $fail('کلمات کلیدی نمی‌تواند خالی باشد.');
                                break;
                            }
                        }
                    }
                },
            ],
            'publish' => ['required', 'boolean'],
            'lock' => ['required', 'boolean'],
            'order' => ['required', 'numeric'],
            'publish_date' => ['nullable', "date"],
            'section_id' => ['required', "exists:sections,id"],
            'episode_id' => ['required', "exists:episodes,id"],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validData = $validator->validated();
            
            // Clean and format meta_keywords
            if (isset($validData['meta_keywords']) && $validData['meta_keywords']) {
                $keywords = array_filter(array_map('trim', explode(',', $validData['meta_keywords'])));
                $validData['meta_keywords'] = implode(', ', $keywords);
            } else {
                $validData['meta_keywords'] = null;
            }
            
            $section = Section::find($request->input('section_id'));
            if (!$section || $section->course_id != $course->id) {
                return response()->json(['message' => 'Validation error!', 'errors' => ['section_id' => ['the selected section not belong to this course.']]], 422);
            }

            [$minOrder, $maxOrder] = $this->validateEpisodeOrder($validData['section_id'], $validData['order']);

            if ($validData['order'] < $minOrder || $validData['order'] > $maxOrder) {
                return response()->json(['message' => 'Validation error!', 'errors' => ['order' => ["The selected order must be between {$minOrder} and {$maxOrder}."]]], 422);
            }

            $episode = Episode::find($validData['episode_id']);
            if ($episode) {
                $episode->update($validData);
                // if ($episode->order != $validData['order']) {
                    $this->reorderCourseEpisodes($course->id);
                // }

                return response()->json(['message' => 'Success, episode updated successfully.'], 200);
            }


        }
    }

    protected function validateEpisodeOrder(int $sectionId, int $order): array
    {
        $section = Section::with(['course.section.episode'])->findOrFail($sectionId);
        $sections = $section->course->section->sortBy('order')->values();

        $currentIndex = $sections->search(fn($sec) => $sec->id === $section->id);

        $minOrder = 1;
        for ($i = $currentIndex - 1; $i >= 0; $i--) {
            $prevOrders = $sections[$i]->episode->pluck('order')->all();
            if (!empty($prevOrders)) {
                $minOrder = max($prevOrders) + 1;
                break;
            }
        }

        $maxOrder = null;
        for ($i = $currentIndex + 1; $i < count($sections); $i++) {
            $nextOrders = $sections[$i]->episode->pluck('order')->all();
            if (!empty($nextOrders)) {
                $maxOrder = min($nextOrders);
                break;
            }
        }

        if (is_null($maxOrder)) {
            $allOrders = $sections->flatMap(fn($s) => $s->episode->pluck('order'))->all();
            $maxOrder = !empty($allOrders) ? max($allOrders) + 1 : 1;
        }

        return [$minOrder, $maxOrder];
    }

    protected function reorderCourseEpisodes(int $courseId): void
    {
        $episodes = Episode::whereHas('section', fn($q) => $q->where('course_id', $courseId))
            ->orderBy('order')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($episodes as $index => $episode) {
            $episode->update(['order' => $index + 1]);
        }
    }

    public function uploadAttachedFile(Request $request, Course $course)
    {
        $validator = Validator::make($request->all(), [
            'episode_id' => ['required', 'exists:episodes,id'],
            'filename' => ['required', 'string'],
            'mime' => ['required', 'string'],
            'size' => ['required', 'integer', 'min:1'],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $user = auth('api')->user();
            $validData = $validator->validated();
            $episode = Episode::findOrFail($validData['episode_id']);
            if (!$episode) {
                return response()->json(['message' => 'Error! episode not found'], 404);
            }
            if ($episode->section->course->id != $course->id) {
                return response()->json(['message' => 'Validation error!', 'errors' => ['episode_id' => ['the selected episode not belong to this course.']]], 422);
            }


            $disk = 'static';
            $folder = "attach/" . date('Y/m/d');
            $ext = pathinfo($validData['filename'], PATHINFO_EXTENSION);
            $generated = Str::uuid()->toString();
            $filePath = "{$folder}/{$generated}.{$ext}";

            $claims = [
                'sub' => 'upload',
                'type' => 'attachment',
                'disk' => $disk,
                'path' => $filePath,
                'mime' => $validData['mime'],
                'size' => (int) $validData['size'],
                'courseId' => $course->id,
                'episodeId' => $episode->id,
                'userId' => optional($user)->id,
            ];

            $tokenData = UploadTokenService::generate($claims);

            return response()->json([
                'message' => 'Upload initialized. Use worker to upload the file.',
                'uploadPath' => $filePath,
                'uploadToken' => $tokenData['token'],
                'workerUploadUrl' => rtrim(config('upload.worker_base_url'), '/') . '/api/upload/attachment',
                'expiresAt' => $tokenData['expires_at'],
            ], 200);

        }
    }

    public function removeAttachedFile($episode)
    {
        $attach = $episode->attachs->first();
        if ($attach) {
            $details = $this->urlDetails($attach->url);
            if ($details && Storage::disk($details['disk'])->exists($details['path'])) {
                Storage::disk($details['disk'])->delete($details['path']);
                $episode->attachs()->delete();
            }
        }
    }

    public function removeVideo($episode)
    {
        $videos = $episode->videos;
        if ($videos) {
            foreach ($videos as $vid) {
                $folderPath = dirname($vid->path);
                $storage = Storage::disk($vid->disk);
                if ($vid->type == 'raw' && Storage::disk($vid->disk)->exists($vid->path)) {
                    if ($storage->exists($folderPath)) {
                        $files = $storage->allFiles($folderPath);
                        foreach ($files as $file) {
                            $storage->delete($file);
                        }
                        $storage->deleteDirectory($folderPath);
                    }
                } else if (Storage::disk($vid->disk)->exists($vid->path)) {
                    Storage::disk($vid->disk)->delete($vid->path);
                    $storage->deleteDirectory($folderPath);
                }
            }
            $episode->videos()->delete();
            
            // Update episode total_time to 0 after removing videos
            $episode->update(['total_time' => 0]);
        }
    }

    public function removeFile(Request $request)
    {
        $episodeId = $request->input('episode_id');
        $fileType = $request->input('file_type');

        $episode = Episode::find($episodeId);
        if (!$episode) {
            return response()->json(['message' => "Error! episode not found."], 404);
        }
        $response = null;
        switch ($fileType) {
            case 'video':
                $this->removeVideo($episode);
                $response = ['message' => "Success, video of the episode has been deleted successfully."];
                break;
            case 'attached_file':
                $this->removeAttachedFile($episode);
                $response = ['message' => "Success, attached file of the episode has been deleted successfully."];
                break;
            default:
                return response()->json(['message' => "Error! Unknown file type.."], 422);
        }
        return response()->json($response, 200);
    }

    private function urlDetails($url)
    {
        if (!Str::is('http*://*', $url)) {
            return null;
        }

        foreach (config('filesystems.disks') as $disk => $config) {
            if (!isset($config['url'])) {
                continue;
            }

            $baseUrl = rtrim($config['url'], '/');
            if (str_starts_with($url, $baseUrl)) {
                $relativePath = ltrim(str_replace($baseUrl, '', $url), '/');
                return [
                    'domain' => $baseUrl,
                    'disk' => $disk,
                    'path' => $relativePath,
                    'size' => Storage::disk($disk)->size($relativePath),
                    'ext' => pathinfo($relativePath, PATHINFO_EXTENSION),
                    'url' => $url,
                ];
            }
        }

        return null;
    }

}