<?php

namespace App\Models\Quiz;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttemptAnswer extends Model
{
    protected $table = 'quiz_attempt_answers';

    protected $fillable = [
        'attempt_id', 'question_id', 'answer',
        'is_correct', 'score', 'max_score',
        'needs_manual_review', 'reviewer_comment', 'answered_at',
    ];

    protected $casts = [
        'answer' => 'array',
        'is_correct' => 'boolean',
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'needs_manual_review' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'question_id');
    }
}
