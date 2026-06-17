<?php

namespace App\Models\Quiz;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestionOption extends Model
{
    protected $table = 'quiz_question_options';

    protected $fillable = [
        'question_id', 'text', 'is_correct', 'blank_index',
        'match_key', 'match_value', 'correct_position', 'feedback', 'position',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'question_id');
    }
}
