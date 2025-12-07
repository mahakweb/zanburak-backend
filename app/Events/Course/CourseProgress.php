<?php

namespace App\Events\Course;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourseProgress
{
    use Dispatchable, SerializesModels;

    public $user;
    public $course;
    public $progress;

    public function __construct(User $user, Course $course, int $progress)
    {
        $this->user = $user;
        $this->course = $course;
        $this->progress = $progress;
    }
}
