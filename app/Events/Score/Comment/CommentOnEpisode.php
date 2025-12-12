<?php

namespace App\Events\Score\Comment;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentOnEpisode
{
    use Dispatchable, SerializesModels;

    public $user;
    public $comment;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Comment $comment)
    {
        $this->user = $user;
        $this->comment = $comment;
    }
}

