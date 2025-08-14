<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $courses = Course::all();
        return view('admin.apps.courses.list', compact('courses'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.apps.courses.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // return $request->all();
        $validator = Validator::make($request->all(), [
            'poster' => ['required'],
            'status_id' => ['required', 'exists:statuses,id'],
            'level_id' => ['required', 'exists:levels,id'],
            'type' => ['required', 'in:free,cash,cash-vip'],
            'category' => ['required', 'array'],
            'tags' => ['required'],
            'title' => ['required', 'min:10', 'max:255', 'unique:courses,title'],
            'english_title' => ['required', 'min:10', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/', 'unique:courses,english_title'],
            'description' => ['required', 'min:10'],
            'publish' => ['required', 'boolean'],
            'price' => ['nullable', 'numeric', 'max:100000000'],
            'start_date' => ['nullable'],
            'end_date' => ['nullable'],
            'disk' => ['nullable'],
            'trailer' => ['nullable'],// check 'mimes:mp4,mkv'
            'attached_file' => ['nullable'],// check 'mimes:zip,rar'
        ]);
        if(!$validator->passes()){
            return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
        }else{
            $validData = $validator->validated();

            // if($request['poster']){
            //     $poster = $request->file('poster');
            //     $destinationPath = '/course/poster/'.now()->year.'/'.now()->month.'/'.now()->day.'/';
            //     $poster_pic = time().'-'.$poster->getClientOriginalName();
            //     $validData['poster'] = env('APP_URL').'/storage'.$destinationPath.$poster_pic;
            //     Storage::disk('public')->putFileAs($destinationPath, $request->file('poster'), $poster_pic);
            // }else{
            //     $validData['poster'] = null;
            // }


            $course = auth()->user()->addCourse()->create($validData);
            $course->category()->attach($request['category']);

            $request['tags'] = json_decode($request['tags'], true);


            foreach ($request['tags'] as $key => $value) {
                $course->tag($value);
            }



            return response()->json(['status' => 1, 'msg' => 'ok']);

        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Course  $course
     * @return \Illuminate\Http\Response
     */
    public function show(Course $course)
    {
        return view('admin.apps.courses.show', compact('course'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Course  $course
     * @return \Illuminate\Http\Response
     */
    public function edit(Course $course)
    {
        return view('admin.apps.courses.edit', compact('course'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Course  $course
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Course $course)
    {
        // return $request->all();
        $validator = Validator::make($request->all(), [
            'poster' => ['required'],
            'status_id' => ['required', 'exists:statuses,id'],
            'level_id' => ['required', 'exists:levels,id'],
            'type' => ['required', 'in:free,cash,cash-vip'],
            'category' => ['required', 'array'],
            'tags' => ['required'],
            'title' => ['required', 'min:10', 'max:255', Rule::unique('courses')->ignore($course->id)],
            'english_title' => ['required', 'min:10', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/', Rule::unique('courses')->ignore($course->id)],
            'description' => ['required', 'min:10'],
            'publish' => ['required', 'boolean'],
            'price' => ['nullable', 'numeric', 'max:20000000'],
            'start_date' => ['nullable'],
            'end_date' => ['nullable'],
            'disk' => ['nullable'],
            'trailer' => ['nullable'],// check 'mimes:mp4,mkv'
            'attached_file' => ['nullable'],// check 'mimes:zip,rar'
        ]);
        if(!$validator->passes()){
            return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
        }else{
            $validData = $validator->validated();

            $course->update($validData);
            $course->category()->sync($request['category']);

            $request['tags'] = json_decode($request['tags'], true);

            $course->detag();

            foreach ($request['tags'] as $key => $value) {
                $course->tag($value);
            }



            return response()->json(['status' => 1, 'msg' => 'ok']);

        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Course  $course
     * @return \Illuminate\Http\Response
     */
    public function destroy(Course $course)
    {
        $course->detag();
        $course->comments()->delete();
        if($course->delete()){
            return response()->json(['status' => 1, 'msg' => 'successfully delete item']);
        }
        return response()->json(['status' => 0, 'msg' => 'item was not delete']);
    }
}
