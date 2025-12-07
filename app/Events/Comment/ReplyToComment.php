<?php

namespace App\Events\Comment;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReplyToComment
{
    use Dispatchable, SerializesModels;

    public $comment;
    public $replier;
    public $commentableTitle;
    public $commentableUrl;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Comment $comment, User $replier, string $commentableTitle, string $commentableUrl)
    {
        $this->comment = $comment;
        $this->replier = $replier;
        $this->commentableTitle = $commentableTitle;
        $this->commentableUrl = $commentableUrl;
    }
}
