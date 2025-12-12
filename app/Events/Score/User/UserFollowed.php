<?php

namespace App\Events\Score\User;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserFollowed
{
    use Dispatchable, SerializesModels;

    public $user;
    public $followedUser;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, User $followedUser)
    {
        $this->user = $user;
        $this->followedUser = $followedUser;
    }
}

