<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiscussController extends Controller
{
    // Get all questions with pagination and filters
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 15);
        $search = $request->input('search', '');
        $categoryId = $request->input('category_id', null);
        $publish = $request->input('publish', null);
        $isPrivate = $request->input('is_private', null);
        $hasBestAnswer = $request->input('has_best_answer', null);

        $query = Question::with(['user:id,first_name,last_name,username,profile_pic', 'category:id,title,english_title'])
            ->withCount('answers');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('question', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($publish !== null) {
            $query->where('publish', $publish);
        }

        if ($isPrivate !== null) {
            $query->where('is_private', $isPrivate);
        }

        if ($hasBestAnswer !== null) {
            if ($hasBestAnswer) {
                $query->whereNotNull('best_answer');
            } else {
                $query->whereNull('best_answer');
            }
        }

        $questions = $query->orderBy('id', 'desc')->paginate($perPage);

        $questions->getCollection()->transform(function ($question) {
            return [
                'id' => $question->id,
                'subject' => $question->subject,
                'slug' => $question->slug,
                'question' => $question->question,
                'meta_keywords' => $question->meta_keywords,
                'best_answer' => $question->best_answer,
                'is_private' => $question->is_private,
                'publish' => $question->publish,
                'allowed_user_ids' => $question->allowed_user_ids,
                'created_at' => $question->created_at,
                'updated_at' => $question->updated_at,
                'user' => $question->user,
                'category' => $question->category,
                'answers_count' => $question->answers_count,
                'has_best_answer' => $question->best_answer !== null,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'questions' => $questions,
        ], 200);
    }

    // Get single question with answers
    public function show(Question $question)
    {
        $question->load([
            'user:id,first_name,last_name,username,profile_pic',
            'category:id,title,english_title,slug',
            'answers' => function ($query) {
                $query->with(['user:id,first_name,last_name,username,profile_pic'])
                    ->orderBy('pinned_at', 'desc')
                    ->orderBy('created_at', 'desc');
            }
        ]);

        $question->loadCount('answers');

        $tags = $question->tags->map(function ($tag) {
            return [
                'id' => $tag->tag_id,
                'name' => $tag->name,
                'normalized' => $tag->normalized,
            ];
        });

        // Get allowed users if question is private
        $allowedUsers = [];
        if ($question->is_private && $question->allowed_user_ids) {
            // allowed_user_ids is cast to array in model, so it's already an array
            $userIds = $question->allowed_user_ids;
            
            // Ensure it's an array (in case cast didn't work)
            if (!is_array($userIds)) {
                $userIds = json_decode($userIds, true) ?? [];
            }
            
            // Filter out null values and ensure we have valid IDs
            $userIds = array_filter($userIds, function($id) {
                return !is_null($id) && $id !== '' && is_numeric($id);
            });
            
            if (!empty($userIds)) {
                $allowedUsers = User::whereIn('id', $userIds)
                    ->select('id', 'first_name', 'last_name', 'username', 'profile_pic')
                    ->get();
            }
        }

        return response()->json([
            'message' => 'Success',
            'question' => [
                'id' => $question->id,
                'subject' => $question->subject,
                'slug' => $question->slug,
                'question' => $question->question,
                'meta_keywords' => $question->meta_keywords,
                'best_answer' => $question->best_answer,
                'is_private' => $question->is_private,
                'publish' => $question->publish,
                'allowed_user_ids' => $question->allowed_user_ids,
                'created_at' => $question->created_at,
                'updated_at' => $question->updated_at,
                'user' => $question->user,
                'category' => $question->category,
                'answers' => $question->answers,
                'answers_count' => $question->answers_count,
                'tags' => $tags,
                'allowed_users' => $allowedUsers,
            ],
        ], 200);
    }

    // Create question
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:question_categories,id',
            'subject' => 'required|string|min:5|max:255',
            'question' => 'required|string|min:10',
            'meta_keywords' => 'nullable|string',
            'is_private' => 'nullable|boolean',
            'publish' => 'nullable|boolean',
            'allowed_user_ids' => 'nullable|array',
            'allowed_user_ids.*' => 'exists:users,id',
            'tags' => 'nullable|array|max:3',
            'tags.*' => 'string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $question = Question::create([
            'user_id' => $request->user_id,
            'category_id' => $request->category_id,
            'subject' => $request->subject,
            'question' => $request->question,
            'meta_keywords' => $request->meta_keywords,
            'is_private' => $request->is_private ?? false,
            'publish' => $request->publish ?? true,
            'allowed_user_ids' => $request->allowed_user_ids ? json_encode($request->allowed_user_ids) : null,
        ]);

        if ($request->tags) {
            $question->tag($request->tags);
        }

        $question->load(['user:id,first_name,last_name,username,profile_pic', 'category:id,title,english_title']);

        return response()->json([
            'message' => 'Question created successfully',
            'question' => $question,
        ], 201);
    }

    // Update question
    public function update(Request $request, Question $question)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:users,id',
            'category_id' => 'sometimes|exists:question_categories,id',
            'subject' => 'sometimes|string|min:5|max:255',
            'question' => 'sometimes|string|min:10',
            'meta_keywords' => 'nullable|string',
            'is_private' => 'nullable|boolean',
            'publish' => 'nullable|boolean',
            'allowed_user_ids' => 'nullable|array',
            'allowed_user_ids.*' => 'exists:users,id',
            'tags' => 'nullable|array|max:3',
            'tags.*' => 'string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = [];
        if ($request->has('user_id')) $updateData['user_id'] = $request->user_id;
        if ($request->has('category_id')) $updateData['category_id'] = $request->category_id;
        if ($request->has('subject')) $updateData['subject'] = $request->subject;
        if ($request->has('question')) $updateData['question'] = $request->question;
        if ($request->has('meta_keywords')) $updateData['meta_keywords'] = $request->meta_keywords;
        if ($request->has('is_private')) $updateData['is_private'] = $request->is_private;
        if ($request->has('publish')) $updateData['publish'] = $request->publish;
        if ($request->has('allowed_user_ids')) {
            $updateData['allowed_user_ids'] = $request->allowed_user_ids ? json_encode($request->allowed_user_ids) : null;
        }

        $question->update($updateData);

        if ($request->has('tags')) {
            if ($request->tags) {
                $question->retag($request->tags);
            } else {
                $question->detag();
            }
        }

        $question->load(['user:id,first_name,last_name,username,profile_pic', 'category:id,title,english_title']);

        return response()->json([
            'message' => 'Question updated successfully',
            'question' => $question,
        ], 200);
    }

    // Delete question
    public function delete(Question $question)
    {
        $question->detag();
        $question->answers()->delete();
        $question->delete();

        return response()->json([
            'message' => 'Question deleted successfully',
        ], 200);
    }

    // Toggle publish status
    public function togglePublish(Question $question)
    {
        $question->publish = !$question->publish;
        $question->save();

        return response()->json([
            'message' => 'Question publish status updated',
            'publish' => $question->publish,
        ], 200);
    }

    // Set best answer
    public function setBestAnswer(Request $request, Question $question)
    {
        $validator = Validator::make($request->all(), [
            'answer_id' => 'required|exists:answers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $answer = Answer::findOrFail($request->answer_id);
        
        if ($answer->question_id !== $question->id) {
            return response()->json([
                'message' => 'Answer does not belong to this question',
            ], 422);
        }

        $question->best_answer = $answer->id;
        $question->save();

        return response()->json([
            'message' => 'Best answer set successfully',
            'question' => $question,
        ], 200);
    }

    // Remove best answer
    public function removeBestAnswer(Question $question)
    {
        $question->best_answer = null;
        $question->save();

        return response()->json([
            'message' => 'Best answer removed successfully',
            'question' => $question,
        ], 200);
    }

    // Get answers for a question
    public function getAnswers(Request $request, Question $question)
    {
        $perPage = $request->input('perPage', 10);
        $page = $request->input('page', 1);

        // Get best answer if exists
        $bestAnswer = null;
        if ($question->best_answer) {
            $bestAnswer = $question->answers()
                ->where('id', $question->best_answer)
                ->with(['user:id,first_name,last_name,username,profile_pic'])
                ->first();
        }

        // Get pinned answers (excluding best answer)
        $pinnedAnswers = $question->answers()
            ->whereNotNull('pinned_at')
            ->where('id', '!=', $question->best_answer ?? 0)
            ->with(['user:id,first_name,last_name,username,profile_pic'])
            ->orderBy('pinned_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $excludeIds = $pinnedAnswers->pluck('id')->all();
        if ($question->best_answer) {
            $excludeIds[] = $question->best_answer;
        }
        $excludeIds = array_values(array_unique($excludeIds));

        $answersQuery = $question->answers()
            ->with(['user:id,first_name,last_name,username,profile_pic'])
            ->orderBy('created_at', 'desc');

        if (! empty($excludeIds)) {
            $answersQuery->whereNotIn('id', $excludeIds);
        }

        $answers = $answersQuery->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Success',
            'answers' => $answers,
            'best_answer' => $bestAnswer,
            'pinned_answers' => $pinnedAnswers,
        ], 200);
    }

    // Create answer
    public function createAnswer(Request $request, Question $question)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'answer' => 'required|string|min:5',
            'parent_id' => 'nullable|exists:answers,id',
            'publish' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $answerData = [
            'user_id' => $request->user_id,
            'question_id' => $question->id,
            'answer' => $request->answer,
            'publish' => $request->publish ?? true,
        ];

        // Only set parent_id if it's provided and not null
        if ($request->has('parent_id') && $request->parent_id !== null) {
            $answerData['parent_id'] = $request->parent_id;
        }

        $answer = Answer::create($answerData);

        $answer->load(['user:id,first_name,last_name,username,profile_pic']);

        return response()->json([
            'message' => 'Answer created successfully',
            'answer' => $answer,
        ], 201);
    }

    // Update answer
    public function updateAnswer(Request $request, Answer $answer)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:users,id',
            'answer' => 'sometimes|string|min:5',
            'publish' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = [];
        if ($request->has('user_id')) $updateData['user_id'] = $request->user_id;
        if ($request->has('answer')) $updateData['answer'] = $request->answer;
        if ($request->has('publish')) $updateData['publish'] = $request->publish;

        $answer->update($updateData);
        $answer->load(['user:id,first_name,last_name,username,profile_pic']);

        return response()->json([
            'message' => 'Answer updated successfully',
            'answer' => $answer,
        ], 200);
    }

    // Delete answer
    public function deleteAnswer(Answer $answer)
    {
        $answer->delete();

        return response()->json([
            'message' => 'Answer deleted successfully',
        ], 200);
    }

    // Toggle answer pin
    public function togglePinAnswer(Answer $answer)
    {
        $answer->togglePin();

        return response()->json([
            'message' => 'Answer pin status updated',
            'pinned_at' => $answer->pinned_at,
        ], 200);
    }

    // Toggle answer publish
    public function togglePublishAnswer(Answer $answer)
    {
        $answer->publish = !$answer->publish;
        $answer->save();

        return response()->json([
            'message' => 'Answer publish status updated',
            'publish' => $answer->publish,
        ], 200);
    }
}

