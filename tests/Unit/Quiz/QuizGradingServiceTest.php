<?php

namespace Tests\Unit\Quiz;

use App\Models\Quiz\Quiz;
use App\Models\Quiz\QuizAttempt;
use App\Models\Quiz\QuizQuestion;
use App\Models\Quiz\QuizQuestionOption;
use App\Services\Quiz\QuizGradingService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class QuizGradingServiceTest extends TestCase
{
    protected QuizGradingService $grading;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grading = new QuizGradingService;
    }

    public function test_multiple_choice_awards_partial_credit(): void
    {
        $question = $this->makeMultipleChoiceQuestion(
            correctIds: [1, 2],
            incorrectIds: [3, 4]
        );

        $result = $this->grading->gradeAnswer(
            $question,
            ['option_ids' => [1]],
            4.0
        );

        $this->assertFalse($result['is_correct']);
        $this->assertSame(2.0, (float) $result['score']);
        $this->assertSame(4.0, (float) $result['max_score']);
    }

    public function test_negative_scoring_on_wrong_single_choice_is_non_positive(): void
    {
        $question = $this->makeSingleChoiceQuestion(correctId: 1, incorrectId: 2);

        $result = $this->grading->gradeAnswer(
            $question,
            ['option_ids' => [2]],
            2.0,
            true,
            0.5
        );

        $this->assertFalse($result['is_correct']);
        $this->assertLessThanOrEqual(0.0, (float) $result['score']);
    }

    public function test_after_end_blocks_results_until_deadline(): void
    {
        $quiz = new Quiz([
            'result_display' => 'after_end',
            'show_correct_answers' => true,
            'end_at' => Carbon::now()->addDay(),
        ]);

        $attempt = new QuizAttempt(['status' => 'completed']);

        $this->assertFalse($quiz->canShowResultsForAttempt($attempt));
        $this->assertSame(
            'نتیجه پس از پایان مهلت آزمون نمایش داده می‌شود.',
            $quiz->resultUnavailableMessage()
        );
    }

    public function test_after_review_requires_completed_status(): void
    {
        $quiz = new Quiz([
            'result_display' => 'after_review',
            'show_correct_answers' => true,
        ]);

        $gradingAttempt = new QuizAttempt(['status' => 'grading']);
        $completedAttempt = new QuizAttempt(['status' => 'completed']);

        $this->assertFalse($quiz->canShowResultsForAttempt($gradingAttempt));
        $this->assertTrue($quiz->canShowResultsForAttempt($completedAttempt));
        $this->assertTrue($quiz->canShowCorrectAnswersForAttempt($completedAttempt));
    }

    protected function makeMultipleChoiceQuestion(array $correctIds, array $incorrectIds): QuizQuestion
    {
        $options = new Collection;

        foreach ($correctIds as $id) {
            $option = new QuizQuestionOption(['is_correct' => true]);
            $option->id = $id;
            $options->push($option);
        }

        foreach ($incorrectIds as $id) {
            $option = new QuizQuestionOption(['is_correct' => false]);
            $option->id = $id;
            $options->push($option);
        }

        $question = new QuizQuestion(['type' => 'multiple_choice', 'id' => 1]);
        $question->setRelation('options', $options);

        return $question;
    }

    protected function makeSingleChoiceQuestion(int $correctId, int $incorrectId): QuizQuestion
    {
        $question = new QuizQuestion(['type' => 'single_choice', 'id' => 1]);
        $correct = new QuizQuestionOption(['is_correct' => true]);
        $correct->id = $correctId;
        $incorrect = new QuizQuestionOption(['is_correct' => false]);
        $incorrect->id = $incorrectId;
        $question->setRelation('options', collect([$correct, $incorrect]));

        return $question;
    }
}
