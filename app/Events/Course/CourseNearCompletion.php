<?php

namespace App\Events\Course;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourseNearCompletion
{
    use Dispatchable, SerializesModels;

    public $user;
    public $course;
    public $remainingPercent;

    public function __construct(User $user, Course $course, int $remainingPercent)
    {
        $this->user = $user;
        $this->course = $course;
        $this->remainingPercent = $remainingPercent;
    }
}
