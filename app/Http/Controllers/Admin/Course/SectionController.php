<?php

namespace App\Http\Controllers\Admin\Course;

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
    public function index(Course $course)
    {
        $sections = $course->section;
        return view('admin.apps.courses.section.list', compact(['course', 'sections']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Course $course)
    {

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'min:5', 'max:255'],
            'english_title' => ['required', 'min:5', 'max:255'],
            'publish' => ['required', 'boolean'],
            'start_date' => ['nullable'],
            'end_date' => ['nullable'],
            'attached_file' => ['nullable'],// check 'mimes:zip,rar'
        ]);
        if(!$validator->passes()){
            return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
        }else{
            $validData = $validator->validated();

            $section = $course->section()->create($validData);

            return response()->json(['status' => 1, 'msg' => 'ok']);

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
    public function edit(Section $section)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Section $section)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Section  $section
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Course $course, Section $section)
    {

        $delete = $section->delete();
        if($delete){
            return response()->json(['status' => 1, 'msg' => 'successfully']);
        }

    }
}
