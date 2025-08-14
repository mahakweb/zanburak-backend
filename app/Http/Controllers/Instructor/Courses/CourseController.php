<?php

namespace App\Http\Controllers\Instructor\Courses;

use App\Http\Controllers\Controller;
use App\Models\category;
use App\Models\Course;
use App\Models\Level;
use App\Models\Section;
use App\Models\status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('instructor.courses.all');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $categories = \App\Models\Category::all();
        $statuses = \App\Models\Status::all();
        $levels = \App\Models\Level::all();
        $tags = \Illuminate\Support\Facades\DB::table('taggable_tags')->get();

        return view('instructor/courses/create', compact('categories', 'statuses', 'levels', 'tags'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validData = $request->validate([
            'title' => ['required', 'min:10', 'max:50'],
            'description' => ['required', 'min:10'],
//            'trailer-video' => ['required', 'mimetypes:video/avi,video/mpeg,video/quicktime,video/mp4,video/mkv,video/wmv', 'max:102400'],
//            'poster' => ['required', 'mimetypes:image/jpg,image/jpeg,image/png', 'max:5120'],
            'trailer-video' => ['required'],
            'poster' => ['required'],
            'category' => 'required',
            'status' => 'required',
            'level' => 'required',
            'price' => 'required',
            'tags' => 'required',

        ]);
        if($request['publish'] == 'on'){
            $request['publish'] = 1;
        }else{
            $request['publish'] = 0;
        }

        $validData['price'] = str_replace(",", "", $validData['price']);
        $course = Course::create([
            'teacher_id' => auth()->user()->id,
            'title' => $validData['title'],
            'description' => $validData['description'],
            'trailer' => $validData['trailer-video'],
            'poster' => $validData['poster'],
            'status_id' => $validData['status'],
            'level_id' => $validData['level'],
            'price' => $validData['price'],
            'publish' => $request['publish']
        ]);
        $course->tagById($validData['tags']);
        $course->category()->attach($validData['category']);
        return back();

    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Course  $course
     * @return \Illuminate\Http\Response
     */
    public function edit(Course $course)
    {
        if(auth()->user()->id !== $course->teacher_id){
            abort(404);
        }
        $categories = Category::all();
        $statuses = Status::all();
        $levels = Level::all();
        $tags = DB::table('taggable_tags')->get();
        $course_category = $course->category()->get()->pluck('id')->toArray();
        $course_tag = $course->allTags();
        $sections = $course->section()->get();
        return view('instructor.courses.edit', compact('course', 'categories', 'statuses', 'levels', 'tags', 'course_tag', 'course_category', 'sections'));
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
        if(auth()->user()->id !== $course->teacher_id){
            abort(404);
        }
        $validData = $request->validate([
            'title' => ['required', 'min:10', 'max:50'],
            'description' => ['required', 'min:10'],
//            'trailer-video' => ['required', 'mimetypes:video/avi,video/mpeg,video/quicktime,video/mp4,video/mkv,video/wmv', 'max:102400'],
//            'poster' => ['required', 'mimetypes:image/jpg,image/jpeg,image/png', 'max:5120'],
            'trailer-video' => ['required'],
            'poster' => ['required'],
            'category' => 'required',
            'status' => 'required',
            'level' => 'required',
            'price' => 'required',
            'tags' => 'required',

        ]);
        if($request['publish'] == 'on'){
            $request['publish'] = 1;
        }else{
            $request['publish'] = 0;
        }

        $validData['price'] = str_replace(",", "", $validData['price']);
        $course_update = $course->update([
//            'teacher_id' => auth()->user()->id,
            'title' => $validData['title'],
            'description' => $validData['description'],
            'trailer' => $validData['trailer-video'],
            'poster' => $validData['poster'],
            'status_id' => $validData['status'],
            'level_id' => $validData['level'],
            'price' => $validData['price'],
            'publish' => $request['publish']
        ]);
        $course->retagById($request->input('tags'));
        $course->category()->detach();
        $course->category()->attach($validData['category']);
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Course  $course
     * @return \Illuminate\Http\Response
     */
    public function destroy(Course $course)
    {
        //
    }

}
