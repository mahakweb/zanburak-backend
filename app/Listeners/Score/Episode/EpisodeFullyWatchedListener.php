<?php

namespace App\Listeners\Score\Episode;

use App\Events\Score\Episode\EpisodeFullyWatched;

class EpisodeFullyWatchedListener
{
    public function handle(EpisodeFullyWatched $event)
    {
        $episode = $event->episode;
        $course = $episode->section->course;

        award_mission_exp(
            $event->user,
            'episode-fully-watched',
            "مشاهده کامل اپیزود: {$episode->title} از دوره {$course->title}"
        );
        upgrade_mission_for_user($event->user->id, 'episode-fully-watched', 1, 1, false);
    }
}
