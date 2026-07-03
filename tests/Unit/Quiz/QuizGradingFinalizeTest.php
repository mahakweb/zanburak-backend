<?php

namespace Tests\Unit\Quiz;

use App\Models\Quiz\Quiz;
use App\Models\Quiz\QuizAttempt;
use App\Models\Quiz\QuizAttemptAnswer;
use App\Models\Quiz\QuizQuestion;
use App\Models\User;
use App\Services\Quiz\QuizGradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizGradingFinalizeTest extends TestCase
{
    use RefreshDatabase;

    protected QuizGradingService $grading;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grading = app(QuizGradingService::class);
    }

    public function test_manual_review_required_puts_attempt_in_grading_queue(): void
    {
        [$attempt] = $this->makeSubmittedAttempt(manualReviewRequired: true, resultDisplay: 'immediately');

        $final = $this->grading->finalizeAttempt($attempt);

        $this->assertSame('grading', $final->status);
        $this->assertTrue($final->requires_manual_review);
        $this->assertNull($final->completed_at);
    }

    public function test_after_review_display_puts_attempt_in_grading_queue(): void
    {
        [$attempt] = $this->makeSubmittedAttempt(manualReviewRequired: false, resultDisplay: 'after_review');

        $final = $this->grading->finalizeAttempt($attempt);

        $this->assertSame('grading', $final->status);
        $this->assertTrue($final->requires_manual_review);
    }

    public function test_long_answer_puts_attempt_in_grading_queue(): void
    {
        [$attempt, $answer] = $this->makeSubmittedAttempt(manualReviewRequired: false, resultDisplay: 'immediately');
        $answer->update([
            'needs_manual_review' => true,
            'is_correct' => null,
            'score' => 0,
        ]);

        $final = $this->grading->finalizeAttempt($attempt->fresh(['answers', 'quiz']));

        $this->assertSame('grading', $final->status);
        $this->assertTrue($final->requires_manual_review);
    }

    /**
     * @return array{0: QuizAttempt, 1: QuizAttemptAnswer}
     */
    protected function makeSubmittedAttempt(bool $manualReviewRequired, string $resultDisplay): array
    {
        $quiz = Quiz::create([
            'title' => 'Finalize test',
            'slug' => 'finalize-test-'.uniqid(),
            'is_published' => true,
            'manual_review_required' => $manualReviewRequired,
            'result_display' => $resultDisplay,
            'total_score' => 2,
            'questions_count' => 1,
        ]);

        $question = QuizQuestion::create([
            'type' => 'single_choice',
            'text' => 'Q1',
            'default_score' => 2,
            'is_active' => true,
        ]);

        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test-'.uniqid().'@example.com',
            'username' => 'testuser'.uniqid(),
            'password' => bcrypt('password'),
            'mobile' => '+989123456789',
        ]);

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'attempt_number' => 1,
            'status' => 'submitted',
            'submitted_at' => now(),
            'max_score' => 2,
        ]);

        $answer = QuizAttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'max_score' => 2,
            'score' => 2,
            'is_correct' => true,
            'needs_manual_review' => false,
        ]);

        return [$attempt->fresh(['answers', 'quiz']), $answer];
    }
}
