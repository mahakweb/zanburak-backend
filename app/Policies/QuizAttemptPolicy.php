<?php

namespace App\Policies;

use App\Models\Quiz\QuizAttempt;
use App\Models\User;

class QuizAttemptPolicy
{
    public function view(User $user, QuizAttempt $attempt): bool
    {
        return $attempt->user_id === $user->id
            || $user->isSuperUser()
            || $user->hasPermissionName('quizzes.view');
    }

    public function review(User $user, QuizAttempt $attempt): bool
    {
        return $user->isSuperUser() || $user->hasPermissionName('quizzes.review');
    }
}
