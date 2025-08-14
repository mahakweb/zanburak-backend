<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class CourseController extends Controller
{

    public function allCourse(Request $request){
         $courses = Course::where('publish', '1')->filter()->paginate(9);


        if ($request->ajax()) {
            $html = '';

            $html = view('course.courses-list', [
                'courses' => $courses,
            ])->render();

            return $html;
        }

        return view('course.all-course', compact('courses'));
    }


    public function SingleCourse($course, Request $request){
        if($course->publish == 0){
            abort(404);
        }
        $relatedCourses = collect();
        foreach($course->category as $category) {
            foreach ($category->course as $relatedCourse) {
                if($relatedCourse->id != $course->id){
                    $relatedCourses->add($relatedCourse);
                }   
            }
        }

        $comments = $course->comments()->where('parent_id', 0)->where('approved', '=', 1)->orderBy('id','desc')->paginate(10);

        if ($request->ajax()) {
            $html = '';

            $html = view('comment.comments-list', [
                'comments' => $comments,
            ])->render();

            return $html;
        }

        return view('course.course', compact(['course', 'relatedCourses', 'comments']));
    }


    public function SingleEpisode($course, Episode $episode, Request $request){
        if($episode->publish == 0 ){
            abort(404);
        }

        if($course->id != $episode->section->course_id){
            abort(404);
        }

        $comments = $episode->comments()->where('parent_id', 0)->where('approved', '=', 1)->orderBy('id','desc')->paginate(10);

        if ($request->ajax()) {
            $html = '';

            $html = view('comment.comments-list', [
                'comments' => $comments,
            ])->render();

            return $html;
        }

        return view('course.episode', compact(['episode', 'course', 'comments']));
    }





    public function episodeVideo(Episode $episode){
        // $episode = Episode::findOrFail(request()->episode);
        return redirect(URL::temporarySignedRoute('video-playlist', now()->addMinutes(20), ['path' => $episode->videos->where('type', 'stream')->pluck('path')[0], 'disk' => $episode->videos->where('type', 'stream')->pluck('disk')[0]]));
    }

    public function courseVideo(Course $course){
        // $course = Course::findOrFail(request()->course);
        return redirect(URL::temporarySignedRoute('video-playlist', now()->addMinutes(20), ['path' => $course->trailer, 'disk' => $course->disk]));
    }


    public function videoKey(){
        return Storage::disk(request()->disk)->get(request()->path);
    }


    public function videoM3u8(){
        return Storage::disk(request()->disk)->get(request()->path);
    }


    public function videoPlaylist(){
        // var_dump(request()->path);
        // var_dump(request()->disk);
        $disk = request()->disk;
        return FFMpeg::dynamicHLSPlaylist()
        ->fromDisk($disk)
        ->open(request()->path)
        ->setKeyUrlResolver(function ($key) {
            return (URL::temporarySignedRoute('video-key', now()->addMinutes(20), ['path' => str_replace(substr(request()->path, strrpos(request()->path, "/")+1),"", request()->path)."keys/".$key, 'disk' => request()->disk]));
        })
        ->setMediaUrlResolver(function ($mediaFilename) {
            return (URL::temporarySignedRoute('video-m3u8', now()->addMinutes(20), ['path' => str_replace(substr(request()->path, strrpos(request()->path, "/")+1),"", request()->path).$mediaFilename, 'disk' => request()->disk]));
        })
        ->setPlaylistUrlResolver(function ($playlistFilename) {
            return (URL::temporarySignedRoute('video-playlist', now()->addMinutes(20),['path' => str_replace(substr(request()->path, strrpos(request()->path, "/")+1),"", request()->path).$playlistFilename, 'disk' => request()->disk]));
        });
    }



    public function episodeDownloadCheck(Request $request, Episode $episode){
        if(auth()->user()->canGetEpisode($episode)['can'] == true){
            $url = URL::temporarySignedRoute('episode-download', now()->addMinutes(20), $episode->id);
           return response()->json(['status' => 1, 'url' => $url]);
        }
        return response()->json(['status' => 1, 'msg' => 'You have not access to download this episode']);
    }

    public function episodeDownload(Request $request, Episode $episode){
        // $storagePath  = Storage::disk('dl')->url('');
        // $storagePath  = "http://dl.zanburak.ir/";
        // $path = $storagePath.$episode->video;
        // return response()->download($path);
        return Storage::disk($episode->videos->where('type', 'download')->pluck('disk')[0])->download($episode->videos->where('type', 'download')->pluck('path')[0]);
    }
}
