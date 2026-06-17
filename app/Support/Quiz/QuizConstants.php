<?php

namespace App\Support\Quiz;

final class QuizConstants
{
    public const QUESTION_TYPES = [
        'single_choice',
        'multiple_choice',
        'true_false',
        'short_answer',
        'long_answer',
        'fill_blank',
        'matching',
        'ordering',
    ];

    public const AUTO_GRADED_TYPES = [
        'single_choice',
        'multiple_choice',
        'true_false',
        'short_answer',
        'fill_blank',
        'matching',
        'ordering',
    ];

    public const MANUAL_TYPES = ['long_answer'];

    public const DIFFICULTIES = ['easy', 'medium', 'hard'];

    public const ATTEMPT_STATUSES = [
        'in_progress',
        'submitted',
        'grading',
        'completed',
        'abandoned',
        'expired',
    ];

    public const RESULT_DISPLAY = [
        'immediately',
        'after_review',
        'after_end',
    ];

    public const QUIZZABLE_TYPES = [
        'course' => \App\Models\Course::class,
        'section' => \App\Models\Section::class,
        'episode' => \App\Models\Episode::class,
    ];
}
