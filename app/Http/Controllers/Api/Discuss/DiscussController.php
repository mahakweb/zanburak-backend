<?php

namespace App\Http\Controllers\Api\Discuss;

use App\Events\Score\Discuss\SelectBestAnswer;
use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Like;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Models\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class DiscussController extends Controller
{

    public function discussions(Request $request)
    {
        $user = auth('api')->user();

        $categories = $request->input('cat', []);
        $filter = $request->input('filter', '');
        $search = $request->input('search', '');

        if (!empty($categories)) {
            $categoryIds = QuestionCategory::whereIn('title', $categories)->pluck('id')->toArray();
        } else {
            $categoryIds = [];
        }

        $query = Question::filter($filter)
            ->categoryId($categoryIds)
            ->search($search)
            ->orderBy('id', 'desc');

        $allQuestions = $query->get();

        $filteredQuestions = $allQuestions->filter(function ($question) use ($user) {
            $allowedUserIds = json_decode($question->allowed_user_ids, true);

            return !$question->is_private ||
                ($user && ($question->user_id === $user->id || (is_array($allowedUserIds) && in_array($user->id, $allowedUserIds))));
        });

        $questions = $filteredQuestions->map(function ($question) use ($user) {
            $questionUser = $question->user()->select('first_name', 'last_name', 'username', 'profile_pic')->first();
            $totalAnswers = $question->answers()->count();
            $lastAnswer = $question->answers()->latest()->first();
            $isEditable = $question->isEditableBy($user);

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

            $isBookmarked = $user ? $user->hasBookmarked($question) : false;

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
            'questions' => $paginatedQuestions,
            'pagination' => [
                'total' => $total,
                'current_page' => $currentPage,
                'per_page' => $questionsPerPage,
                'last_page' => $lastPage,
            ]
        ], 200);
    }

    public function discuss(Request $request, $question)
    {
        $user = auth('api')->user();
        $allowedUserIds = json_decode($question->allowed_user_ids, true);

        $allowCheck = !$question->is_private || ($user && ($question->user_id === $user->id || (is_array($allowedUserIds) && in_array($user->id, $allowedUserIds))));
        if (!$allowCheck) {
            return response()->json(['message' => '!Error Question not found.'], 404);
        }
        View::createFor($question);


        $questionUser = $question->user()->select('first_name', 'last_name', 'username', 'profile_pic')->first();
        $totalAnswers = $question->answers()->count();
        $lastAnswer = $question->answers()->latest()->first();

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

        $isBookmarked = $user ? $user->hasBookmarked($question) : false;
        $isEditable = $question->isEditableBy($user);

        $questionData = [
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

        return response()->json(['message' => 'Success', 'question' => $questionData,], 200);

    }

    public function answers(Request $request, $question)
    {
        $user = auth('api')->user();
        $allowedUserIds = json_decode($question->allowed_user_ids, true);

        $allowCheck = !$question->is_private || ($user && ($question->user_id === $user->id || (is_array($allowedUserIds) && in_array($user->id, $allowedUserIds))));
        if (!$allowCheck) {
            return response()->json(['message' => '!Error Question not found.'], 404);
        }

        $pinnedAnswers = collect([]);
        $bestAnswer = $question->bestAnswer();
        if ($bestAnswer) {
            $bestAnswer->user = $bestAnswer->user->only('id', 'first_name', 'last_name', 'username', 'profile_pic');
            $bestAnswer->likes_count = $bestAnswer->likesCount();
        }

        $answers = $question->answers()
            ->where('publish', 1)
            ->latest()
            ->select('id', 'answer', 'user_id', 'question_id', 'parent_id', 'pinned_at', 'created_at', 'updated_at')
            ->with(['user:id,first_name,last_name,username,profile_pic'])
            ->get();

        $answers = $answers->map(function ($answer) use ($pinnedAnswers, $bestAnswer, $user) {
            $answer->likes_count = $answer->likesCount();
            $answer->is_editable = $answer->isEditableBy($user);

            if ($bestAnswer && $answer->id === $bestAnswer->id) {
                return null;
            }

            if ($answer->pinned_at !== null) {
                $pinnedAnswers->push($answer);
                return null;
            }

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
            'pinned_answers' => $pinnedAnswers,
            'best_answer' => $bestAnswer,
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

    public function newAnswer(Request $request, $question)
    {
        $user = auth('api')->user();
        $validData = Validator::make($request->all(), [
            'answer' => 'required|min:5',
            'parent_id' => 'nullable|exists:answers,id',
        ]);
        if (!$validData->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validData->errors()->toArray()], 422);
        } else {
            $parent_id = $request->input('parent_id', 0);
            $answer = new Answer();
            $answer->question_id = $question->id;
            $answer->user_id = $user->id;
            $answer->parent_id = $parent_id;
            $answer->pinned_at = null;
            $answer->answer = $request->answer;
            $answer->save();
            $answer->likes_count = 0;
            $answer->is_editable = true;
            $answer->user = $user->only('id', 'first_name', 'last_name', 'username', 'profile_pic');
            return response()->json(['message' => 'Success', 'answer' => $answer], 200);
        }
    }

    public function likeDislike(Request $request)
    {
        $user = auth('api')->user();
        $type = $request->input('type');

        $validData = Validator::make($request->all(), [
            'type' => 'required|in:like,dislike',
            'likeable_id' => 'required|exists:answers,id',
        ]);

        if (!$validData->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validData->errors()->toArray()], 422);
        }

        $className = "App\\Models\\" . ucfirst($request->input('likeable_type'));
        $model = new $className;
        $obj = $model::find($request->likeable_id);

        if ($obj->user_id == $user->id) {
            return response()->json([
                'errorType' => 'ownPost',
                'message' => 'You cannot like or dislike your own post.',
            ], 403);
        }

        $minScore = 4000;
        if ($user->currentScore() < $minScore) {
            return response()->json([
                'errorType' => 'lowScore',
                'minScore' => $minScore,
                'message' => 'Your score is too low to like or dislike.',
            ], 403);
        }

        $existingLike = $obj->likes()->where('user_id', $user->id)->first();

        if ($type === 'like') {
            if ($existingLike && $existingLike->type === 'dislike') {
                $existingLike->delete();
            } elseif (!$existingLike) {
                $obj->likes()->create([
                    'user_id' => $user->id,
                    'type' => 'like',
                ]);
            }
        } elseif ($type === 'dislike') {
            if ($existingLike && $existingLike->type === 'like') {
                $existingLike->delete();
            } elseif (!$existingLike) {
                $obj->likes()->create([
                    'user_id' => $user->id,
                    'type' => 'dislike',
                ]);
            }
        }

        $likesCount = $obj->likes()->where('type', 'like')->count() - $obj->likes()->where('type', 'dislike')->count();

        return response()->json(['message' => 'Success', 'likes_count' => $likesCount], 200);
    }

    public function getInitData(Request $request)
    {
        $initData = collect();
        $categories = QuestionCategory::select('id', 'title', 'english_title', 'slug')->where('status', '1')->get();
        $initData->put('categories', $categories);
        $popularTags = Question::popularTagsNormalized(20);
        $tags = collect($popularTags)->map(function ($count, $name) {
            return [
                'name' => $name,
                'count' => $count
            ];
        })->values();
        $initData->put('popularTags', $tags);
        $topUsers = User::leftJoin('scores', 'users.id', '=', 'scores.user_id')
            ->selectRaw('users.first_name, users.last_name, users.username, users.profile_pic, sum(scores.score) AS sum_score')
            ->where('scores.created_at', '>', now()->subDays(30)->endOfDay())
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.username')
            ->orderBy('sum_score', 'DESC')
            ->take(20)
            ->get();
        $initData->put('topUsers', $topUsers);
        // TODO add my tags
        return response()->json(['message' => 'Success', 'initData' => $initData], 200);
    }

    public function create(Request $request)
    {
        $user = auth('api')->user();
        $validData = Validator::make($request->all(), [
            'subject' => 'required|string|min:5|max:255',
            'category' => 'required|exists:question_categories,id',
            'question' => 'required|min:10',
            'tags' => 'nullable|array|max:3',
            'tags.*' => 'string|max:20',
            'is_private' => 'required|boolean',
            'mention_users' => 'nullable|array',
            'mention_users.*' => 'string|exists:users,username',
        ]);
        if (!$validData->passes()) {
            $errors = $validData->errors()->toArray();
            // for replace mention_users and tags to values for example mention_users.0 => milad and tags.0 => php
            foreach ($errors as $key => &$messages) {
                $patterns = [
                    'mention_users' => $request->mention_users,
                    'tags' => $request->tags,
                ];
                foreach ($patterns as $pattern => $values) {
                    if (strpos($key, "$pattern.") === 0) {
                        $index = explode('.', $key)[1];
                        $replacement = $values[$index] ?? $key;
                        foreach ($messages as &$message) {
                            $message = str_replace("$pattern.$index", $replacement, $message);
                        }
                        break;
                    }
                }
            }
            return response()->json(['message' => 'Error', 'errors' => $errors], 422);
        } else {

            $question = new Question();
            $question->user_id = $user->id;
            $question->subject = $request->subject;
            $question->category_id = $request->category;
            $question->question = $request->question;

            $question->is_private = $request->is_private;

            if ($request->is_private && !empty($request->mention_users)) {
                $usernames = array_map(function ($username) {
                    return str_replace('@', '', $username);
                }, $request->mention_users);

                $userIds = User::whereIn('username', $usernames)->pluck('id')->toArray();
                $question->allowed_user_ids = json_encode($userIds);
            }

            $question->save();

            if ($request->tags)
                $question->tag($request->tags);

            return response()->json(['message' => 'Success', 'question' => $question], 200);
        }
    }

    public function getQuestionForEdit(Request $request, $question)
    {
        $user = auth('api')->user();

        if (!$question || !$question->isEditableBy($user)) {
            return response()->json(['message' => 'Error! ًQuestion not found.'], 404);
        }

        $allowedUserIds = json_decode($question->allowed_user_ids, true);

        $allowCheck = !$question->is_private || ($user && ($question->user_id === $user->id || (is_array($allowedUserIds) && in_array($user->id, $allowedUserIds))));
        if (!$allowCheck) {
            return response()->json(['message' => '!Error Question not found.'], 404);
        }
        $questionUser = $question->user()->select('first_name', 'last_name', 'username', 'profile_pic')->first();

        $tags = $question->tags->map(function ($tag) {
            return [
                'id' => $tag->tag_id,
                'name' => $tag->name,
                'normalized' => $tag->normalized,
            ];
        });
        $isEditable = $question->isEditableBy($user);

        $usernames = [];
        if (!empty($allowedUserIds)) {
            $usernames = User::whereIn('id', $allowedUserIds)->pluck('username')->toArray();
        }

        $tags = $question->tags->pluck('name')->toArray();

        $questionData = [
            'id' => $question->id,
            'subject' => $question->subject,
            'slug' => $question->slug,
            'category_id' => $question->category_id,
            'question' => $question->question,
            'is_private' => boolval($question->is_private),
            'created_at' => $question->created_at,
            'updated_at' => $question->updated_at,
            'user' => $questionUser,
            'tags' => $tags,
            'is_editable' => $isEditable,
            'allow_user_usernames' => $usernames,
        ];

        return response()->json(['message' => 'Success', 'question' => $questionData,], 200);
    }

    public function updateQuestion(Request $request, $question)
    {
        $user = auth('api')->user();
        if (!$question->isEditableBy($user)) {
            return response()->json(['message' => 'Error! Question Not Found.'], 404);
        }
        $validData = Validator::make($request->all(), [
            'subject' => 'required|string|min:5|max:255',
            'category' => 'required|exists:question_categories,id',
            'question' => 'required|min:10',
            'tags' => 'nullable|array|max:3',
            'tags.*' => 'string|max:20',
            'is_private' => 'required|boolean',
            'mention_users' => 'nullable|array',
            'mention_users.*' => 'string|exists:users,username',
        ]);
        if (!$validData->passes()) {
            $errors = $validData->errors()->toArray();
            // for replace mention_users and tags to values for example mention_users.0 => milad and tags.0 => php
            foreach ($errors as $key => &$messages) {
                $patterns = [
                    'mention_users' => $request->mention_users,
                    'tags' => $request->tags,
                ];
                foreach ($patterns as $pattern => $values) {
                    if (strpos($key, "$pattern.") === 0) {
                        $index = explode('.', $key)[1];
                        $replacement = $values[$index] ?? $key;
                        foreach ($messages as &$message) {
                            $message = str_replace("$pattern.$index", $replacement, $message);
                        }
                        break;
                    }
                }
            }
            return response()->json(['message' => 'Error', 'errors' => $errors], 422);
        } else {
            $question->subject = $request->subject;
            $question->category_id = $request->category;
            $question->question = $request->question;

            $question->is_private = $request->is_private;

            if ($request->is_private && !empty($request->mention_users)) {
                $usernames = array_map(function ($username) {
                    return str_replace('@', '', $username);
                }, $request->mention_users);

                $userIds = User::whereIn('username', $usernames)->pluck('id')->toArray();
                $question->allowed_user_ids = json_encode($userIds);
            } else {
                $question->is_private = false;
                $question->allowed_user_ids = null;
            }

            $question->save();

            if ($request->tags)
                $question->retag($request->tags);
            else
                $question->detag();

            return response()->json(['message' => 'Success', 'question' => $question], 200);
        }
    }

    public function updateAnswer(Request $request)
    {
        $answerId = $request->id;
        $answer = Answer::findOrFail($answerId);
        $user = auth('api')->user();
        if (!$answer || !$answer->isEditableBy($user)) {
            return response()->json(['message' => 'Error! Answer Not Found.'], 404);
        }
        $validData = Validator::make($request->all(), [
            'id' => 'required|exists:answers,id',
            'answer' => 'required|min:5',
        ]);
        if (!$validData->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validData->errors()->toArray()], 422);
        }

        $answer->answer = $request->answer;
        $answer->save();
        $answerUser = $answer->user->only(['id', 'first_name', 'last_name', 'username', 'profile_pic']);
        $answerData = $answer->only(['id', 'answer', 'user_id', 'question_id', 'parent_id', 'pinned_at', 'created_at', 'updated_at']);
        $answerData['user'] = $answerUser;
        $answerData['likes_count'] = $answer->likesCount();
        $answerData['is_editable'] = $answer->isEditableBy($user);
        return response()->json(['message' => 'Success', 'answer' => $answerData], 200);

    }


    public function setBestAnswer(Request $request, $question){
        $user = auth('api')->user();
        $validData = Validator::make($request->all(), [
            'answer_id' => 'required|exists:answers,id',
        ]);
        if (!$validData->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validData->errors()->toArray()], 422);
        }
        $answer = Answer::findOrFail($request->input('answer_id'));
        if ($question->user_id == $user->id && $answer->question_id == $question->id && is_null($question->best_answer)) {
            $question->best_answer = $answer->id;
            $question->save();

            event(new SelectBestAnswer($answer));

            return response()->json(['message' => 'Success. Best Answer Set.'], 200);
        }
        return response()->json(['message' => 'Error. You are not allowed to set best answer.'], 403);
    }

    public function deleteQuestion(Request $request, $question)
    {
        $user = auth('api')->user();
        if (!$question || !$question->isEditableBy($user)) {
            return response()->json(['message' => 'Error! Question Not Found.'], 404);
        }
        $validData = Validator::make($request->all(), [
            'id' => 'required|exists:questions,id',
        ]);
        if (!$validData->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validData->errors()->toArray()], 422);
        }
        $question->detag();
        $question->answers()->delete();
        $question->delete();
        return response()->json(['message' => 'Success'], 200);
    }

    public function deleteAnswer(Request $request)
    {
        $answerId = $request->input('id');
        $answer = Answer::findOrFail($answerId);
        $user = auth('api')->user();
        if (!$answer || !$answer->isEditableBy($user)) {
            return response()->json(['message' => 'Error! Answer Not Found.'], 404);
        }
        $validData = Validator::make($request->all(), [
            'id' => 'required|exists:answers,id',
        ]);
        if (!$validData->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validData->errors()->toArray()], 422);
        }
        $answer->delete();
        return response()->json(['message' => 'Success'], 200);

    }


    public function togglePin(Request $request)
    {
        $user = auth('api')->user();
        $validData = Validator::make($request->all(), [
            'answer_id' => 'required|exists:answers,id',
        ]);
        if (!$validData->passes()) {
            $errors = $validData->errors()->toArray();

            return response()->json(['message' => 'Error', 'errors' => $validData->errors()->toArray()], 422);
        }
        $answerId = $request->input('answer_id');
        $answer = Answer::findOrFail($answerId);

        if ($answer->question->user_id !== $user->id) { // check if user owned the question
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $answer->togglePin();

        $answerData = $answer->only(['id', 'answer', 'user_id', 'question_id', 'parent_id', 'pinned_at', 'created_at', 'updated_at']);
        $answerData['user'] = $answer->user->only(['id', 'first_name', 'last_name', 'username', 'profile_pic']);
        $answerData['likes_count'] = $answer->likesCount();

        return response()->json([
            'message' => 'Success',
            'answer' => $answerData
        ]);
    }

    public function similarQuestions(Request $request)
    {
        $keyword = $request->keyword;
        if ($keyword == '') {
            $similarQuestions = collect();
        } else {
            $similarQuestions = Question::select('subject', 'slug')->where('subject', 'LIKE', '%' . $keyword . '%')->orWhere('question', 'LIKE', '%' . $keyword . '%')->orderBy('updated_at', 'desc')->get();
        }

        return response()->json(['message' => 'Success', 'similarQuestions' => $similarQuestions], 200);
    }
}
