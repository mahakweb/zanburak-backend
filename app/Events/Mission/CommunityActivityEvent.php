<?php

namespace App\Events\Mission;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Comment;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Report;

class CommunityActivityEvent
{
    use Dispatchable, SerializesModels;

    public $user;
    public $comment;
    public $question;
    public $answer;
    public $report;
    public $type; // 'comment', 'question', 'answer', 'report'

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, $type, $data = null)
    {
        $this->user = $user;
        $this->type = $type;
        
        switch ($type) {
            case 'comment':
                $this->comment = $data;
                break;
            case 'question':
                $this->question = $data;
                break;
            case 'answer':
                $this->answer = $data;
                break;
            case 'report':
                $this->report = $data;
                break;
        }
    }
}
