<?php

namespace App\Http\Controllers\Instructor\Courses;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EpisodeController extends Controller
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
    public function create(Course $course, Section $section)
    {
        if(auth()->user()->id == $course->teacher_id && $course->id == $section->course_id){
            return view('instructor.courses.episodes.create', compact('section'));
        }
        abort(404);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Course $course, Section $section)
    {
        if(auth()->user()->id == $course->teacher_id && $course->id == $section->course_id){
            $validData = $request->validate([
                'title' => ['required', 'min:5'],
                'description' => ['required', 'min:10'],
                'attached-file' => ['required'],
                'video' => ['required'],
                'total-time' => ['required', 'numeric'],
            ]);
            if($request['publish'] == 'on'){
                $request['publish'] = 1;
            }else{
                $request['publish'] = 0;
            }

            $episode = Episode::create([
                'section_id' => $section->id,
                'title' => $validData['title'],
                'description' => $validData['description'],
                'attached_file' => $validData['attached-file'],
                'video' => $validData['video'],
                'total_time' => $validData['total-time'],
                'publish' => $request['publish'],
            ]);
        }
        return back();

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Episode  $episode
     * @return \Illuminate\Http\Response
     */
    public function show(Episode $episode)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Episode  $episode
     * @return \Illuminate\Http\Response
     */
    public function edit(Course $course, Section $section, Episode $episode)
    {
        if(auth()->user()->id == $course->teacher_id && $course->id == $section->course_id && $section->id == $episode->section_id){
            return view('instructor.courses.episodes.edit', compact(['episode', 'section', 'course']));
        }
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Episode  $episode
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Course $course, Section $section, Episode $episode)
    {
        if(auth()->user()->id == $course->teacher_id && $course->id == $section->course_id && $section->id == $episode->section_id){
            $validData = $request->validate([
                'title' => ['required', 'min:5'],
                'description' => ['required', 'min:10'],
                'attached-file' => ['required'],
                'video' => ['required'],
                'total-time' => ['required', 'numeric'],
            ]);
            if($request['publish'] == 'on'){
                $request['publish'] = 1;
            }else{
                $request['publish'] = 0;
            }

            $editEpisode = $episode->update([
                'title' => $validData['title'],
                'description' => $validData['description'],
                'attached_file' => $validData['attached-file'],
                'video' => $validData['video'],
                'total_time' => $validData['total-time'],
                'publish' => $request['publish'],
            ]);
        }
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Episode  $episode
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Course $course, Section $section, Episode $episode)
    {
        if(auth()->user()->id == $course->teacher_id && $section->course_id == $course->id && $episode->section_id == $section->id) {
            if ($request->ajax()) {
                if ($episode->delete()) {
                    return response()->json(['status' => 1, 'msg' => 'data has been successfully delete !']);
                }
            }
        }
    }
}
