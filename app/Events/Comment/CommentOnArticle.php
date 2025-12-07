<?php

namespace App\Events\Comment;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentOnArticle
{
    use Dispatchable, SerializesModels;

    public $comment;
    public $commenter;
    public $commentableTitle;
    public $commentableUrl;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Comment $comment, User $commenter, string $commentableTitle, string $commentableUrl)
    {
        $this->comment = $comment;
        $this->commenter = $commenter;
        $this->commentableTitle = $commentableTitle;
        $this->commentableUrl = $commentableUrl;
    }
}
