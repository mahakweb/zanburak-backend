<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use App\Services\Security\ContentScope;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return ContentScope::for($user)->viewScope('courses') !== ContentScope::NONE;
    }

    public function view(User $user, Course $course): bool
    {
        return ContentScope::for($user)->canCourse($course, 'view');
    }

    public function create(User $user): bool
    {
        return ContentScope::for($user)->canAction('courses', 'create');
    }

    public function update(User $user, Course $course): bool
    {
        return ContentScope::for($user)->canCourse($course, 'update');
    }

    public function delete(User $user, Course $course): bool
    {
        return ContentScope::for($user)->canCourse($course, 'delete');
    }

    public function publish(User $user, Course $course): bool
    {
        return ContentScope::for($user)->canCourse($course, 'publish');
    }

    public function assignUser(User $user, Course $course): bool
    {
        return ContentScope::for($user)->canCourse($course, 'assign_user');
    }

    public function reorderEpisodes(User $user, Course $course): bool
    {
        return ContentScope::for($user)->canCourse($course, 'reorder_episodes');
    }
}
