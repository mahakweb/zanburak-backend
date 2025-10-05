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

                return response()->json(['message' => 'Success, episode created successfully.'], 200);
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



    public function episodeStatus(Request $request, Course $course)
    {
        $validator = Validator::make($request->all(), [
            'episode_id' => ['required', 'exists:episodes,id'],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        }

        $episode = Episode::with(['videos', 'attachs'])->find($request->input('episode_id'));
        if (!$episode) {
            return response()->json(['message' => 'Error! episode not found'], 404);
        }
        if ($episode->section->course->id != $course->id) {
            return response()->json(['message' => 'Validation error!', 'errors' => ['episode_id' => ['the selected episode not belong to this course.']]], 422);
        }

        $raw = $episode->videos?->where('type', 'raw')->first();
        $stream = $episode->videos?->where('type', 'stream')->first();

        $status = 'pending';
        if ($stream) {
            $status = 'processed';
        } elseif ($raw) {
            $status = 'uploaded';
        }

        $videos = $episode->videos?->map(function ($vid) {
            return [
                'id' => $vid->id,
                'type' => $vid->type,
                'quality' => $vid->quality,
                'path' => $vid->path,
                'url' => Storage::disk($vid->disk)->url($vid->path),
                'disk' => $vid->disk,
                'duration' => $vid->duration,
                'status' => $vid->status ?? 'queued',
            ];
        })->values() ?? collect([]);

        $attach = null;
        $rowAttach = $episode->attachs?->first();
        if ($rowAttach) {
            $attach = [
                'url' => $rowAttach->url,
                'size' => $this->urlDetails($rowAttach->url)['size'] ?? null,
            ];
        }

        return response()->json([
            'message' => 'Success',
            'status' => $status,
            'videos' => $videos,
            'attach' => $attach,
        ], 200);
    }
}