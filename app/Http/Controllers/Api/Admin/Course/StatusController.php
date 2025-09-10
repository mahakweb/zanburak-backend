<?php

namespace App\Http\Controllers\Api\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StatusController extends Controller
{

    public function statuses(Request $request)
    {
        $statuses = Status::with([
            'courses:id,status_id,title,english_title,slug,short_description,poster',
        ])->get();

        $data = $statuses->map(function ($status) {
            return [
                'id' => $status->id,
                'title' => $status->title,
                'english_title' => $status->english_title,
                'slug' => $status->slug,
                'icon' => $status->icon,
                'description' => $status->description,
                'courses_count' => $status->courses->count(),
                'courses' => $status->courses->makeHidden('status_id'),
                'created_at' => $status->created_at,
                'updated_at' => $status->updated_at,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'statuses' => $data,
        ], 200);
    }




    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'min:3', 'max:255', 'unique:statuses,title'],
            'english_title' => ['required', 'min:3', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/', 'unique:statuses,english_title'],
            'description' => ['nullable', 'min:10'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $user = auth('api')->user();
            $validData = $validator->validated();

            $status = Status::create($validData);

            return response()->json(['message' => "Success, status created successfully.", 'status' => $status], 200);

        }
    }


    public function update(Status $status, Request $request)
    {
        $user = auth('api')->user();

        if (!$status) {
            return response()->json(['message' => 'Error! status not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'min:3', 'max:255', Rule::unique('statuses', 'title')->ignore($status->id)],
            'english_title' => ['required', 'min:3', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/', Rule::unique('statuses', 'english_title')->ignore($status->id)],
            'description' => ['nullable', 'min:10'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validData = $validator->validated();
            $status->update($validData);

            return response()->json(['message' => "status updated successfully", 'status' => $status], 200);
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


    public function removeIcon($status)
    {
        if ($status->icon) {
            $disk = $this->urlDetails($status->icon)['disk'];
            $path = $this->urlDetails($status->icon)['path'];
            if ($status->icon && Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                $status->icon = null;
                $status->save();
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




    public function delete(Status $status, Request $request)
    {
        if (!$status) {
            return response()->json([
                'message' => 'Not found any status for delete',
            ], 404);
        }

        if ($status->courses()->exists()) {
            return response()->json([
                'message' => 'Cannot delete this status because it is still assigned to one or more courses.'
            ], 409); // Conflict
        }

        $this->removeIcon($status);
        $status->delete();

        return response()->json([
            'message' => 'Success, status deleted successfully',
        ], 200);
    }

}
