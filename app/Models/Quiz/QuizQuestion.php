<?php

namespace App\Models\Quiz;

use App\Models\User;
use App\Support\Quiz\QuizConstants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class QuizQuestion extends Model
{
    use SoftDeletes;

    protected $table = 'quiz_questions';

    protected $fillable = [
        'uuid', 'category_id', 'type', 'text', 'explanation', 'difficulty',
        'default_score', 'settings', 'requires_manual_review', 'is_active', 'created_by',
    ];

    protected $casts = [
        'default_score' => 'decimal:2',
        'settings' => 'array',
        'requires_manual_review' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $question) {
            if (empty($question->uuid)) {
                $question->uuid = (string) Str::uuid();
            }
            if (in_array($question->type, QuizConstants::MANUAL_TYPES, true)) {
                $question->requires_manual_review = true;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(QuizQuestionCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuizQuestionOption::class, 'question_id')->orderBy('position');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(QuizTag::class, 'quiz_question_tag', 'question_id', 'tag_id');
    }

    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class, 'quiz_quiz_question', 'question_id', 'quiz_id')
            ->withPivot(['position', 'score'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOfDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }
}
