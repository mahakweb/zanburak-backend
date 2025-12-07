<?php

namespace App\Listeners\Course;

use App\Events\Course\CertificateIssued;

class SendCertificateIssuedNotification
{
    public function handle(CertificateIssued $event)
    {
        $event->user->notify(
            new \App\Notifications\Course\CertificateIssuedNotification(
                $event->course,
                $event->certificate
            )
        );
    }
}
