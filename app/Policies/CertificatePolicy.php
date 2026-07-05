<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;
use App\Services\Security\ContentScope;

class CertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return ContentScope::for($user)->viewScope('certificates') !== ContentScope::NONE
            || ContentScope::for($user)->viewScope('courses') === ContentScope::OWN;
    }

    public function view(User $user, Certificate $certificate): bool
    {
        return ContentScope::for($user)->canCertificate($certificate, 'view');
    }

    public function create(User $user): bool
    {
        return ContentScope::for($user)->canAction('certificates', 'create');
    }

    public function update(User $user, Certificate $certificate): bool
    {
        return ContentScope::for($user)->canAction('certificates', 'update', $certificate);
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return ContentScope::for($user)->canAction('certificates', 'delete', $certificate);
    }
}
