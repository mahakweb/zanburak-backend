<?php

namespace App\Http\Controllers\Discuss;

use App\Events\Score\Discuss\SelectBestAnswer;
use App\Events\Score\Discuss\SubmitNewAnswer;
use App\Events\Score\Discuss\SubmitNewQuestion;
use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;


class DiscussController extends Controller
{
    public function all(Request $request){

        // $questions_search = Question::search($request->search)->get();
        // $questions_filter = Question::filter()->orderBy('id', 'desc')->get();
        // $questions = $questions_search->intersect($questions_filter)->values()->all();
        // $questions = $this->paginate($questions, 5);

        // return $questions;

        if($request->search){
            $questions_search = Question::search($request->search)->get();
            $questions_filter = Question::filter()->orderBy('id', 'desc')->get();
            $questions = $questions_search->intersect($questions_filter)->values()->all();
            $questions = $this->paginate($questions, 10);
        }else{
            $questions = Question::filter()->orderBy('id', 'desc')->paginate(10);
        }

        // $questions = Question::paginate(8);
        if ($request->ajax()) {
            $html = '';
            $html = view('discuss.search-result-all', [
                'questions' => $questions,
            ])->render();
            return $html;
        }
        return view('discuss.discussions');
        // if($request['type'] && $request['type'] == 'all'){
        //     return redirect()->to($request->fullUrlWithoutQuery('type'));
        // }
        // $questions = Question::filter()->orderBy('id', 'desc')->get();
        // return view('discuss.discussions', compact('questions'));
    }


    public function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    public function createQuestionForm(){
        return view('discuss.create');
    }

    public function storeQuestion(Request $request){

        $validData = Validator::make($request->all(), [
            'category' => ['required', 'exists:categories,id'],
            'question' => ['required', 'min:10'],
            'subject'  => ['required', 'min:5'],
        // 'tags' => ['required'],
        ]);
        if( ! $validData->passes() ){
            return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
        }else {
            $question = auth()->user()->questions()->create([
                'subject' => $request['subject'],
                'question' => $request['question'],
                'category_id' => $request['category'],
            ]);
            if ($request['tags']){
                $question->tag($request['tags']);
            }
            if ($question) {

                event(new SubmitNewQuestion($question));
                
                // Fire Mission Community Activity Event
                event(new \App\Events\Mission\CommunityActivityEvent(auth()->user(), 'question', $question));

                return response()->json(['status' => 1, 'redirect_url' => route('discuss-question', $question->slug), 'msg' => 'data has been successfully saved !']);
            }
        }

    }

    public function question($question) {
        return view('discuss.question', compact('question'));
    }

    public function setBestAnswer(Request $request){
        $question = Question::findOrFail($request['question_id']);
        $answer = Answer::findOrFail($request['answer_id']);
        if ($question->user_id == auth()->user()->id && $answer->question_id == $question->id){
            $question->update([
                'best_answer' => $answer->id
            ]);

            event(new SelectBestAnswer($answer));

            alert()->success('موفق', 'بهترین پاسخ با موفقیت ثبت شد.')->showConfirmButton('بسیار خوب');

            return redirect()->back();
        }
        alert()->error('خطا!', 'لطفا مقادیر ورودی را بدرستی وارد کنید.')->showConfirmButton('بسیار خوب');

        return redirect()->back();
    }

    public function storeAnswer(Request $request){

        $validData = Validator::make($request->all(), [
            'parent_id' => ['required'],
            'question_id' => ['required'],
            'answer' => ['required', 'min:5']
        ], [
            'answer.required' => 'وارد کردن متن پاسخ الزامی است',
            'answer.min' => ' متن پاسخ نباید کمتر از 5 کاراکتر باشد'
        ]);
        if( ! $validData->passes() ){
            return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
        }else {
            $answer = auth()->user()->answers()->create([
                'answer' => $request['answer'],
                'parent_id' => $request['parent_id'],
                'question_id' => $request['question_id'],
            ]);
            if ($answer) {

                event(new SubmitNewAnswer($request->user(), $answer));

                return response()->json(['status' => 1, 'msg' => 'data has been successfully saved !']);
            }
        }

    }






    public function sendReport(Request $request){

        $validData = Validator::make($request->all(), [
            'reportable_id' => ['required'],
            'reportable_type' => ['required'],
            'report' => ['required']
        ]);
        if( ! $validData->passes() ){
            return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
        }else {
            $type = $request['reportable_type'];
            $className = "App\\Models\\" . ucfirst($type);
            
            if (!class_exists($className)) {
                return response()->json(['status' => 0, 'error' => 'Invalid reportable type']);
            }
            
            // Store full class name for proper polymorphic relation
            $report = auth()->user()->reports()->create([
                'report' => $request['report'],
                'reportable_id' => $request['reportable_id'],
                'reportable_type' => $className, // Store full class name
            ]);
            if ($report) {
                return response()->json(['status' => 1, 'msg' => 'data has been successfully saved !']);
            }
        }

    }

    public function ajaxSearchCreate(Request $request){

        $keyword = $request->get('keyword');
        if($keyword == ''){
            $questions = collect();
        }else{
            $questions = Question::where('subject','LIKE','%'.$keyword.'%')->orWhere('question','LIKE','%'.$keyword.'%')->orderBy('updated_at', 'desc')->get();
        }

        $html = view('discuss.search-result-create', [
            'questions' => $questions,
        ])->render();

        return response()->json(['html' => $html], 200);

    }

}
