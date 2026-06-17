<?php

namespace App\Http\Controllers\Api\Admin\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Quiz\Quiz;
use App\Services\Quiz\QuizAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Support\Quiz\QuizConstants;

class QuizController extends Controller
{
    public function __construct(protected QuizAdminService $admin) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Quiz::class);

        $query = Quiz::query()
            ->with(['quizzable'])
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->search.'%'))
            ->when($request->filled('quizzable_type'), fn ($q) => $q->where('quizzable_type', QuizConstants::QUIZZABLE_TYPES[$request->quizzable_type] ?? $request->quizzable_type))
            ->when($request->filled('quizzable_id'), fn ($q) => $q->where('quizzable_id', $request->quizzable_id))
            ->when($request->published === 'yes', fn ($q) => $q->where('is_published', true))
            ->when($request->published === 'no', fn ($q) => $q->where('is_published', false));

        $quizzes = $query->latest()->paginate((int) $request->input('perPage', 20));

        return response()->json(['message' => 'Success', 'quizzes' => $quizzes]);
    }

    public function show(Quiz $quiz)
    {
        $this->authorize('view', $quiz);

        $quiz->load(['questions.options', 'questions.tags', 'quizzable']);

        return response()->json(['message' => 'Success', 'quiz' => $quiz]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Quiz::class);

        $data = $this->validatedQuiz($request);
        $data['created_by'] = $request->user()->id;
        $data['slug'] = $data['slug'] ?? Str::slug($data['title']);

        $quiz = $this->admin->createQuiz($data);

        return response()->json(['message' => 'Quiz created', 'quiz' => $quiz], 201);
    }

    public function update(Request $request, Quiz $quiz)
    {
        $this->authorize('update', $quiz);

        $quiz = $this->admin->updateQuiz($quiz, $this->validatedQuiz($request, $quiz));

        return response()->json(['message' => 'Quiz updated', 'quiz' => $quiz]);
    }

    public function destroy(Quiz $quiz)
    {
        $this->authorize('delete', $quiz);
        $quiz->delete();

        return response()->json(['message' => 'Quiz deleted']);
    }

    protected function validatedQuiz(Request $request, ?Quiz $quiz = null): array
    {
        return $request->validate([
            'title' => [$quiz ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'quizzable' => ['nullable', 'array'],
            'quizzable.type' => ['nullable', Rule::in(array_keys(QuizConstants::QUIZZABLE_TYPES))],
            'quizzable.id' => ['nullable', 'integer'],
            'passing_score' => ['nullable', 'numeric', 'min:0'],
            'passing_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'time_limit' => ['nullable', 'integer', 'min:1'],
            'max_attempts' => ['nullable', 'integer', 'min:1'],
            'randomize_questions' => ['boolean'],
            'randomize_answers' => ['boolean'],
            'result_display' => [Rule::in(QuizConstants::RESULT_DISPLAY)],
            'negative_scoring' => ['boolean'],
            'negative_scoring_factor' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'manual_review_required' => ['boolean'],
            'show_correct_answers' => ['boolean'],
            'is_published' => ['boolean'],
            'question_ids' => ['nullable', 'array'],
            'question_ids.*' => ['integer', 'exists:quiz_questions,id'],
            'settings' => ['nullable', 'array'],
        ]);
    }
}
