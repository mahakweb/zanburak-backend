<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Question;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request){
        if(! $request->search || empty($request->search)){
            return redirect('/');
        }
        if(! $request->type || !in_array($request->type, ['discuss', 'courses', 'episodes'])) {
            return redirect($request->fullUrlWithQuery(['type' => 'courses']));
        }


        switch ($request->type) {
            case 'discuss':
                $questions = Question::search($request->search)->paginate(10);
                if($request->ajax()){
                    // $html = '';
                    // $html = view('search.component.questions', [
                    //     'questions' => $questions,
                    // ])->render();
                    // return $html;
                    return response()->json([
                        'data' => view('search.component.questions')->with('questions',$questions)->render()
                    ], 200,['Content-Type' => 'application/json']);
                }

                // return view('search.index', compact('questions'));

            case 'courses':
                $courses = Course::search($request->search)->paginate(9);
                if($request->ajax()){
                    // $html = '';
                    // $html = view('search.component.courses', [
                    //     'courses' => $courses,
                    // ])->render();
                    // return $html;
                    return response()->json([
                        'data' => view('search.component.courses')->with('courses',$courses)->render()
                    ], 200,['Content-Type' => 'application/json']);
                }

                // return view('search.index', compact('courses'));

            case 'episodes':
                $episodes = Episode::search($request->search)->paginate(9);
                if($request->ajax()){
                    // $html = '';
                    // $html = view('search.component.episodes', [
                    //     'episodes' => $episodes,
                    // ])->render();
                    // return $html;
                    return response()->json([
                        'data' => view('search.component.episodes')->with('episodes',$episodes)->render()
                    ], 200,['Content-Type' => 'application/json']);
                }

                // return view('search.index', compact('episodes'));

            // default:
            //     $courses = Course::search($request->search)->get();
            //     return $courses;
        }


        return view('search.index');

    }
}
