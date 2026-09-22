<?php

namespace App\Listeners\Mission\CommunityActivities;

use App\Events\Mission\CommunityActivityEvent;
use App\Models\Comment;

class ValuableCommentListener
{
    protected $missionId = 'valuable-comment';

    /**
     * Handle the event.
     * ماموریت کامنت با ارزش: کاربری که اولین کامنت مفید در یک دوره جدید را ارسال کرده است.
     * کامنتی که پس از تایید، اولین امتیاز نظر مثبت گرفته باشه (خود کاربر نمیتونه به پاسخ خودش نظر بده)
     *
     * @param  CommunityActivityEvent  $event
     * @return void
     */
    public function handle(CommunityActivityEvent $event)
    {
        if ($event->type !== 'comment' || !$event->comment) {
            return;
        }

        $comment = $event->comment;

        if (!$comment->approved || !$comment->likes()->where('type', 'like')->exists()) {
            return;
        }

        $earlierLikedComment = Comment::query()
            ->where('commentable_type', $comment->commentable_type)
            ->where('commentable_id', $comment->commentable_id)
            ->where('approved', true)
            ->where('id', '!=', $comment->id)
            ->where('created_at', '<', $comment->created_at)
            ->whereHas('likes', function ($q) {
                $q->where('type', 'like');
            })
            ->exists();

        if (!$earlierLikedComment) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
