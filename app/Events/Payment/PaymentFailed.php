<?php

namespace App\Events\Payment;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed
{
    use Dispatchable, SerializesModels;

    public $user;
    public $amount;
    public $reason;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, int $amount, string $reason = null)
    {
        $this->user = $user;
        $this->amount = $amount;
        $this->reason = $reason;
    }
}
