<?php

namespace App\Events\Discount;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiscountApplied
{
    use Dispatchable, SerializesModels;

    public $user;
    public $message;
    public $actionUrl;

    public function __construct(User $user, string $message, string $actionUrl)
    {
        $this->user = $user;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
    }
}
