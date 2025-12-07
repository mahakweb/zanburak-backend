<?php

namespace App\Events\Payment;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VipUpgraded
{
    use Dispatchable, SerializesModels;

    public $user;
    public $message;
    public $actionUrl;
    public $actionText;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, string $message, string $actionUrl, string $actionText = 'مشاهده پلن')
    {
        $this->user = $user;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText;
    }
}
