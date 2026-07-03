<?php

namespace App\Http\Controllers\Api\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Quiz\Quiz;
use App\Models\Quiz\QuizAttempt;
use App\Models\Quiz\QuizQuestion;
use App\Services\Quiz\QuizAttemptService;
use Illuminate\Http\Request;

class StudentQuizController extends Controller
{
    public function __construct(protected QuizAttemptService $attempts) {}

    public function show(Request $request, Quiz $quiz)
    {
        $this->authorize('viewForStudent', $quiz);

        $activeAttempt = $this->attempts->activeAttempt($quiz, $request->user());

        return response()->json([
            'message' => 'Success',
            'quiz' => $quiz->toStudentSummary(),
            'active_attempt_uuid' => $activeAttempt?->uuid,
            'user_attempts_count' => $this->attempts->countUserAttempts($quiz, $request->user()),
            'attempts_exhausted' => $this->attempts->userAttemptsExhausted($quiz, $request->user()),
            'can_start_new_attempt' => $this->attempts->userCanStartNewAttempt($quiz, $request->user()),
        ]);
    }

    public function start(Request $request, Quiz $quiz)
    {
        $this->authorize('viewForStudent', $quiz);

        if (! $quiz->isAvailable()) {
            abort(404);
        }

        if ($this->attempts->userAttemptsExhausted($quiz, $request->user())) {
            abort(404);
        }

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
        $resultAvailable = $quiz->canShowResultsForAttempt($attempt);
        $showAnswers = $resultAvailable && $quiz->canShowCorrectAnswersForAttempt($attempt);
        $showQuestions = $resultAvailable && (bool) $quiz->show_questions_in_result;

        $payload = [
            'uuid' => $attempt->uuid,
            'status' => $attempt->status,
            'result_available' => $resultAvailable,
            'show_correct_answers' => $showAnswers,
            'show_questions_in_result' => $showQuestions,
            'result_message' => $resultAvailable ? null : $quiz->resultUnavailableMessage(),
            'score' => $resultAvailable ? $attempt->score : null,
            'max_score' => $resultAvailable ? $attempt->max_score : null,
            'percentage' => $resultAvailable ? $attempt->percentage : null,
            'passed' => $resultAvailable ? $attempt->passed : null,
            'time_spent' => $attempt->time_spent,
            'submitted_at' => $attempt->submitted_at,
            'completed_at' => $attempt->completed_at,
            'attempt_number' => $attempt->attempt_number,
            'result_display' => $quiz->result_display,
            'quiz' => $quiz,
        ];

        if ($showQuestions) {
            $payload['answers'] = $attempt->answers->map(function ($a) use ($showAnswers) {
                $item = [
                    'question_id' => $a->question_id,
                    'score' => $a->score,
                    'max_score' => $a->max_score,
                    'question' => $this->transformResultQuestion($a->question, $showAnswers),
                ];

                if ($a->reviewer_comment) {
                    $item['reviewer_comment'] = $a->reviewer_comment;
                }

                if ($showAnswers) {
                    $item['answer'] = $a->answer;
                    $item['is_correct'] = $a->is_correct;
                }

                return $item;
            });
        }

        return $payload;
    }

    protected function transformResultQuestion(?QuizQuestion $question, bool $showAnswers): ?array
    {
        if (! $question) {
            return null;
        }

        $base = [
            'id' => $question->id,
            'type' => $question->type,
            'text' => $question->text,
        ];

        if (! $showAnswers) {
            return $base;
        }

        $question->loadMissing('options');

        $base['explanation'] = $question->explanation;
        $base['options'] = $question->options->map(fn ($opt) => [
            'id' => $opt->id,
            'text' => $opt->text,
            'is_correct' => (bool) $opt->is_correct,
            'blank_index' => $opt->blank_index,
            'match_key' => $opt->match_key,
            'match_value' => $opt->match_value,
            'correct_position' => $opt->correct_position,
        ])->values()->all();

        return $base;
    }
}
