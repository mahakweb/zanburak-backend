<?php

namespace App\Listeners\Score\Rating;

use App\Events\Score\Rating\RatingSubmitted;
use App\Models\Comment;
use App\Models\Rating;

class RatingSubmittedListener
{
    public function handle(RatingSubmitted $event)
    {
        $rateable = $event->rating->rateable;
        $title = $rateable->title ?? $rateable->subject ?? 'محتوا';

        award_mission_exp(
            $event->user,
            'rating-submitted',
            "ثبت امتیاز برای: {$title}"
        );
        upgrade_mission_for_user($event->user->id, 'rating-submitted', 1, 1, false);
        sync_mission_progress_for_user($event->user->id, 'reviewer', (int) $event->user->ratings()->count());

        $this->syncWrittenFeedback($event);
    }

    protected function syncWrittenFeedback(RatingSubmitted $event): void
    {
        $userId = $event->user->id;
        $text = trim((string) ($event->rating->comment ?? ''));
        $length = mb_strlen($text);
        $stars = (int) $event->rating->rating;

        if ($length >= 40) {
            sync_mission_progress_for_user($userId, 'accurate-feedback', $this->countFeedback($userId, 40));
        }
        if ($length >= 120) {
            sync_mission_progress_for_user($userId, 'comprehensive-feedback', $this->countFeedback($userId, 120));
        }
        if ($length >= 200) {
            sync_mission_progress_for_user($userId, 'innovative-critic', $this->countFeedback($userId, 200));
        }
        if ($stars >= 2 && $stars <= 4 && $length >= 20) {
            sync_mission_progress_for_user($userId, 'fair-critic', $this->countFeedback($userId, 20, true));
        }

        $rateable = $event->rating->rateable;
        if ($length >= 40 && $rateable && $this->hasCommentOn($userId, $rateable)) {
            upgrade_mission_for_user($userId, 'collaborative-feedback');
            upgrade_mission_for_user($userId, 'feedback-follower');
        }
    }

    protected function countFeedback(int $userId, int $minLength, bool $balanced = false): int
    {
        return Rating::query()
            ->where('user_id', $userId)
            ->get(['rating', 'comment'])
            ->filter(function ($rating) use ($minLength, $balanced) {
                if (mb_strlen(trim((string) $rating->comment)) < $minLength) {
                    return false;
                }

                if ($balanced && ((int) $rating->rating < 2 || (int) $rating->rating > 4)) {
                    return false;
                }

                return true;
            })
            ->count();
    }

    protected function hasCommentOn(int $userId, $rateable): bool
    {
        return Comment::query()
            ->where('user_id', $userId)
            ->where('commentable_id', $rateable->id)
            ->where(function ($query) use ($rateable) {
                $query->where('commentable_type', $rateable->getMorphClass())
                    ->orWhere('commentable_type', class_basename($rateable));
            })
            ->exists();
    }
}
