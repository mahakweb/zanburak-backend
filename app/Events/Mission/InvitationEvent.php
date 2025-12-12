<?php

namespace App\Events\Mission;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class InvitationEvent
{
    use Dispatchable, SerializesModels;

    public $inviter;
    public $invitee;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $inviter, User $invitee = null)
    {
        $this->inviter = $inviter;
        $this->invitee = $invitee;
    }
}
