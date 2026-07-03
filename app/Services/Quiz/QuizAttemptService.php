<?php

namespace App\Services\Quiz;

use App\Models\Quiz\Quiz;
use App\Models\Quiz\QuizAttempt;
use App\Models\Quiz\QuizAttemptAnswer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizAttemptService
{
    public function __construct(
        protected QuizGradingService $grading
    ) {}

    public function start(Quiz $quiz, User $user): QuizAttempt
    {
        $this->expireStaleAttempts();

        if (! $quiz->userCanTake($user)) {
            if (! $quiz->isAvailable()) {
                abort(404);
            }

            throw ValidationException::withMessages(['quiz' => 'شما مجاز به شرکت در این آزمون نیستید.']);
        }

        $inProgress = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        if ($inProgress) {
            return $this->loadAttemptForStudent($inProgress);
        }

        if (! $quiz->hasUnlimitedAttempts() && $this->countedAttempts($quiz, $user) >= (int) $quiz->max_attempts) {
            abort(404);
        }

        $attemptNumber = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->max('attempt_number') + 1;

        $questionOrder = $this->buildQuestionOrder($quiz);

        $startedAt = now();
        $expiresAt = $this->computeExpiresAt($quiz, $startedAt);

        return DB::transaction(function () use ($quiz, $user, $attemptNumber, $questionOrder, $startedAt, $expiresAt) {
            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'attempt_number' => $attemptNumber,
                'status' => 'in_progress',
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
                'max_score' => $quiz->total_score,
                'question_order' => $questionOrder,
                'requires_manual_review' => $quiz->manual_review_required,
            ]);

            foreach ($questionOrder as $questionId) {
                QuizAttemptAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $questionId,
                    'max_score' => $this->questionMaxScore($quiz, (int) $questionId),
                ]);
            }

            return $this->loadAttemptForStudent($attempt);
        });
    }

    public function saveProgress(QuizAttempt $attempt, array $answers, int $timeSpent = 0, bool $forSubmit = false): QuizAttempt
    {
        if ($forSubmit) {
            $this->assertCanSubmit($attempt);
        } else {
            $this->assertInProgress($attempt);
        }

        $attempt->load(['quiz', 'answers.question.options']);

        DB::transaction(function () use ($attempt, $answers, $timeSpent) {
            foreach ($answers as $item) {
                $answerRow = $attempt->answers->firstWhere('question_id', $item['question_id']);
                if (! $answerRow) {
                    continue;
                }

                $answerRow->update([
                    'answer' => $item['answer'] ?? null,
                    'answered_at' => now(),
                ]);
            }

            $attempt->update(['time_spent' => max($attempt->time_spent, $timeSpent)]);
        });

        return $this->loadAttemptForStudent($attempt->fresh());
    }

    public function submit(QuizAttempt $attempt, array $answers, int $timeSpent = 0): QuizAttempt
    {
        $this->assertCanSubmit($attempt);
        $attempt->load(['quiz.questions', 'answers.question.options']);

        return DB::transaction(function () use ($attempt, $answers, $timeSpent) {
            $this->saveProgress($attempt, $answers, $timeSpent, forSubmit: true);
            $attempt->refresh()->load(['answers.question.options', 'quiz']);

            foreach ($attempt->answers as $answerRow) {
                $question = $answerRow->question;
                $result = $this->grading->gradeAnswer(
                    $question,
                    $answerRow->answer,
                    (float) $answerRow->max_score,
                    (bool) $attempt->quiz->negative_scoring,
                    (float) $attempt->quiz->negative_scoring_factor
                );

                $answerRow->update($result);
            }

            $attempt->update([
                'submitted_at' => now(),
                'time_spent' => max($attempt->time_spent, $timeSpent),
                'status' => 'submitted',
            ]);

            return $this->grading->finalizeAttempt($attempt->fresh(['answers', 'quiz']));
        });
    }

    public function resume(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->load('quiz');

        if (! $attempt->quiz->isAvailable()) {
            abort(404);
        }

        if ($attempt->isExpired()) {
            $attempt->update(['status' => 'expired']);
            throw ValidationException::withMessages(['attempt' => 'مهلت آزمون به پایان رسیده است.']);
        }

        if (! $attempt->isInProgress()) {
            throw ValidationException::withMessages(['attempt' => 'این تلاش قابل ادامه نیست.']);
        }

        return $this->loadAttemptForStudent($attempt);
    }

    public function history(User $user, ?int $quizId = null)
    {
        return QuizAttempt::with(['quiz:id,uuid,title'])
            ->where('user_id', $user->id)
            ->when($quizId, fn ($q) => $q->where('quiz_id', $quizId))
            ->whereIn('status', ['in_progress', 'completed', 'grading', 'submitted', 'expired'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    public function activeAttempt(Quiz $quiz, User $user): ?QuizAttempt
    {
        $this->expireStaleAttempts();

        return QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();
    }

    public function countUserAttempts(Quiz $quiz, User $user): int
    {
        return $this->countedAttempts($quiz, $user);
    }

    public function countedAttempts(Quiz $quiz, User $user): int
    {
        return QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'grading', 'completed', 'expired'])
            ->count();
    }

    public function userAttemptsExhausted(Quiz $quiz, User $user): bool
    {
        if ($quiz->hasUnlimitedAttempts()) {
            return false;
        }

        if ($this->activeAttempt($quiz, $user)) {
            return false;
        }

        return $this->countedAttempts($quiz, $user) >= (int) $quiz->max_attempts;
    }

    public function userCanStartNewAttempt(Quiz $quiz, User $user): bool
    {
        if (! $quiz->userCanTake($user)) {
            return false;
        }

        if ($this->activeAttempt($quiz, $user)) {
            return true;
        }

        if ($quiz->hasUnlimitedAttempts()) {
            return true;
        }

        return $this->countedAttempts($quiz, $user) < (int) $quiz->max_attempts;
    }

    public function expireStaleAttempts(): int
    {
        return QuizAttempt::query()
            ->where('status', 'in_progress')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->subSeconds(60))
            ->update(['status' => 'expired']);
    }

    public function loadAttemptForStudent(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->load([
            'quiz' => fn ($q) => $q->select([
                'id', 'uuid', 'title', 'description', 'time_limit', 'max_attempts', 'end_at',
                'result_display', 'show_correct_answers', 'passing_score', 'passing_percentage',
                'randomize_answers', 'manual_review_required', 'total_score', 'settings', 'questions_count',
            ]),
            'answers.question.options',
        ]);

        if ($attempt->quiz->randomize_answers) {
            foreach ($attempt->answers as $answer) {
                if ($answer->relationLoaded('question') && $answer->question) {
                    $answer->question->setRelation(
                        'options',
                        $answer->question->options->shuffle()->values()
                    );
                }
            }
        }

        return $attempt;
    }

    protected function computeExpiresAt(Quiz $quiz, \Illuminate\Support\Carbon $startedAt): ?\Illuminate\Support\Carbon
    {
        $candidates = [];

        if ($quiz->time_limit) {
            $candidates[] = $startedAt->copy()->addSeconds((int) $quiz->time_limit);
        }

        if ($quiz->end_at) {
            $candidates[] = $quiz->end_at->copy();
        }

        if ($candidates === []) {
            return null;
        }

        return collect($candidates)->sort()->first();
    }

    protected function buildQuestionOrder(Quiz $quiz): array
    {
        $quiz->load('questions');
        $ids = $quiz->questions->pluck('id')->map(fn ($id) => (int) $id)->values();

        if ($quiz->randomize_questions) {
            return $ids->shuffle()->values()->all();
        }

        return $ids->all();
    }

    protected function questionMaxScore(Quiz $quiz, int $questionId): float
    {
        $question = $quiz->questions->firstWhere('id', $questionId);

        return (float) ($question?->pivot?->score ?? $question?->default_score ?? 0);
    }

    protected function assertInProgress(QuizAttempt $attempt): void
    {
        if (! $attempt->isInProgress()) {
            throw ValidationException::withMessages(['attempt' => 'این تلاش دیگر فعال نیست.']);
        }

        if ($attempt->isExpired()) {
            throw ValidationException::withMessages(['attempt' => 'مهلت آزمون به پایان رسیده است.']);
        }
    }

    protected function assertCanSubmit(QuizAttempt $attempt): void
    {
        $attempt->refresh();

        if (! $attempt->isInProgress()) {
            throw ValidationException::withMessages(['attempt' => 'این تلاش دیگر فعال نیست.']);
        }
    }
}
