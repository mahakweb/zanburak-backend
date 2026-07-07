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
        
        // بررسی اینکه آیا این اولین کامنت تایید شده در این دوره است که لایک گرفته
        $isFirstValuable = Comment::query()
            ->where('commentable_type', $comment->commentable_type)
            ->where('commentable_id', $comment->commentable_id)
            ->where('approved', true)
            ->where('id', '!=', $comment->id)
            ->whereHas('likes', function ($q) {
                $q->where('type', 'like');
            })
            ->where('created_at', '<', $comment->created_at)
            ->doesntExist();

        if ($comment->approved && $isFirstValuable) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
