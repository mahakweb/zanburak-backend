<?php

namespace App\Models\Quiz;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class QuizAttempt extends Model
{
    protected $table = 'quiz_attempts';

    protected $fillable = [
        'uuid', 'quiz_id', 'user_id', 'attempt_number', 'status',
        'started_at', 'submitted_at', 'completed_at', 'expires_at',
        'score', 'max_score', 'percentage', 'passed', 'time_spent',
        'requires_manual_review', 'reviewed_by', 'reviewed_at',
        'question_order', 'meta',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'passed' => 'boolean',
        'requires_manual_review' => 'boolean',
        'question_order' => 'array',
        'meta' => 'array',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $attempt) {
            if (empty($attempt->uuid)) {
                $attempt->uuid = (string) Str::uuid();
            }
        });
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class, 'attempt_id');
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast() && $this->isInProgress();
    }
}
