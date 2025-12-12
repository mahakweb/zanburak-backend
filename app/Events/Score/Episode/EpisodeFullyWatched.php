<?php

namespace App\Events\Score\Episode;

use App\Models\Episode;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EpisodeFullyWatched
{
    use Dispatchable, SerializesModels;

    public $user;
    public $episode;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Episode $episode)
    {
        $this->user = $user;
        $this->episode = $episode;
    }
}

