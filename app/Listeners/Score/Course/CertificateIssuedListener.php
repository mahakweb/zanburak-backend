<?php

namespace App\Listeners\Score\Course;

use App\Events\Course\CertificateIssued;

class CertificateIssuedListener
{
    public function handle(CertificateIssued $event)
    {
        upgrade_mission_for_user($event->user->id, 'certificate-issued');
    }
}
