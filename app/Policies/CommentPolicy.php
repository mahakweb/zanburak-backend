<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use App\Services\Security\ContentScope;

class CommentPolicy
{
    public function viewAny(User $user): bool
    {
        return ContentScope::for($user)->viewScope('comments') !== ContentScope::NONE;
    }

    public function view(User $user, Comment $comment): bool
    {
        return ContentScope::for($user)->canComment($comment, 'view');
    }

    public function moderate(User $user, Comment $comment): bool
    {
        return ContentScope::for($user)->canComment($comment, 'moderate');
    }

    public function reply(User $user, Comment $comment): bool
    {
        return ContentScope::for($user)->canComment($comment, 'reply');
    }

    public function delete(User $user, Comment $comment): bool
    {
        return ContentScope::for($user)->canComment($comment, 'delete');
    }
}
