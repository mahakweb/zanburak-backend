<?php

namespace App\Events\Course;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewCourseInCategory
{
    use Dispatchable, SerializesModels;

    public $user;
    public $course;
    public $category;

    public function __construct(User $user, Course $course, Category $category)
    {
        $this->user = $user;
        $this->course = $course;
        $this->category = $category;
    }
}
