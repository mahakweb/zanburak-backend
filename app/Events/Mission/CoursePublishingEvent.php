<?php

namespace App\Events\Mission;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Course;

class CoursePublishingEvent
{
    use Dispatchable, SerializesModels;

    public $user;
    public $course;
    public $publishedAt;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Course $course, $publishedAt = null)
    {
        $this->user = $user;
        $this->course = $course;
        $this->publishedAt = $publishedAt ?? now();
    }
}
