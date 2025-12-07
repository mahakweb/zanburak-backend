<?php

namespace App\Events\Comment;

use App\Models\Comment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentApproved
{
    use Dispatchable, SerializesModels;

    public $comment;
    public $commentableTitle;
    public $commentableUrl;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Comment $comment, string $commentableTitle, string $commentableUrl)
    {
        $this->comment = $comment;
        $this->commentableTitle = $commentableTitle;
        $this->commentableUrl = $commentableUrl;
    }
}
