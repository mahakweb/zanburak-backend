<?php

namespace App\Http\Controllers\Api\Admin\Mission;

use App\Http\Controllers\Controller;
use App\Models\Mission;
use App\Models\MissionCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MissionCategoryController extends Controller
{
  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'title' => ['required', 'string', 'min:2', 'max:255'],
      'english_title' => [
        'required',
        'string',
        'min:2',
        'max:255',
        'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/',
        'unique:mission_categories,english_title',
      ],
    ]);

    if (!$validator->passes()) {
      return response()->json([
        'message' => 'Validation error!',
        'errors' => $validator->errors()->toArray(),
      ], 422);
    }

    $category = MissionCategory::create($validator->validated());

    return response()->json([
      'message' => 'دسته‌بندی ماموریت با موفقیت ایجاد شد.',
      'category' => $this->transformCategory($category),
    ], 201);
  }

  public function update(MissionCategory $category, Request $request)
  {
    $validator = Validator::make($request->all(), [
      'title' => ['required', 'string', 'min:2', 'max:255'],
      'english_title' => [
        'required',
        'string',
        'min:2',
        'max:255',
        'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/',
        Rule::unique('mission_categories', 'english_title')->ignore($category->id),
      ],
    ]);

    if (!$validator->passes()) {
      return response()->json([
        'message' => 'Validation error!',
        'errors' => $validator->errors()->toArray(),
      ], 422);
    }

    $category->update($validator->validated());

    return response()->json([
      'message' => 'دسته‌بندی ماموریت با موفقیت به‌روزرسانی شد.',
      'category' => $this->transformCategory($category->fresh()->loadCount('missions')),
    ]);
  }

  public function destroy(MissionCategory $category, Request $request)
  {
    $missionsCount = Mission::where('category_id', $category->id)->count();

    if ($missionsCount > 0 && !$request->boolean('force')) {
      return response()->json([
        'message' => "این دسته‌بندی {$missionsCount} ماموریت دارد. برای حذف، force=true ارسال کنید.",
        'missions_count' => $missionsCount,
      ], 409);
    }

    Mission::where('category_id', $category->id)->delete();
    $category->delete();

    return response()->json([
      'message' => 'دسته‌بندی ماموریت با موفقیت حذف شد.',
    ]);
  }

  protected function transformCategory(MissionCategory $category): array
  {
    return [
      'id' => $category->id,
      'title' => $category->title,
      'english_title' => $category->english_title,
      'slug' => $category->slug,
      'missions_count' => $category->missions_count ?? Mission::where('category_id', $category->id)->count(),
      'created_at' => $category->created_at,
      'updated_at' => $category->updated_at,
    ];
  }
}
