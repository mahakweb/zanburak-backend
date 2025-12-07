<?php

namespace App\Events\Course;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CertificateIssued
{
    use Dispatchable, SerializesModels;

    public $user;
    public $course;
    public $certificate;

    public function __construct(User $user, Course $course, Certificate $certificate)
    {
        $this->user = $user;
        $this->course = $course;
        $this->certificate = $certificate;
    }
}
