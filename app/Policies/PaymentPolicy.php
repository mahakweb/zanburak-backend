<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Services\Security\ContentScope;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return ContentScope::for($user)->viewScope('payments') !== ContentScope::NONE;
    }

    public function view(User $user, Payment $payment): bool
    {
        return ContentScope::for($user)->canPayment($payment, 'view');
    }

    public function create(User $user): bool
    {
        return ContentScope::for($user)->canAction('payments', 'create');
    }

    public function update(User $user, Payment $payment): bool
    {
        return ContentScope::for($user)->canAction('payments', 'update', $payment);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return ContentScope::for($user)->canAction('payments', 'delete', $payment);
    }
}
