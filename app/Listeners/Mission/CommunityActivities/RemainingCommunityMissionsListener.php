<?php

namespace App\Listeners\Mission\CommunityActivities;

use App\Events\Mission\CommunityActivityEvent;
use App\Models\Answer;
use App\Models\Comment;
use App\Models\Question;
use App\Models\Rating;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RemainingCommunityMissionsListener
{
    public function handle(CommunityActivityEvent $event): void
    {
        if ($event->type === 'answer' && $event->answer) {
            $this->onAnswer($event);
        }

        if ($event->type === 'question' && $event->question) {
            $this->syncPopularQuestions($event->user->id);
        }

        if ($event->type === 'comment' && $event->comment) {
            $this->onComment($event);
        }

        if ($event->type === 'report' && $event->report && $event->report->status) {
            $this->onReport($event);
        }
    }

    protected function onAnswer(CommunityActivityEvent $event): void
    {
        $answer = $event->answer;
        $question = $answer->question;
        if (!$question) {
            return;
        }

        $firstAnswerId = Answer::where('question_id', $question->id)->min('id');
        if ((int) $firstAnswerId === (int) $answer->id) {
            $firstAnswers = (int) DB::table('answers as a')
                ->where('a.user_id', $answer->user_id)
                ->whereRaw('a.id = (select min(a2.id) from answers a2 where a2.question_id = a.question_id)')
                ->count();

            sync_mission_progress_for_user($answer->user_id, 'first-responder', $firstAnswers);
        }

        $this->syncPopularQuestions($question->user_id);
        $this->syncQuestionGuide($answer->user_id);

        if ($question->best_answer) {
            $followed = Question::where('user_id', $question->user_id)
                ->whereNotNull('best_answer')
                ->count();
            sync_mission_progress_for_user($question->user_id, 'question-follower', (int) $followed);
        }
    }

    protected function syncPopularQuestions(int $userId): void
    {
        $popular = Question::where('user_id', $userId)->has('answers', '>=', 5)->count();
        sync_mission_progress_for_user($userId, 'popular-questions', (int) $popular);
    }

    protected function syncQuestionGuide(int $userId): void
    {
        $guided = Answer::query()
            ->where('answers.user_id', $userId)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('likes')
                    ->join('questions', 'questions.id', '=', 'answers.question_id')
                    ->whereColumn('likes.likeable_id', 'answers.id')
                    ->where('likes.type', 'like')
                    ->whereColumn('likes.user_id', 'questions.user_id')
                    ->where(function ($types) {
                        $types->where('likes.likeable_type', Answer::class)
                            ->orWhere('likes.likeable_type', 'Answer');
                    });
            })
            ->count();

        sync_mission_progress_for_user($userId, 'question-guide', (int) $guided);
    }

    protected function onComment(CommunityActivityEvent $event): void
    {
        $comment = $event->comment;
        $userId = $event->user->id;

        $hasRating = Rating::query()
            ->where('user_id', $userId)
            ->where('rateable_id', $comment->commentable_id)
            ->where(function ($query) use ($comment) {
                $query->where('rateable_type', $comment->commentable_type)
                    ->orWhere('rateable_type', class_basename((string) $comment->commentable_type));
            })
            ->exists();

        if ($hasRating) {
            upgrade_mission_for_user($userId, 'feedback-follower');
        }

        if (mb_strlen(trim((string) $comment->comment)) >= 120) {
            upgrade_mission_for_user($userId, 'comprehensive-feedback');
        }
    }

    protected function onReport(CommunityActivityEvent $event): void
    {
        $report = $event->report;
        $userId = $event->user->id;
        $kind = strtolower(class_basename((string) $report->reportable_type));
        $textLength = mb_strlen(trim((string) $report->report));

        if (in_array($kind, ['question', 'answer'], true)) {
            sync_mission_progress_for_user($userId, 'community-guardian', $this->approvedCount($userId, ['question', 'answer']));
        }

        if (in_array($kind, ['question', 'answer', 'comment'], true)) {
            sync_mission_progress_for_user($userId, 'reviewer-community', $this->approvedCount($userId, ['question', 'answer', 'comment']));
        }

        if (in_array($kind, ['course', 'episode'], true)) {
            sync_mission_progress_for_user($userId, 'educational-reporter', $this->approvedCount($userId, ['course', 'episode']));
        }

        $reportable = $report->reportable;
        if ($reportable && $reportable->created_at && $report->created_at && elapsed_hours($reportable->created_at, $report->created_at) <= 1) {
            upgrade_mission_for_user($userId, 'fast-reporter');
        }

        $approvedThisMonth = Report::query()
            ->where('user_id', $userId)
            ->where('status', true)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        if ($approvedThisMonth >= 10) {
            upgrade_mission_for_user($userId, 'active-reporter');
        }

        if ($textLength >= 10) {
            sync_mission_progress_for_user($userId, 'fair-reporter', $this->approvedWithMinLength($userId, 10));
        }
        if ($textLength >= 40) {
            sync_mission_progress_for_user($userId, 'constructive-reporter', $this->approvedWithMinLength($userId, 40));
        }
        if ($textLength >= 80) {
            sync_mission_progress_for_user($userId, 'supportive-reporter', $this->approvedWithMinLength($userId, 80));
        }
        if ($textLength >= 100) {
            sync_mission_progress_for_user($userId, 'transparency', $this->approvedWithMinLength($userId, 100));
        }

        $siblingReports = Report::query()
            ->where('reportable_id', $report->reportable_id)
            ->where('id', '!=', $report->id)
            ->count();

        if ($siblingReports >= 1) {
            upgrade_mission_for_user($userId, 'collaborative-reporter');
        }

        if ($reportable && (
            (isset($reportable->publish) && !$reportable->publish)
            || (isset($reportable->approved) && !$reportable->approved)
        )) {
            upgrade_mission_for_user($userId, 'reformer');
        }
    }

    protected function approvedCount(int $userId, array $kinds): int
    {
        return Report::query()
            ->where('user_id', $userId)
            ->where('status', true)
            ->get(['reportable_type'])
            ->filter(function ($report) use ($kinds) {
                return in_array(strtolower(class_basename((string) $report->reportable_type)), $kinds, true);
            })
            ->count();
    }

    protected function approvedWithMinLength(int $userId, int $min): int
    {
        return Report::query()
            ->where('user_id', $userId)
            ->where('status', true)
            ->get(['report'])
            ->filter(fn ($report) => mb_strlen(trim((string) $report->report)) >= $min)
            ->count();
    }
}
