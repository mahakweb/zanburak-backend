<?php

namespace App\Services\Quiz;

use App\Models\Quiz\Quiz;
use App\Models\Quiz\QuizAttempt;
use Illuminate\Support\Facades\DB;

class QuizReportService
{
    public function summary(Quiz $quiz): array
    {
        $base = QuizAttempt::where('quiz_id', $quiz->id)
            ->whereIn('status', ['completed', 'grading', 'submitted']);

        $completed = (clone $base)->where('status', 'completed');

        $totalAttempts = (clone $base)->count();
        $completedCount = (clone $completed)->count();
        $passedCount = (clone $completed)->where('passed', true)->count();
        $failedCount = (clone $completed)->where('passed', false)->count();

        $avgScore = (clone $completed)->avg('score');
        $avgPercentage = (clone $completed)->avg('percentage');
        $avgTimeSpent = (clone $completed)->avg('time_spent');

        $successRate = $completedCount > 0
            ? round(($passedCount / $completedCount) * 100, 2)
            : 0;

        $pendingReviewCount = (clone $base)
            ->where('requires_manual_review', true)
            ->whereIn('status', ['grading', 'submitted'])
            ->count();

        return [
            'quiz_id' => $quiz->id,
            'total_attempts' => $totalAttempts,
            'completed_attempts' => $completedCount,
            'passed_count' => $passedCount,
            'failed_count' => $failedCount,
            'pending_review_count' => $pendingReviewCount,
            'success_rate' => $successRate,
            'average_score' => round((float) $avgScore, 2),
            'average_percentage' => round((float) $avgPercentage, 2),
            'average_time_spent' => (int) round((float) $avgTimeSpent),
        ];
    }

    public function passedStudents(Quiz $quiz, int $perPage = 20)
    {
        return $this->studentList($quiz, passed: true, perPage: $perPage);
    }

    public function failedStudents(Quiz $quiz, int $perPage = 20)
    {
        return $this->studentList($quiz, passed: false, perPage: $perPage);
    }

    protected function studentList(Quiz $quiz, bool $passed, int $perPage)
    {
        return QuizAttempt::with(['user:id,first_name,last_name,username,email,profile_pic'])
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->where('passed', $passed)
            ->orderByDesc('completed_at')
            ->paginate($perPage);
    }

    public function scoreDistribution(Quiz $quiz): array
    {
        $buckets = ['0-20' => 0, '21-40' => 0, '41-60' => 0, '61-80' => 0, '81-100' => 0];

        QuizAttempt::where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->select('percentage')
            ->orderBy('id')
            ->chunk(1000, function ($rows) use (&$buckets) {
                foreach ($rows as $row) {
                    $p = (float) $row->percentage;
                    $key = match (true) {
                        $p <= 20 => '0-20',
                        $p <= 40 => '21-40',
                        $p <= 60 => '41-60',
                        $p <= 80 => '61-80',
                        default => '81-100',
                    };
                    $buckets[$key]++;
                }
            });

        return collect($buckets)->map(fn ($count, $label) => [
            'label' => $label,
            'count' => $count,
        ])->values()->all();
    }
}
