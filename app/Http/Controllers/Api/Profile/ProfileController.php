<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class ProfileController extends Controller
{
    public function index($username)
    {
        if (!$username) {
            return response()->json(['message' => 'Error: Not found'], 404);
        }
        $loginUser = auth('api')->user();
        $user = $username->only('id', 'first_name', 'last_name', 'username', 'username', 'email', 'profile_pic', 'cover_pic', 'created_at', 'last_seen');
        $user['info'] = $username->info->only('about', 'job', 'birth_date', 'website', 'github', 'twitter', 'linkedin', 'telegram', 'instagram');
        
        if (!empty($username->info->birth_date)) {
            $user['info']['birth_date'] = Jalalian::fromCarbon(new \Carbon\Carbon($username->info->birth_date))->format('Y-m-d');
        } else {
            $user['info']['birth_date'] = null; 
        }

        $user['follow'] = [
            'hasFlollow' => $loginUser ? $loginUser->isFollowing($username) : false,
            'numberOfFollowers' => $username->followers->count(),
            'numberOfFollowings' => $username->followings->count(),
        ];
        $user['discuss'] = [
            'numberOfQuestions' => $username->questions->count(),
            'numberOfAnswers' => $username->answers->count(),
            'numberOfBestAnswers' => $username->BestAnswers()->count()
        ];
        $user['score'] = $username->currentScore();
        return response()->json(['message' => 'Success', 'user' => $user]);
    }

    public function about($username)
    {
        if (!$username) {
            return response()->json(['message' => 'Error: Not found'], 404);
        }
        $user = $username->only('id', 'first_name', 'last_name', 'username', 'username', 'email', 'profile_pic', 'cover_pic', 'created_at', 'last_seen');
        $user['info'] = $username->info->only('about', 'job', 'birth_date', 'website', 'github', 'twitter', 'linkedin', 'telegram', 'instagram');

        return response()->json(['message' => 'Success', 'user' => $user]);
    }

    public function discuss($username, Request $request)
    {
        $loginUser = auth('api')->user();
        if (!$username) {
            return response()->json(['message' => 'Error: Not found'], 404);
        }
        $user = $username->only('id', 'first_name', 'last_name', 'username', 'username', 'email', 'profile_pic', 'cover_pic', 'created_at', 'last_seen');

        $query = $username->questions()->where('publish', '1')->where('is_private', '0')->orderBy('id', 'desc')->get();
        $questions = $query->map(function ($question) use ($loginUser) {
            $questionUser = $question->user()->select('first_name', 'last_name', 'username', 'profile_pic')->first();
            $totalAnswers = $question->answers()->count();
            $lastAnswer = $question->answers()->latest()->first();
            $isEditable = $question->isEditableBy($loginUser);

            $lastAnswerData = null;
            if ($lastAnswer) {
                $answerUser = $lastAnswer->user()->select('first_name', 'last_name', 'username', 'profile_pic')->first();
                $lastAnswerData = [
                    'created_at' => $lastAnswer->created_at,
                    'user' => $answerUser
                ];
            }
            $tags = $question->tags->map(function ($tag) {
                return [
                    'id' => $tag->tag_id,
                    'name' => $tag->name,
                    'normalized' => $tag->normalized,
                ];
            });

            $isBookmarked = $loginUser ? $loginUser->hasBookmarked($question) : false;

            return [
                'id' => $question->id,
                'subject' => $question->subject,
                'slug' => $question->slug,
                'question' => $question->question,
                'best_answer' => $question->best_answer,
                'is_private' => $question->is_private,
                'created_at' => $question->created_at,
                'updated_at' => $question->updated_at,
                'user' => $questionUser,
                'last_answer' => $lastAnswerData,
                'total_answers' => $totalAnswers,
                'tags' => $tags,
                'bookmarked' => $isBookmarked,
                'is_editable' => $isEditable
            ];
        });

        $questionsPerPage = $request->input('perPage', 10);

        $currentPage = $request->input('page', 1);
        $total = $questions->count();
        $lastPage = ceil($total / $questionsPerPage);

        $paginatedQuestions = $questions->slice(($currentPage - 1) * $questionsPerPage, $questionsPerPage)->values();

        return response()->json([
            'message' => 'Success',
            'user' => $user,
            'questions' => $paginatedQuestions,
            'pagination' => [
                'total' => $total,
                'current_page' => $currentPage,
                'per_page' => $questionsPerPage,
                'last_page' => $lastPage,
            ]
        ], 200);
    }

    public function answers($username, Request $request)
    {
        $loginUser = auth('api')->user();
        if (!$username) {
            return response()->json(['message' => 'Error: Not found'], 404);
        }
        $user = $username->only('id', 'first_name', 'last_name', 'username', 'email', 'profile_pic', 'cover_pic', 'created_at', 'last_seen');

        $answers = $username->answers()
            ->where('publish', 1)
            ->latest()
            ->select('id', 'answer', 'user_id', 'question_id', 'created_at', 'updated_at')
            ->with([
                'user:id,first_name,last_name,username,profile_pic',
                'question:id,user_id,slug,subject,question,created_at,updated_at',
                'question.user:id,first_name,last_name,username,email,profile_pic,cover_pic,created_at,last_seen'
                ])
            ->get();

        $answers = $answers->map(function ($answer) use ($loginUser) {
            $answer->likes_count = $answer->likesCount();
            $answer->is_editable = $answer->isEditableBy($loginUser);

            return $answer;
        })->filter();

        $answersPerPage = $request->input('perPage', 10);
        $currentPage = $request->input('page', 1);
        $total = $answers->count();
        $lastPage = ceil($total / $answersPerPage);

        $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

        $paginatedAnswers = $answers->slice(($currentPage - 1) * $answersPerPage, $answersPerPage)->values();

        return response()->json([
            'message' => 'Success',
            'user' => $user,
            'answers' => $paginatedAnswers,
            'pagination' => [
                'total' => $total,
                'current_page' => intval($currentPage),
                'per_page' => $answersPerPage,
                'last_page' => $lastPage,
                'prev_page' => $prevPage,
                'next_page' => $nextPage
            ]
        ], 200);
    }
}
