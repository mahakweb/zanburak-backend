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
            throw ValidationException::withMessages(['quiz' => 'شما مجاز به شرکت در این آزمون نیستید.']);
        }

        $inProgress = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        if ($inProgress) {
            return $this->loadAttemptForStudent($inProgress);
        }

        if (! $quiz->hasUnlimitedAttempts()) {
            $used = QuizAttempt::where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->whereNotIn('status', ['abandoned'])
                ->count();

            if ($used >= $quiz->max_attempts) {
                throw ValidationException::withMessages(['quiz' => 'حداکثر تعداد تلاش‌های مجاز را استفاده کرده‌اید.']);
            }
        }

        $attemptNumber = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->max('attempt_number') + 1;

        $questionOrder = $this->buildQuestionOrder($quiz);

        $startedAt = now();
        $expiresAt = $quiz->time_limit
            ? $startedAt->copy()->addSeconds($quiz->time_limit)
            : null;

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

    public function saveProgress(QuizAttempt $attempt, array $answers, int $timeSpent = 0): QuizAttempt
    {
        $this->assertInProgress($attempt);

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
        $this->assertInProgress($attempt);
        $attempt->load(['quiz.questions', 'answers.question.options']);

        return DB::transaction(function () use ($attempt, $answers, $timeSpent) {
            $this->saveProgress($attempt, $answers, $timeSpent);
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

    public function expireStaleAttempts(): int
    {
        return QuizAttempt::query()
            ->where('status', 'in_progress')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);
    }

    public function loadAttemptForStudent(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->load([
            'quiz' => fn ($q) => $q->select([
                'id', 'uuid', 'title', 'description', 'time_limit', 'max_attempts',
                'result_display', 'show_correct_answers', 'passing_score', 'passing_percentage',
                'randomize_answers', 'manual_review_required', 'total_score', 'settings',
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
        if ($attempt->isExpired()) {
            $attempt->update(['status' => 'expired']);
            throw ValidationException::withMessages(['attempt' => 'مهلت آزمون به پایان رسیده است.']);
        }
        if (! $attempt->isInProgress()) {
            throw ValidationException::withMessages(['attempt' => 'این تلاش دیگر فعال نیست.']);
        }
    }
}
