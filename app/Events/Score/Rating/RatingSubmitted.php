<?php

namespace App\Events\Score\Rating;

use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RatingSubmitted
{
    use Dispatchable, SerializesModels;

    public $user;
    public $rating;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Rating $rating)
    {
        $this->user = $user;
        $this->rating = $rating;
    }
}

