<?php

namespace App\Http\Controllers\Api\Admin\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Quiz\QuizQuestion;
use App\Models\Quiz\QuizQuestionCategory;
use App\Models\Quiz\QuizTag;
use App\Services\Quiz\QuizAdminService;
use App\Support\Quiz\QuizConstants;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuizQuestionController extends Controller
{
    public function __construct(protected QuizAdminService $admin) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', QuizQuestion::class);

        $query = QuizQuestion::with(['category', 'tags'])
            ->withCount(['options', 'quizzes'])
            ->when($request->filled('search'), fn ($q) => $q->where('text', 'like', '%'.$request->search.'%'))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('difficulty'), fn ($q) => $q->where('difficulty', $request->difficulty))
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('tag_id'), fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('quiz_tags.id', $request->tag_id)));

        return response()->json([
            'message' => 'Success',
            'questions' => $query->latest()->paginate((int) $request->input('perPage', 20)),
            'types' => QuizConstants::QUESTION_TYPES,
            'difficulties' => QuizConstants::DIFFICULTIES,
        ]);
    }

    public function show(QuizQuestion $question)
    {
        $this->authorize('viewAny', QuizQuestion::class);
        $question->load(['options', 'tags', 'category']);

        return response()->json(['message' => 'Success', 'question' => $question]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', QuizQuestion::class);
        $data = $this->validatedQuestion($request);
        $data['created_by'] = $request->user()->id;

        $question = $this->admin->createQuestion($data);

        return response()->json(['message' => 'Question created', 'question' => $question], 201);
    }

    public function update(Request $request, QuizQuestion $question)
    {
        $this->authorize('update', $question);
        $question = $this->admin->updateQuestion($question, $this->validatedQuestion($request, $question));

        return response()->json(['message' => 'Question updated', 'question' => $question]);
    }

    public function destroy(QuizQuestion $question)
    {
        $this->authorize('delete', $question);
        $question->delete();

        return response()->json(['message' => 'Question deleted']);
    }

    public function categories(Request $request)
    {
        $this->authorize('viewAny', QuizQuestion::class);

        return response()->json([
            'categories' => QuizQuestionCategory::with('children')->whereNull('parent_id')->get(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $this->authorize('create', QuizQuestion::class);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:quiz_question_categories,id'],
            'description' => ['nullable', 'string'],
        ]);

        $category = $this->admin->upsertCategory($data);

        return response()->json(['message' => 'Category saved', 'category' => $category], 201);
    }

    public function tags()
    {
        $this->authorize('viewAny', QuizQuestion::class);

        return response()->json(['tags' => QuizTag::orderBy('name')->get()]);
    }

    protected function validatedQuestion(Request $request, ?QuizQuestion $question = null): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'exists:quiz_question_categories,id'],
            'type' => [$question ? 'sometimes' : 'required', Rule::in(QuizConstants::QUESTION_TYPES)],
            'text' => [$question ? 'sometimes' : 'required', 'string'],
            'explanation' => ['nullable', 'string'],
            'difficulty' => [Rule::in(QuizConstants::DIFFICULTIES)],
            'default_score' => ['nullable', 'numeric', 'min:0'],
            'settings' => ['nullable', 'array'],
            'requires_manual_review' => ['boolean'],
            'is_active' => ['boolean'],
            'options' => ['nullable', 'array'],
            'options.*.text' => ['nullable', 'string'],
            'options.*.is_correct' => ['boolean'],
            'options.*.blank_index' => ['nullable', 'integer', 'min:0'],
            'options.*.match_key' => ['nullable', 'string'],
            'options.*.match_value' => ['nullable', 'string'],
            'options.*.correct_position' => ['nullable', 'integer', 'min:0'],
            'options.*.feedback' => ['nullable', 'string'],
            'options.*.position' => ['nullable', 'integer', 'min:0'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:quiz_tags,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
        ]);
    }
}
