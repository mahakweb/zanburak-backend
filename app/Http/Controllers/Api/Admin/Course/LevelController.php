<?php

namespace App\Http\Controllers\Api\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LevelController extends Controller
{

    public function levels(Request $request)
    {
        $levels = Level::with([
            'courses:id,level_id,title,english_title,slug,short_description,poster',
        ])->get();

        $data = $levels->map(function ($level) {
            return [
                'id' => $level->id,
                'title' => $level->title,
                'english_title' => $level->english_title,
                'slug' => $level->slug,
                'icon' => $level->icon,
                'description' => $level->description,
                'courses_count' => $level->courses->count(),
                'courses' => $level->courses->makeHidden('level_id'),
                'created_at' => $level->created_at,
                'updated_at' => $level->updated_at,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'levels' => $data,
        ], 200);
    }




    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'min:3', 'max:255', 'unique:levels,title'],
            'english_title' => ['required', 'min:3', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/', 'unique:levels,english_title'],
            'description' => ['nullable', 'min:10'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $user = auth('api')->user();
            $validData = $validator->validated();

            $level = Level::create($validData);

            return response()->json(['message' => "Success, level created successfully.", 'level' => $level], 200);

        }
    }


    public function update(Level $level, Request $request)
    {
        $user = auth('api')->user();

        if (!$level) {
            return response()->json(['message' => 'Error! level not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'min:3', 'max:255', Rule::unique('levels', 'title')->ignore($level->id)],
            'english_title' => ['required', 'min:3', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/', Rule::unique('levels', 'english_title')->ignore($level->id)],
            'description' => ['nullable', 'min:10'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validData = $validator->validated();
            $level->update($validData);

            return response()->json(['message' => "level updated successfully", 'level' => $level], 200);
        }
    }



    public function uploadIcon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', 'exists:categories,id'],
            'icon' => ['required', 'mimes:jpg,svg,png,jpeg,webp', 'max:5120'],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $user = auth('api')->user();
            $validData = $validator->validated();
            $category = Category::findOrFail($request->category_id);
            if (!$category) {
                return response()->json(['message' => 'Error! category not found'], 404);
            }
            $this->removeIcon($category);
            $file = $request->file('icon');
            $disk = 'static';
            $folder = "images/icon/category/" . date('Y/m/d');
            $filePath = $file->store($folder, $disk);
            $category->icon = Storage::disk($disk)->url($filePath);
            $category->save();
            return response()->json(['message' => "Icon uploaded successfully", 'icon' => $category->icon], 200);

        }
    }


    public function removeIcon($level)
    {
        if ($level->icon) {
            $disk = $this->urlDetails($level->icon)['disk'];
            $path = $this->urlDetails($level->icon)['path'];
            if ($level->icon && Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                $level->icon = null;
                $level->save();
            }
        }
    }
    public function urlDetails($url)
    {
        if (!Str::is('http*://*', $url)) {
            return;
        }
        $result = [
            'domain' => null,
            'disk' => null,
            'path' => null,
            'size' => null,
            'ext' => null,
            'url' => null,
        ];

        foreach (config('filesystems.disks') as $disk => $config) {
            if (!isset($config['url'])) {
                continue;
            }

            $baseUrl = rtrim($config['url'], '/');

            if (str_starts_with($url, $baseUrl)) {
                $relativePath = ltrim(str_replace($baseUrl, '', $url), '/');
                $result['domain'] = $baseUrl;
                $result['disk'] = $disk;
                $result['path'] = $relativePath;
                $result['size'] = Storage::disk($disk)->size($relativePath);
                $result['ext'] = explode('.', $relativePath)[1];
                $result['url'] = $url;
                break;
            }
        }
        return $result;
    }




    public function delete(Level $level, Request $request)
    {
        if (!$level) {
            return response()->json([
                'message' => 'Not found any level for delete',
            ], 404);
        }

        if ($level->courses()->exists()) {
            return response()->json([
                'message' => 'Cannot delete this level because it is still assigned to one or more courses.'
            ], 409); // Conflict
        }

        $this->removeIcon($level);
        $level->delete();

        return response()->json([
            'message' => 'Success, level deleted successfully',
        ], 200);
    }

}
