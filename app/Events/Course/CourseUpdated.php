<?php

namespace App\Events\Course;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourseUpdated
{
    use Dispatchable, SerializesModels;

    public $user;
    public $course;

    public function __construct(User $user, Course $course)
    {
        $this->user = $user;
        $this->course = $course;
    }
}
