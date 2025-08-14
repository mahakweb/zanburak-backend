<?php

namespace App\Events\Course;

use App\Models\Course;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GetCourse
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $courses;
    public $user;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($courses, User $user)
    {
        $this->courses = $courses;
        $this->user = $user;
    }

}
