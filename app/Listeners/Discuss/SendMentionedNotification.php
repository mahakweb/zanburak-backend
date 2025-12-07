<?php

namespace App\Listeners\Discuss;

use App\Events\Discuss\Mentioned;

class SendMentionedNotification
{
    public function handle(Mentioned $event)
    {
        $event->user->notify(
            new \App\Notifications\Discuss\MentionNotification(
                $event->question,
                $event->mentioner
            )
        );
    }
}
