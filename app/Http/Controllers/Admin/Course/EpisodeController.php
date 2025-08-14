<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Section;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

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
        return view('admin.apps.courses.episode.create', compact('course', 'section'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Course $course, Section $section)
    {
        $validator = Validator::make($request->all(), [
            'disk'          => ['required'],
            'video'         => ['required'],
            'download_video'=> ['required'],
            'total_time'    => ['required', 'numeric', 'min:0'],
            'lock'          => ['boolean'],
            'title'         => ['required', 'min:5', 'max:255'],
            'english_title' => ['required', 'min:5', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/'],
            'description'   => ['nullable', 'min:10'],
            'publish'       => ['required', 'boolean'],
            'attach.*.attach_title'      => ['nullable', 'min:3'],
            'attach.*.attach_url'      => ['nullable', 'min:3']
        ], [
           'total_time.required' => 'فیلد زمان ویدیو اجباری است',
           'attach.*.attach_title.min' => 'عنوان فایل پیوست نباید از 3 کاراکتر کمتر باشد',
           'attach.*.attach_url.min' => 'آدرس فایل پیوست نباید از 3 کاراکتر کمتر باشد',
        ]);

        if(!$validator->passes()){
            return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
        }else{
            $validData = $validator->validated();
            $validData['lock'] = $request->has('lock');

            $episode = $section->episode()->create($validData);

            $episode->videos()->create([
                'disk' => $validData['disk'],
                'type' => 'stream',
                'path' => $validData['video'],
                'duration' => $validData['total_time']
            ]);
            $episode->videos()->create([
                'disk' => $validData['disk'],
                'type' => 'download',
                'path' => $validData['download_video'],
                'duration' => $validData['total_time']
            ]);

            if($request->attach){
                foreach($request->attach as $x => $y){
                    if($y['attach_title']){
                        $episode->attachs()->create([
                            'title' => $y['attach_title'],
                            'url' => $y['attach_url'],
                        ]);
                    }
                }
            }



            return response()->json(['status' => 1, 'msg' => 'ok']);

        }
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
        return view('admin.apps.courses.episode.edit', compact(['course', 'section', 'episode']));
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
        $validator = Validator::make($request->all(), [
            'disk'          => ['required'],
            'video'         => ['required'],
            'download_video'=> ['required'],
            'total_time'    => ['required', 'numeric', 'min:0'],
            'lock'          => ['boolean'],
            'title'         => ['required', 'min:5', 'max:255'],
            'english_title' => ['required', 'min:5', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/'],
            'description'   => ['nullable', 'min:10'],
            'publish'       => ['required', 'boolean'],
            'attach.*.attach_title'      => ['nullable', 'min:3'],
            'attach.*.attach_url'      => ['nullable', 'min:3']
        ], [
           'total_time.required' => 'فیلد زمان ویدیو اجباری است',
           'attach.*.attach_title.min' => 'عنوان فایل پیوست نباید از 3 کاراکتر کمتر باشد',
           'attach.*.attach_url.min' => 'آدرس فایل پیوست نباید از 3 کاراکتر کمتر باشد',
        ]);

        if(!$validator->passes()){
            return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
        }else{
            $validData = $validator->validated();
            $validData['lock'] = $request->has('lock');

            $episode->update($validData);
            // todo if video change then delete else not delete
            $episode->videos()->delete();

            $episode->videos()->create([
                'disk' => $validData['disk'],
                'type' => 'stream',
                'path' => $validData['video'],
                'duration' => $validData['total_time']
            ]);
            $episode->videos()->create([
                'disk' => $validData['disk'],
                'type' => 'download',
                'path' => $validData['download_video'],
                'duration' => $validData['total_time']
            ]);

            $episode->attachs()->delete();

            if($request->attach){
                foreach($request->attach as $x => $y){
                    if($y['attach_title']){
                        $episode->attachs()->create([
                            'title' => $y['attach_title'],
                            'url' => $y['attach_url'],
                        ]);
                    }
                }
            }



            return response()->json(['status' => 1, 'msg' => 'ok']);

        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Episode  $episode
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Course $course, Section $section, Episode $episode)
    {

        $episode->attachs()->delete();
        $episode->videos()->delete();
        $episode->comments()->delete();

        if($episode->delete()){
            return response()->json(['status' => 1, 'msg' => 'successfully']);
        }

    }
}
