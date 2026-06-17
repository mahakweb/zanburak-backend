<?php

namespace App\Models\Quiz;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuizTag extends Model
{
    protected $table = 'quiz_tags';

    protected $fillable = ['name', 'slug'];

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(QuizQuestion::class, 'quiz_question_tag', 'tag_id', 'question_id');
    }
}
