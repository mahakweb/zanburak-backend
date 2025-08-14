<?php

namespace App\Models;

use App\Contracts\Likeable;
use App\Models\Concerns\Likes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model implements Likeable
{
    use HasFactory, Likes;

    protected $fillable = [
        'user_id',
        'question_id',
        'answer',
        'parent_id',
        'pinned_at',
        'publish'
    ];

    public function isEditableBy($user)
    {
        if ($user && $this->user_id === $user->id) {
            if ($this->question->best_answer === $this->id) { // if this answer is best answer of the question then it can't be edited
                return false;
            }
            $oneMonthAgo = now()->subMonth();
            return $this->created_at >= $oneMonthAgo;
        }
        return false;
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Answer::class);
    }


    public function isBest()
    {
        return $this->id == $this->question->best_answer ? true : false;
    }

    public function likesCount(){
        return $this->likes()->where('type', 'like')->count() - $this->likes()->where('type', 'dislike')->count();
    }

    public function togglePin()
    {
        if ($this->pinned_at) {
            $this->pinned_at = null;
        } else {
            $this->pinned_at = now();
        }
        $this->save();
    }
}
