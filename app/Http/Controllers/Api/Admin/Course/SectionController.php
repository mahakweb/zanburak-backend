<?php

namespace App\Http\Controllers\Api\Admin\Course;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesAdminCourses;
use App\Models\Course;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    use AuthorizesAdminCourses;

    public function createSection(Request $request, $course)
    {
        $this->authorizeCourse($course, 'update');

        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'min:5', 'max:255'],
            'english_title' => ['required', 'min:5', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/'],
            'description' => ['nullable', 'min:10'],
            'publish' => ['required', 'boolean'],
            'start_date' => ['nullable', "date", "before:end_date"],
            'end_date' => ['nullable', "date", "after:start_date"],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validData = $validator->validated();
            $section = $course->section()->create($validData);
            $section->episodes = [];
            return response()->json(['message' => 'success, section create successfully.', 'section' => $section], 200);
        }
    }

    public function editSection(Request $request, $course, Section $section)
    {
        $this->authorizeCourse($course, 'update');

        if ($course->id != $section->course_id) {
            return response()->json(['message' => 'error! this section not belong to selected course'], 422);
        }
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'exists:sections,id'],
            'title' => ['required', 'min:5', 'max:255'],
            'english_title' => ['required', 'min:5', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/'],
            'description' => ['nullable', 'min:10'],
            'publish' => ['required', 'boolean'],
            'start_date' => ['nullable', "date", "before:end_date"],
            'end_date' => ['nullable', "date", "after:start_date"],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validData = $validator->validated();
            $section->update($validData);
            return response()->json(['message' => 'success, section create successfully.', 'section' => $section], 200);
        }
    }


    public function deleteSection(Request $request, $course, Section $section)
    {
        $this->authorizeCourse($course, 'update');

        if ($course->id != $section->course_id) {
            return response()->json(['message' => 'error! this section not belong to selected course'], 422);
        }
        $user = auth('api')->user();

        $deletedSection = $section;
        $section->delete();
        return response()->json(['message' => 'success, section deleted successfully.', 'section' => $deletedSection], 200);
    }
}