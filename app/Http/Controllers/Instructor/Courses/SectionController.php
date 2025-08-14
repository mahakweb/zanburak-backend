<?php

namespace App\Http\Controllers\Instructor\Courses;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Course $course)
    {
        if(auth()->user()->id == $course->teacher_id){
            return view('instructor.courses.sections.create', compact('course'));
        }
        abort(404);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Course $course)
    {
        if(auth()->user()->id == $course->teacher_id){
            if($request->ajax()){
                $validData = Validator::make($request->all(), [
                    'title' =>['required', 'min:5'],
                    'status' =>['required'],
                ]);

                if(!$validData->passes()){
                    return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
                }else{
                    $insertData = Section::create([
                        'course_id' => $course->id,
                        'title' => $request->title,
                        'status' => $request->status,
                    ]);
                    if ($insertData){
                        return response()->json(['status' => 1, 'course_id' => $course->id, 'section_id' => $insertData->id, 'msg' => 'data has been successfully saved !']);
                    }
                }
            }else{
                $validData = $request->validate([
                    'title' => ['required', 'min:5'],
                    'status' => ['required'],
                ]);
                if($request['publish'] == 'on'){
                    $request['publish'] = 1;
                }else{
                    $request['publish'] = 0;
                }

                $createSection = Section::create([
                    'course_id' => $course->id,
                    'title' => $validData['title'],
                    'description' => $request['description'],
                    'status' => $validData['status'],
                    'publish' => $request['publish'],
                    'attached_file' => $request['attached-file'],
                ]);
                return back();
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\Response
     */
    public function show(Section $section)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\Response
     */
    public function edit(Course $course, Section $section)
    {
        if(auth()->user()->id == $course->teacher_id && $course->id == $section->course_id){
            return view('instructor.courses.sections.edit', compact('course', 'section'));
        }
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Course $course, Section $section)
    {
        if(auth()->user()->id == $course->teacher_id && $course->id == $section->course_id){
            $validData = $request->validate([
                'title' => ['required', 'min:5'],
                'status' => ['required'],
            ]);
            if($request['publish'] == 'on'){
                $request['publish'] = 1;
            }else{
                $request['publish'] = 0;
            }

            $updateSection = $section->update([
                'title' => $validData['title'],
                'description' => $request['description'],
                'status' => $validData['status'],
                'publish' => $request['publish'],
                'attached_file' => $request['attached-file'],
            ]);
            return back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Course $course, Section $section)
    {
        if(auth()->user()->id == $course->teacher_id && $section->course_id == $course->id) {
            if ($request->ajax()) {
                if ($section->delete()) {
                    return response()->json(['status' => 1, 'msg' => 'data has been successfully delete !']);
                }
            }
        }
    }
}
