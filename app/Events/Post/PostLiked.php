<?php

namespace App\Events\Post;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostLiked
{
    use Dispatchable, SerializesModels;

    public $user;
    public $liker;
    public $postTitle;
    public $actionType;
    public $actionUrl;
    public ?string $subjectType;
    public $subjectId;

    public function __construct(User $user, User $liker, string $postTitle, string $actionType, string $actionUrl, ?string $subjectType = null, $subjectId = null)
    {
        $this->user = $user;
        $this->liker = $liker;
        $this->postTitle = $postTitle;
        $this->actionType = $actionType;
        $this->actionUrl = $actionUrl;
        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId;
    }
}
