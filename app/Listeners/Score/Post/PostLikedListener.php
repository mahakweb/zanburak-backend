<?php

namespace App\Listeners\Score\Post;

use App\Events\Mission\CommunityActivityEvent;
use App\Events\Post\PostLiked;
use App\Models\Answer;
use App\Models\Comment;
use App\Models\Question;

class PostLikedListener
{
    public function handle(PostLiked $event)
    {
        if ($event->actionType !== 'لایک') {
            return;
        }

        [$missionId, $description] = $this->resolveMission($event);

        if ($missionId) {
            award_mission_exp($event->user, $missionId, $description);
            upgrade_mission_for_user($event->user->id, $missionId, 1, 1, false);
        }

        $this->replayCommunityActivity($event);
    }

    protected function resolveMission(PostLiked $event): array
    {
        $type = class_basename((string) $event->subjectType);

        if ($type === 'Answer') {
            return ['like-on-answer', "دریافت لایک روی پاسخ: {$event->postTitle}"];
        }

        if ($type === 'Question') {
            return ['like-on-question', "دریافت لایک روی پرسش: {$event->postTitle}"];
        }

        if ($type === 'Comment') {
            return ['like-on-comment', "دریافت لایک روی نظر: {$event->postTitle}"];
        }

        $url = $event->actionUrl ?? '';
        if (str_contains($url, '/episode/') || str_contains($url, '/comment')) {
            return ['like-on-comment', "دریافت لایک روی نظر: {$event->postTitle}"];
        }

        return [null, null];
    }

    protected function replayCommunityActivity(PostLiked $event): void
    {
        $type = class_basename((string) $event->subjectType);
        $map = [
            'Answer' => [Answer::class, 'answer'],
            'Question' => [Question::class, 'question'],
            'Comment' => [Comment::class, 'comment'],
        ];

        if (!isset($map[$type]) || !$event->subjectId) {
            return;
        }

        [$class, $activity] = $map[$type];
        $model = $class::find($event->subjectId);
        if (!$model) {
            return;
        }

        event(new CommunityActivityEvent($event->user, $activity, $model));
    }
}
