<?php

namespace App\Policies;

use App\Models\Quiz\Quiz;
use App\Models\Quiz\QuizAttempt;
use App\Models\User;
use App\Services\Security\ContentScope;

class QuizPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperUser()) {
            return true;
        }

        return $user->hasPermissionName('quizzes.view')
            || ContentScope::for($user)->viewScope('courses') !== ContentScope::NONE;
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return ContentScope::for($user)->canQuiz($quiz, 'view');
    }

    public function create(User $user): bool
    {
        return ContentScope::for($user)->canAction('quizzes', 'create');
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return ContentScope::for($user)->canQuiz($quiz, 'update');
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return ContentScope::for($user)->canQuiz($quiz, 'delete');
    }

    public function viewReports(User $user, Quiz $quiz): bool
    {
        return ContentScope::for($user)->canQuiz($quiz, 'reports');
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
        return $attempt->user_id === $user->id || ContentScope::for($user)->canQuiz($attempt->quiz, 'view');
    }

    public function reviewAttempt(User $user, QuizAttempt $attempt): bool
    {
        return ContentScope::for($user)->canQuiz($attempt->quiz, 'review');
    }

    public function review(User $user, QuizAttempt $attempt): bool
    {
        return $this->reviewAttempt($user, $attempt);
    }
}
