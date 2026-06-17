<?php

namespace App\Policies;

use App\Models\Quiz\QuizQuestion;
use App\Models\User;

class QuizQuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperUser() || $user->hasPermissionName('quiz_questions.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperUser() || $user->hasPermissionName('quiz_questions.create');
    }

    public function update(User $user, QuizQuestion $question): bool
    {
        return $user->isSuperUser() || $user->hasPermissionName('quiz_questions.update');
    }

    public function delete(User $user, QuizQuestion $question): bool
    {
        return $user->isSuperUser() || $user->hasPermissionName('quiz_questions.delete');
    }
}
