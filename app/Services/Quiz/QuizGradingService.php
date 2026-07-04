<?php

namespace App\Services\Quiz;

use App\Models\Quiz\Quiz;
use App\Models\Quiz\QuizAttempt;
use App\Models\Quiz\QuizAttemptAnswer;
use App\Models\Quiz\QuizQuestion;
use App\Support\Quiz\QuizConstants;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Auto-grades all machine-scorable question types.
 * Long answers always return needs_manual_review = true.
 */
class QuizGradingService
{
    public function gradeAnswer(
        QuizQuestion $question,
        ?array $answerPayload,
        float $maxScore,
        bool $negativeScoring = false,
        float $negativeFactor = 0
    ): array {
        if ($question->type === 'long_answer') {
            return [
                'is_correct' => null,
                'score' => 0,
                'max_score' => $maxScore,
                'needs_manual_review' => true,
            ];
        }

        if ($answerPayload === null || $answerPayload === []) {
            return $this->result(false, 0, $maxScore, $negativeScoring, $negativeFactor);
        }

        $question->loadMissing('options');

        $isCorrect = match ($question->type) {
            'single_choice', 'true_false' => $this->gradeSingleChoice($question, $answerPayload),
            'multiple_choice' => null,
            'short_answer' => $this->gradeShortAnswer($question, $answerPayload),
            'fill_blank' => $this->gradeFillBlank($question, $answerPayload),
            'matching' => $this->gradeMatching($question, $answerPayload),
            'ordering' => $this->gradeOrdering($question, $answerPayload),
            default => false,
        };

        if ($question->type === 'multiple_choice') {
            return $this->gradeMultipleChoicePartial(
                $question,
                $answerPayload,
                $maxScore,
                $negativeScoring,
                $negativeFactor
            );
        }

        if ($question->type === 'short_answer' && ($question->settings['manual_review'] ?? false)) {
            return [
                'is_correct' => null,
                'score' => 0,
                'max_score' => $maxScore,
                'needs_manual_review' => true,
            ];
        }

        return $this->result($isCorrect, $isCorrect ? $maxScore : 0, $maxScore, $negativeScoring, $negativeFactor);
    }

    public function finalizeAttempt(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->load(['answers', 'quiz']);

        $score = max(0, (float) $attempt->answers->sum('score'));
        $maxScore = (float) $attempt->answers->sum('max_score');
        $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0;

        $needsPerAnswerReview = $attempt->answers->contains(fn ($answer) => (bool) $answer->needs_manual_review);
        $needsAdminReview = $needsPerAnswerReview
            || (bool) $attempt->quiz->manual_review_required
            || $attempt->quiz->result_display === 'after_review';

        $passed = $this->determinePass($attempt->quiz, $score, $percentage);

        if ($needsAdminReview) {
            $status = 'grading';
            $completedAt = null;
            $passedValue = null;
        } elseif ($attempt->quiz->result_display === 'after_end') {
            $status = 'submitted';
            $completedAt = null;
            $passedValue = null;
        } else {
            $status = 'completed';
            $completedAt = now();
            $passedValue = $passed;
        }

        $attempt->update([
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'passed' => $passedValue,
            'requires_manual_review' => $needsAdminReview,
            'status' => $status,
            'completed_at' => $completedAt,
            'submitted_at' => $attempt->submitted_at ?? now(),
        ]);

        return $attempt->fresh(['answers', 'quiz']);
    }

    public function manualGradeAnswer(QuizAttemptAnswer $answer, bool $isCorrect, ?float $score = null, ?string $comment = null): QuizAttemptAnswer
    {
        $score = $score ?? ($isCorrect ? (float) $answer->max_score : 0);

        $answer->update([
            'is_correct' => $isCorrect,
            'score' => $score,
            'needs_manual_review' => false,
            'reviewer_comment' => $comment,
        ]);

        return $answer;
    }

    /**
     * Grade multiple answers of one attempt in a single transaction.
     *
     * @param  array<int, array{answer_id:int, is_correct:bool, score?:float|null, comment?:string|null}>  $grades
     */
    public function manualGradeAnswers(QuizAttempt $attempt, array $grades): Collection
    {
        return DB::transaction(function () use ($attempt, $grades) {
            $answerIds = collect($grades)->pluck('answer_id')->map(fn ($id) => (int) $id)->unique()->values();

            $answers = QuizAttemptAnswer::query()
                ->where('attempt_id', $attempt->id)
                ->whereIn('id', $answerIds)
                ->get()
                ->keyBy('id');

            if ($answers->count() !== $answerIds->count()) {
                throw new InvalidArgumentException('One or more answers do not belong to this attempt.');
            }

            $graded = collect();
            foreach ($grades as $item) {
                $answer = $answers->get((int) $item['answer_id']);
                $graded->push($this->manualGradeAnswer(
                    $answer,
                    (bool) $item['is_correct'],
                    array_key_exists('score', $item) && $item['score'] !== null ? (float) $item['score'] : null,
                    $item['comment'] ?? null
                )->fresh());
            }

            $attempt->load('answers');
            $score = max(0, (float) $attempt->answers->sum('score'));
            $maxScore = (float) $attempt->answers->sum('max_score');
            $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0;

            $attempt->update([
                'score' => $score,
                'max_score' => $maxScore,
                'percentage' => $percentage,
            ]);

            return $graded;
        });
    }

    public function completeManualReview(QuizAttempt $attempt, int $reviewerId): QuizAttempt
    {
        $attempt->load(['answers', 'quiz']);

        if ($attempt->answers->where('needs_manual_review', true)->isNotEmpty()) {
            throw new \RuntimeException('All answers must be reviewed before completing.');
        }

        $score = max(0, (float) $attempt->answers->sum('score'));
        $maxScore = (float) $attempt->answers->sum('max_score');
        $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0;

        $attempt->update([
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'passed' => $this->determinePass($attempt->quiz, $score, $percentage),
            'requires_manual_review' => false,
            'status' => 'completed',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'completed_at' => now(),
        ]);

        return $attempt->fresh(['answers', 'quiz']);
    }

    protected function determinePass(Quiz $quiz, float $score, float $percentage): bool
    {
        if ($quiz->passing_percentage !== null) {
            return $percentage >= (float) $quiz->passing_percentage;
        }
        if ($quiz->passing_score !== null) {
            return $score >= (float) $quiz->passing_score;
        }

        return $percentage >= 50;
    }

    protected function gradeSingleChoice(QuizQuestion $question, array $payload): bool
    {
        $selected = collect($payload['option_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        if ($selected->count() !== 1) {
            return false;
        }

        $correct = $question->options->where('is_correct', true)->pluck('id')->map(fn ($id) => (int) $id);

        return $correct->contains($selected->first());
    }

    protected function gradeMultipleChoicePartial(
        QuizQuestion $question,
        array $payload,
        float $maxScore,
        bool $negativeScoring,
        float $negativeFactor
    ): array {
        $selected = collect($payload['option_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $correct = $question->options->where('is_correct', true)->pluck('id')->map(fn ($id) => (int) $id);
        $incorrect = $question->options->where('is_correct', false)->pluck('id')->map(fn ($id) => (int) $id);

        if ($selected->isEmpty() || $correct->isEmpty()) {
            return $this->result(false, 0, $maxScore, $negativeScoring, $negativeFactor);
        }

        $correctSelected = $selected->intersect($correct)->count();
        $incorrectSelected = $selected->intersect($incorrect)->count();
        $totalCorrect = $correct->count();

        if ($correctSelected === $totalCorrect && $incorrectSelected === 0) {
            return $this->result(true, $maxScore, $maxScore, $negativeScoring, $negativeFactor);
        }

        $ratio = max(0, ($correctSelected - $incorrectSelected) / $totalCorrect);
        $score = round($maxScore * min(1, $ratio), 2);

        if ($incorrectSelected > 0 && $negativeScoring && $negativeFactor > 0) {
            $score = max(0, $score - round($maxScore * $negativeFactor, 2));
        }

        return [
            'is_correct' => $ratio >= 1 && $incorrectSelected === 0,
            'score' => $score,
            'max_score' => $maxScore,
            'needs_manual_review' => false,
        ];
    }

    protected function gradeMultipleChoice(QuizQuestion $question, array $payload): bool
    {
        $selected = collect($payload['option_ids'] ?? [])->map(fn ($id) => (int) $id)->sort()->values();
        $correct = $question->options->where('is_correct', true)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();

        return $selected->count() > 0 && $selected->toArray() === $correct->toArray();
    }

    protected function gradeShortAnswer(QuizQuestion $question, array $payload): bool
    {
        $text = $this->normalizeText((string) ($payload['text'] ?? ''), $question);
        $accepted = $question->options->where('is_correct', true)->pluck('text')->map(
            fn ($t) => $this->normalizeText((string) $t, $question)
        );

        return $accepted->contains($text);
    }

    protected function gradeFillBlank(QuizQuestion $question, array $payload): bool
    {
        $blanks = $payload['blanks'] ?? [];
        $blankCount = $question->options->pluck('blank_index')->filter(fn ($i) => $i !== null)->max();

        if ($blankCount === null) {
            return false;
        }

        for ($i = 0; $i <= $blankCount; $i++) {
            $student = $this->normalizeText((string) ($blanks[$i] ?? ''), $question);
            $accepted = $question->options
                ->where('blank_index', $i)
                ->where('is_correct', true)
                ->pluck('text')
                ->map(fn ($t) => $this->normalizeText((string) $t, $question));

            if (! $accepted->contains($student)) {
                return false;
            }
        }

        return true;
    }

    protected function gradeMatching(QuizQuestion $question, array $payload): bool
    {
        $matches = $payload['matches'] ?? [];
        $pairs = $question->options->filter(function ($o) {
            $key = $o->match_key ?? $o->text;

            return $key !== null && $key !== '' && $o->match_value !== null && $o->match_value !== '';
        });

        foreach ($pairs as $option) {
            $optionId = (string) $option->id;
            $matchKey = (string) ($option->match_key ?? $option->text);
            $expected = $this->normalizeText((string) $option->match_value, $question);
            $given = $this->normalizeText(
                (string) ($matches[$optionId] ?? $matches[$matchKey] ?? ''),
                $question
            );

            if ($given !== $expected) {
                return false;
            }
        }

        return $pairs->isNotEmpty();
    }

    protected function gradeOrdering(QuizQuestion $question, array $payload): bool
    {
        $order = collect($payload['order'] ?? [])->map(fn ($id) => (int) $id)->values();
        $correct = $question->options
            ->sortBy(fn ($o) => $o->correct_position ?? $o->position ?? 0)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return $order->isNotEmpty() && $order->toArray() === $correct->toArray();
    }

    protected function normalizeText(string $text, QuizQuestion $question): string
    {
        $text = trim($text);
        if (! ($question->settings['case_sensitive'] ?? false)) {
            $text = mb_strtolower($text);
        }

        return $text;
    }

    protected function result(bool $isCorrect, float $score, float $maxScore, bool $negative, float $factor): array
    {
        if (! $isCorrect && $negative && $factor > 0) {
            $score = -1 * round($maxScore * $factor, 2);
        }

        return [
            'is_correct' => $isCorrect,
            'score' => $isCorrect ? $maxScore : ($negative && $factor > 0 ? $score : max($score, 0)),
            'max_score' => $maxScore,
            'needs_manual_review' => false,
        ];
    }
}
