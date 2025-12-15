<?php

namespace App\Events\Score\User;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered
{
    use Dispatchable, SerializesModels;

    public $user;
    public $inviter;

    /**
     * Create a new event instance.
     *
     * @param User $user کاربر جدید که ثبت‌نام کرده
     * @param User|null $inviter کاربر دعوت‌کننده (اگر با کد دعوت ثبت‌نام کرده باشد)
     * @return void
     */
    public function __construct(User $user, ?User $inviter = null)
    {
        $this->user = $user;
        $this->inviter = $inviter;
    }
}

