<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\User;
use App\Models\View;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class ProfileController extends Controller
{
    public function index($username)
    {
        if (!$username) {
            return response()->json(['message' => 'Error: Not found'], 404);
        }

        View::createFor($username);

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

    public function followers($username, Request $request)
    {
        if (!$username) {
            return response()->json(['message' => 'Error: Not found'], 404);
        }

        $loginUser = auth('api')->user();
        $perPage = max(1, min(50, (int) $request->input('perPage', 10)));
        $page = max(1, (int) $request->input('page', 1));

        $paginator = $username->followers()
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.username', 'users.profile_pic')
            ->orderByPivot('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $users = $paginator->getCollection()->map(function ($user) use ($loginUser) {
            return $this->formatFollowListUser($user, $loginUser);
        })->values();

        return response()->json([
            'message' => 'Success',
            'users' => $users,
            'pagination' => $this->followListPagination($paginator),
        ], 200);
    }

    public function followings($username, Request $request)
    {
        if (!$username) {
            return response()->json(['message' => 'Error: Not found'], 404);
        }

        $loginUser = auth('api')->user();
        $perPage = max(1, min(50, (int) $request->input('perPage', 10)));
        $page = max(1, (int) $request->input('page', 1));

        $query = $username->followings()
            ->where('followable_type', User::class)
            ->orderBy('id', 'desc');

        $total = $query->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $currentPage = min($page, $lastPage);

        $records = $query
            ->with(['followable:id,first_name,last_name,username,profile_pic'])
            ->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        $users = $records->map(function ($record) use ($loginUser) {
            $user = $record->followable;
            if (!$user instanceof User) {
                return null;
            }

            return $this->formatFollowListUser($user, $loginUser);
        })->filter()->values();

        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

        return response()->json([
            'message' => 'Success',
            'users' => $users,
            'pagination' => [
                'total' => $total,
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'next_page' => $nextPage,
            ],
        ], 200);
    }

    private function formatFollowListUser(User $user, $loginUser): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'profile_pic' => $user->profile_pic,
            'hasFlollow' => $loginUser ? $loginUser->isFollowing($user) : false,
            'is_self' => $loginUser && (int) $loginUser->id === (int) $user->id,
        ];
    }

    private function followListPagination($paginator): array
    {
        return [
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'next_page' => $paginator->currentPage() < $paginator->lastPage()
                ? $paginator->currentPage() + 1
                : null,
        ];
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
