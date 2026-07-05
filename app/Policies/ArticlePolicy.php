<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;
use App\Services\Security\ContentScope;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return ContentScope::for($user)->viewScope('articles') !== ContentScope::NONE;
    }

    public function view(User $user, Article $article): bool
    {
        return ContentScope::for($user)->canArticle($article, 'view');
    }

    public function create(User $user): bool
    {
        return ContentScope::for($user)->canAction('articles', 'create');
    }

    public function update(User $user, Article $article): bool
    {
        return ContentScope::for($user)->canArticle($article, 'update');
    }

    public function delete(User $user, Article $article): bool
    {
        return ContentScope::for($user)->canArticle($article, 'delete');
    }

    public function publish(User $user, Article $article): bool
    {
        return ContentScope::for($user)->canArticle($article, 'publish');
    }
}
