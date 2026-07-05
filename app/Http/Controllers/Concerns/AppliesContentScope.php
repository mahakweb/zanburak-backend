<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Comment;
use App\Models\Course;
use App\Models\Like;
use App\Models\Payment;
use App\Models\View;
use App\Services\Security\ContentScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use LaravelInteraction\Bookmark\Bookmark;

trait AppliesContentScope
{
    protected function contentScope(): ContentScope
    {
        return ContentScope::for(auth()->user());
    }

    protected function scopedCoursesQuery(): Builder
    {
        return $this->contentScope()->applyToCourses(Course::query());
    }

    protected function scopedViewsQuery(): Builder
    {
        return $this->contentScope()->applyToViewableEngagement(View::query());
    }

    protected function scopedLikesQuery(): Builder
    {
        return $this->contentScope()->applyToLikes(Like::query());
    }

    protected function scopedBookmarksQuery(): Builder
    {
        return $this->contentScope()->applyToBookmarks(Bookmark::query());
    }

    protected function scopedCommentsQuery(): Builder
    {
        return $this->contentScope()->applyToComments(Comment::query());
    }

    protected function scopedPaymentsQuery(): Builder
    {
        return $this->contentScope()->applyToPayments(Payment::query());
    }

    protected function canViewGlobalUserMetrics(): bool
    {
        return $this->contentScope()->canViewGlobalUserMetrics();
    }

    protected function emptyDailySeries(Collection $labelDates): Collection
    {
        return $labelDates->map(fn () => 0);
    }

    protected function authorizeCourse(Course $course, string $action = 'view'): void
    {
        $this->contentScope()->authorizeCourse($course, $action);
    }
}
