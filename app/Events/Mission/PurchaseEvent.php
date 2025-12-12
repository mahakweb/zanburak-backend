<?php

namespace App\Events\Mission;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Course;
use App\Models\Payment;

class PurchaseEvent
{
    use Dispatchable, SerializesModels;

    public $user;
    public $course;
    public $payment;
    public $courses;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, $course = null, Payment $payment = null, $courses = [])
    {
        $this->user = $user;
        $this->course = $course;
        $this->payment = $payment;
        $this->courses = $courses;
    }
}
