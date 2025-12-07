<?php

namespace App\Events\Discuss;

use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Mentioned
{
    use Dispatchable, SerializesModels;

    public $user;
    public $question;
    public $mentioner;

    public function __construct(User $user, Question $question, User $mentioner)
    {
        $this->user = $user;
        $this->question = $question;
        $this->mentioner = $mentioner;
    }
}
