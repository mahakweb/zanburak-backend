<?php

namespace App\Policies;

use App\Models\Quiz\Quiz;
use App\Models\Quiz\QuizAttempt;
use App\Models\Quiz\QuizQuestion;
use App\Models\User;

class QuizPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user, 'quizzes.view');
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $this->canManage($user, 'quizzes.view');
    }

    public function create(User $user): bool
    {
        return $this->canManage($user, 'quizzes.create');
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $this->canManage($user, 'quizzes.update');
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->canManage($user, 'quizzes.delete');
    }

    public function viewReports(User $user, Quiz $quiz): bool
    {
        return $this->canManage($user, 'quizzes.reports');
    }

    public function take(User $user, Quiz $quiz): bool
    {
        return $quiz->userCanTake($user);
    }

    public function viewForStudent(User $user, Quiz $quiz): bool
    {
        return $quiz->userCanViewAsStudent($user);
    }

    public function viewAttempt(User $user, QuizAttempt $attempt): bool
    {
        return $attempt->user_id === $user->id || $this->canManage($user, 'quizzes.view');
    }

    public function reviewAttempt(User $user, QuizAttempt $attempt): bool
    {
        return $this->canManage($user, 'quizzes.review');
    }

    protected function canManage(User $user, string $permission): bool
    {
        return $user->isSuperUser() || $user->hasPermissionName($permission);
    }
}
