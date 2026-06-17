<?php

namespace App\Http\Controllers\Api\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Quiz\Quiz;
use App\Models\Quiz\QuizAttempt;
use App\Services\Quiz\QuizAttemptService;
use Illuminate\Http\Request;

class StudentQuizController extends Controller
{
    public function __construct(protected QuizAttemptService $attempts) {}

    public function show(Request $request, Quiz $quiz)
    {
        $this->authorize('take', $quiz);

        $activeAttempt = $this->attempts->activeAttempt($quiz, $request->user());

        return response()->json([
            'message' => 'Success',
            'quiz' => $quiz->toStudentSummary(),
            'active_attempt_uuid' => $activeAttempt?->uuid,
        ]);
    }

    public function start(Request $request, Quiz $quiz)
    {
        $this->authorize('take', $quiz);
        $attempt = $this->attempts->start($quiz, $request->user());

        return response()->json(['message' => 'Attempt started', 'attempt' => $this->transformAttempt($attempt)]);
    }

    public function save(Request $request, QuizAttempt $attempt)
    {
        $this->authorize('view', $attempt);
        $data = $this->validatedAnswers($request);
        $attempt = $this->attempts->saveProgress($attempt, $data['answers'], $data['time_spent'] ?? 0);

        return response()->json(['message' => 'Progress saved', 'attempt' => $this->transformAttempt($attempt)]);
    }

    public function submit(Request $request, QuizAttempt $attempt)
    {
        $this->authorize('view', $attempt);
        $data = $this->validatedAnswers($request);
        $attempt = $this->attempts->submit($attempt, $data['answers'], $data['time_spent'] ?? 0);

        return response()->json([
            'message' => 'Quiz submitted',
            'attempt' => $this->transformResult($attempt, $request->user()->id),
        ]);
    }

    public function resume(QuizAttempt $attempt)
    {
        $this->authorize('view', $attempt);
        $attempt = $this->attempts->resume($attempt);

        return response()->json(['message' => 'Attempt resumed', 'attempt' => $this->transformAttempt($attempt)]);
    }

    public function result(Request $request, QuizAttempt $attempt)
    {
        $this->authorize('view', $attempt);
        $attempt->load(['answers.question.options', 'quiz']);

        return response()->json([
            'message' => 'Success',
            'attempt' => $this->transformResult($attempt, $request->user()->id),
        ]);
    }

    public function history(Request $request)
    {
        $history = $this->attempts->history(
            $request->user(),
            $request->integer('quiz_id') ?: null
        );

        return response()->json(['message' => 'Success', 'history' => $history]);
    }

    protected function validatedAnswers(Request $request): array
    {
        return $request->validate([
            'time_spent' => ['nullable', 'integer', 'min:0'],
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer', 'exists:quiz_questions,id'],
            'answers.*.answer' => ['nullable', 'array'],
        ]);
    }

    protected function transformAttempt(QuizAttempt $attempt): array
    {
        return [
            'uuid' => $attempt->uuid,
            'status' => $attempt->status,
            'attempt_number' => $attempt->attempt_number,
            'started_at' => $attempt->started_at,
            'expires_at' => $attempt->expires_at,
            'time_spent' => $attempt->time_spent,
            'question_order' => $attempt->question_order,
            'quiz' => $attempt->quiz,
            'answers' => $attempt->answers->map(fn ($a) => [
                'question_id' => $a->question_id,
                'answer' => $a->answer,
                'answered_at' => $a->answered_at,
                'question' => $a->question,
            ]),
        ];
    }

    protected function transformResult(QuizAttempt $attempt, int $userId): array
    {
        $quiz = $attempt->quiz;
        $showAnswers = $quiz->show_correct_answers
            && in_array($quiz->result_display, ['immediately', 'after_review'], true)
            && ($attempt->status === 'completed' || ($attempt->status === 'grading' && $quiz->result_display === 'after_review'));

        $payload = [
            'uuid' => $attempt->uuid,
            'status' => $attempt->status,
            'score' => $attempt->score,
            'max_score' => $attempt->max_score,
            'percentage' => $attempt->percentage,
            'passed' => $attempt->passed,
            'time_spent' => $attempt->time_spent,
            'submitted_at' => $attempt->submitted_at,
            'completed_at' => $attempt->completed_at,
            'quiz' => $quiz,
        ];

        if ($showAnswers) {
            $payload['answers'] = $attempt->answers->map(fn ($a) => [
                'question_id' => $a->question_id,
                'answer' => $a->answer,
                'is_correct' => $a->is_correct,
                'score' => $a->score,
                'max_score' => $a->max_score,
                'question' => $a->question,
            ]);
        }

        return $payload;
    }
}
