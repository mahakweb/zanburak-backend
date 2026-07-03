<?php

namespace Tests\Unit\Quiz;

use App\Models\Quiz\Quiz;
use App\Services\Quiz\QuizAdminService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAdminServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QuizAdminService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(QuizAdminService::class);
    }

    public function test_update_persists_start_and_end_dates(): void
    {
        $quiz = Quiz::create([
            'title' => 'Schedule test',
            'slug' => 'schedule-test',
            'is_published' => false,
        ]);

        $start = '2026-07-10 09:00:00';
        $end = '2026-07-10 18:00:00';

        $updated = $this->service->updateQuiz($quiz, [
            'title' => 'Schedule test',
            'start_at' => $start,
            'end_at' => $end,
        ]);

        $this->assertNotNull($updated->start_at);
        $this->assertNotNull($updated->end_at);
        $this->assertSame('2026-07-10 09:00:00', $updated->start_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-10 18:00:00', $updated->end_at->format('Y-m-d H:i:s'));
    }

    public function test_create_persists_start_and_end_dates(): void
    {
        $quiz = $this->service->createQuiz([
            'title' => 'New scheduled quiz',
            'slug' => 'new-scheduled-quiz',
            'is_published' => false,
            'start_at' => '2026-08-01 10:30:00',
            'end_at' => '2026-08-01 20:00:00',
        ]);

        $this->assertNotNull($quiz->start_at);
        $this->assertNotNull($quiz->end_at);
        $this->assertSame('2026-08-01 10:30:00', $quiz->start_at->format('Y-m-d H:i:s'));
    }

    public function test_update_can_clear_start_and_end_dates(): void
    {
        $quiz = Quiz::create([
            'title' => 'Clear dates',
            'slug' => 'clear-dates',
            'is_published' => false,
            'start_at' => Carbon::parse('2026-07-10 09:00:00'),
            'end_at' => Carbon::parse('2026-07-10 18:00:00'),
        ]);

        $updated = $this->service->updateQuiz($quiz, [
            'title' => 'Clear dates',
            'start_at' => null,
            'end_at' => null,
        ]);

        $this->assertNull($updated->start_at);
        $this->assertNull($updated->end_at);
    }
}
