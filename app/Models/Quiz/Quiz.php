<?php

namespace App\Models\Quiz;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Quiz extends Model
{
    use SoftDeletes;

    protected $table = 'quizzes';

    protected $fillable = [
        'uuid', 'quizzable_type', 'quizzable_id', 'title', 'slug', 'description',
        'passing_score', 'passing_percentage', 'time_limit', 'max_attempts',
        'randomize_questions', 'randomize_answers', 'result_display',
        'negative_scoring', 'negative_scoring_factor',
        'start_at', 'end_at', 'manual_review_required', 'show_correct_answers',
        'is_published', 'questions_count', 'total_score', 'settings', 'created_by',
    ];

    protected $casts = [
        'passing_score' => 'decimal:2',
        'passing_percentage' => 'decimal:2',
        'negative_scoring_factor' => 'decimal:2',
        'randomize_questions' => 'boolean',
        'randomize_answers' => 'boolean',
        'negative_scoring' => 'boolean',
        'manual_review_required' => 'boolean',
        'show_correct_answers' => 'boolean',
        'is_published' => 'boolean',
        'total_score' => 'decimal:2',
        'settings' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $quiz) {
            if (empty($quiz->uuid)) {
                $quiz->uuid = (string) Str::uuid();
            }
        });
    }

    public function quizzable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(QuizQuestion::class, 'quiz_quiz_question', 'quiz_id', 'question_id')
            ->withPivot(['position', 'score'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'quiz_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeAvailableNow($query)
    {
        $now = now();

        return $query->published()
            ->where(fn ($q) => $q->whereNull('start_at')->orWhere('start_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('end_at')->orWhere('end_at', '>=', $now));
    }

    public function isAvailable(): bool
    {
        if (! $this->is_published) {
            return false;
        }
        if ($this->start_at && $this->start_at->isFuture()) {
            return false;
        }
        if ($this->end_at && $this->end_at->isPast()) {
            return false;
        }

        return true;
    }

    public function hasUnlimitedAttempts(): bool
    {
        return $this->max_attempts === null;
    }

    public function recalculateTotals(): void
    {
        $questions = $this->questions()->get();
        $this->questions_count = $questions->count();
        $this->total_score = $questions->sum(
            fn ($q) => (float) ($q->pivot->score ?? $q->default_score)
        );
        $this->saveQuietly();
    }

    /**
     * Minimal payload for course/episode pages and public preview.
     */
    public function toStudentSummary(): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'questions_count' => (int) $this->questions_count,
            'total_score' => (float) $this->total_score,
            'time_limit' => $this->time_limit,
            'max_attempts' => $this->max_attempts,
            'passing_percentage' => $this->passing_percentage,
            'passing_score' => $this->passing_score,
            'manual_review_required' => (bool) $this->manual_review_required,
            'result_display' => $this->result_display,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'is_available' => $this->isAvailable(),
        ];
    }
}
