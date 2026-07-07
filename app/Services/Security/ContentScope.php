<?php

namespace App\Services\Security;

use App\Models\Article;
use App\Models\Certificate;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Payment;
use App\Models\Quiz\Quiz;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContentScope
{
    public const ANY = 'any';

    public const OWN = 'own';

    public const NONE = 'none';

    /**
     * View/list scope resolution per domain.
     *
     * @var array<string, array{any: string[], own: string[]}>
     */
    protected static array $viewScopes = [
        'courses' => [
            'any' => ['courses.view.any', 'courses.list.any', 'courses.view', 'courses.list'],
            'own' => ['courses.view.own', 'courses.list.own'],
        ],
        'comments' => [
            'any' => [
                'comments.view.any',
                'comments.view',
                'comments.moderate',
                'comments.moderate.any',
                'comments.course.view.any',
                'comments.episode.view.any',
                'comments.article.view.any',
                'comments.path.view.any',
            ],
            'own' => [
                'comments.view.own',
                'comments.moderate.own',
                'comments.course.view.own',
                'comments.episode.view.own',
                'comments.article.view.own',
                'comments.path.view.own',
                'comments.reply.own',
                'comments.delete.own',
            ],
        ],
        'articles' => [
            'any' => ['articles.view.any', 'articles.list.any', 'articles.view', 'articles.list'],
            'own' => ['articles.view.own', 'articles.list.own'],
        ],
        'payments' => [
            'any' => ['payments.view.any', 'payments.view', 'payments.export', 'payments.export.any', 'payments.stats.any'],
            'own' => ['payments.view.own', 'payments.stats.own', 'payments.export.own'],
        ],
        'certificates' => [
            'any' => ['certificates.view.any', 'certificates.view'],
            'own' => ['certificates.view.own'],
        ],
        'quizzes' => [
            'any' => ['quizzes.view.any', 'quizzes.view'],
            'own' => ['quizzes.view.own'],
        ],
        'analytics' => [
            'any' => ['analytics.view.any', 'analytics.view'],
            'own' => ['analytics.view.own', 'payments.view.own'],
        ],
    ];

    /**
     * @var array<string, array<string, array{any: string[], own: string[]}>>
     */
    protected static array $actionPermissions = [
        'courses' => [
            'view' => [
                'any' => ['courses.view.any', 'courses.view', 'courses.overview.view'],
                'own' => ['courses.view.own'],
            ],
            'create' => [
                'any' => ['courses.create'],
                'own' => ['courses.create'],
            ],
            'update' => [
                'any' => ['courses.update.any', 'courses.update'],
                'own' => ['courses.update.own'],
            ],
            'delete' => [
                'any' => ['courses.delete.any', 'courses.delete'],
                'own' => ['courses.delete.own'],
            ],
            'publish' => [
                'any' => ['courses.publish.any', 'courses.publish', 'courses.unpublish', 'courses.unpublish.any'],
                'own' => ['courses.publish.own', 'courses.unpublish.own'],
            ],
            'assign_user' => [
                'any' => ['courses.assign_user.any', 'courses.assign_user'],
                'own' => ['courses.assign_user.own'],
            ],
            'reorder_episodes' => [
                'any' => ['courses.reorder_episodes.any', 'courses.reorder_episodes'],
                'own' => ['courses.reorder_episodes.own'],
            ],
        ],
        'episodes' => [
            'view' => [
                'any' => ['episodes.view.any', 'episodes.view', 'episodes.edit.any', 'episodes.edit', 'episodes.get_for_edit'],
                'own' => ['episodes.view.own', 'episodes.edit.own'],
            ],
            'create' => [
                'any' => ['episodes.create.any', 'episodes.create'],
                'own' => ['episodes.create.own'],
            ],
            'edit' => [
                'any' => ['episodes.edit.any', 'episodes.edit', 'episodes.get_for_edit'],
                'own' => ['episodes.edit.own'],
            ],
            'delete' => [
                'any' => ['episodes.delete.any', 'episodes.delete'],
                'own' => ['episodes.delete.own'],
            ],
            'reorder' => [
                'any' => ['episodes.reorder.any', 'episodes.reorder'],
                'own' => ['episodes.reorder.own'],
            ],
        ],
        'videos' => [
            'upload' => [
                'any' => ['videos.upload.any', 'videos.upload'],
                'own' => ['videos.upload.own'],
            ],
            'process' => [
                'any' => ['videos.process.any', 'videos.process'],
                'own' => ['videos.process.own'],
            ],
        ],
        'comments' => [
            'view' => [
                'any' => ['comments.view.any', 'comments.view', 'comments.course.view.any', 'comments.episode.view.any', 'comments.article.view.any'],
                'own' => ['comments.view.own', 'comments.course.view.own', 'comments.episode.view.own', 'comments.article.view.own'],
            ],
            'moderate' => [
                'any' => ['comments.moderate.any', 'comments.moderate'],
                'own' => ['comments.moderate.own'],
            ],
            'reply' => [
                'any' => ['comments.reply.any', 'comments.reply'],
                'own' => ['comments.reply.own'],
            ],
            'delete' => [
                'any' => ['comments.delete.any', 'comments.delete'],
                'own' => ['comments.delete.own'],
            ],
        ],
        'articles' => [
            'view' => [
                'any' => ['articles.view.any', 'articles.view', 'articles.overview.view'],
                'own' => ['articles.view.own'],
            ],
            'create' => [
                'any' => ['articles.create'],
                'own' => ['articles.create'],
            ],
            'update' => [
                'any' => ['articles.update.any', 'articles.update'],
                'own' => ['articles.update.own'],
            ],
            'delete' => [
                'any' => ['articles.delete.any', 'articles.delete'],
                'own' => ['articles.delete.own'],
            ],
            'publish' => [
                'any' => ['articles.publish.any', 'articles.publish', 'articles.unpublish', 'articles.unpublish.any'],
                'own' => ['articles.publish.own', 'articles.unpublish.own'],
            ],
        ],
        'sections' => [
            'view' => [
                'any' => ['sections.view.any', 'sections.view'],
                'own' => ['sections.view.own'],
            ],
            'create' => [
                'any' => ['sections.create.any', 'sections.create'],
                'own' => ['sections.create.own'],
            ],
            'update' => [
                'any' => ['sections.update.any', 'sections.update'],
                'own' => ['sections.update.own'],
            ],
            'delete' => [
                'any' => ['sections.delete.any', 'sections.delete'],
                'own' => ['sections.delete.own'],
            ],
        ],
        'payments' => [
            'view' => [
                'any' => ['payments.view.any', 'payments.view'],
                'own' => ['payments.view.own'],
            ],
            'export' => [
                'any' => ['payments.export.any', 'payments.export'],
                'own' => ['payments.export.own'],
            ],
            'stats' => [
                'any' => ['payments.stats.any', 'payments.stats'],
                'own' => ['payments.stats.own'],
            ],
            'create' => [
                'any' => ['payments.create.any', 'payments.create'],
                'own' => ['payments.create.own'],
            ],
            'update' => [
                'any' => ['payments.update_status.any', 'payments.update', 'payments.update_status'],
                'own' => ['payments.update_status.own'],
            ],
            'delete' => [
                'any' => ['payments.delete.any', 'payments.delete'],
                'own' => ['payments.delete.own'],
            ],
        ],
        'certificates' => [
            'view' => [
                'any' => ['certificates.view.any', 'certificates.view'],
                'own' => ['certificates.view.own'],
            ],
            'create' => [
                'any' => ['certificates.create.any', 'certificates.create'],
                'own' => ['certificates.create.own'],
            ],
            'update' => [
                'any' => ['certificates.update.any', 'certificates.update'],
                'own' => ['certificates.update.own'],
            ],
            'delete' => [
                'any' => ['certificates.delete.any', 'certificates.delete'],
                'own' => ['certificates.delete.own'],
            ],
            'export' => [
                'any' => ['certificates.export.any', 'certificates.export'],
                'own' => ['certificates.export.own'],
            ],
        ],
        'quizzes' => [
            'view' => [
                'any' => ['quizzes.view.any', 'quizzes.view'],
                'own' => ['quizzes.view.own'],
            ],
            'create' => [
                'any' => ['quizzes.create.any', 'quizzes.create'],
                'own' => ['quizzes.create.own'],
            ],
            'update' => [
                'any' => ['quizzes.update.any', 'quizzes.update'],
                'own' => ['quizzes.update.own'],
            ],
            'delete' => [
                'any' => ['quizzes.delete.any', 'quizzes.delete'],
                'own' => ['quizzes.delete.own'],
            ],
            'reports' => [
                'any' => ['quizzes.reports.any', 'quizzes.reports'],
                'own' => ['quizzes.reports.own'],
            ],
            'review' => [
                'any' => ['quizzes.review.any', 'quizzes.review'],
                'own' => ['quizzes.review.own'],
            ],
        ],
    ];

    public function __construct(protected User $user) {}

    public static function for(User $user): self
    {
        return new self($user);
    }

    public function viewScope(string $domain): string
    {
        if ($this->user->isSuperUser()) {
            return self::ANY;
        }

        $config = self::$viewScopes[$domain] ?? null;
        if (! $config) {
            return self::NONE;
        }

        if ($this->user->hasAnyPermissionName($config['any'])) {
            return self::ANY;
        }

        if ($this->user->hasAnyPermissionName($config['own'])) {
            return self::OWN;
        }

        return self::NONE;
    }

    /**
     * @return array<string, string>
     */
    public function resolvedScopes(): array
    {
        $scopes = [];
        foreach (array_keys(self::$viewScopes) as $domain) {
            $scopes[$domain] = $this->viewScope($domain);
        }

        return $scopes;
    }

    public function canViewGlobalUserMetrics(): bool
    {
        if ($this->user->isSuperUser()) {
            return true;
        }

        return $this->user->hasAnyPermissionName(['users.view', 'analytics.view']);
    }

    public function canViewGlobalPlatformMetrics(): bool
    {
        if ($this->user->isSuperUser()) {
            return true;
        }

        return $this->viewScope('analytics') === self::ANY;
    }

    public function dashboardCapabilities(): array
    {
        return [
            'users' => $this->canViewGlobalUserMetrics(),
            'platform' => $this->viewScope('analytics') !== self::NONE || $this->viewScope('courses') === self::OWN,
            'payments' => $this->viewScope('payments') !== self::NONE,
            'comments' => $this->viewScope('comments') !== self::NONE,
            'courses' => $this->viewScope('courses') !== self::NONE,
            'certificates' => $this->viewScope('certificates') !== self::NONE,
            'quizzes' => $this->viewScope('quizzes') !== self::NONE,
        ];
    }

    public function canAction(string $domain, string $action, ?Model $model = null): bool
    {
        if ($this->user->isSuperUser()) {
            return true;
        }

        $permissions = self::$actionPermissions[$domain][$action] ?? null;
        if (! $permissions) {
            return false;
        }

        if ($this->user->hasAnyPermissionName($permissions['any'])) {
            return true;
        }

        if (! $this->user->hasAnyPermissionName($permissions['own'])) {
            return false;
        }

        if ($model === null) {
            return true;
        }

        return $this->owns($domain, $model);
    }

    public function authorizeAction(string $domain, string $action, ?Model $model = null, string $message = 'You are not allowed to perform this action'): void
    {
        if (! $this->canAction($domain, $action, $model)) {
            abort(403, $message);
        }
    }

    public function canCourse(Course $course, string $action = 'view'): bool
    {
        return $this->canAction('courses', $action, $course);
    }

    public function authorizeCourse(Course $course, string $action = 'view'): void
    {
        $this->authorizeAction('courses', $action, $course, 'You are not allowed to access this course');
    }

    public function canComment(Comment $comment, string $action = 'view'): bool
    {
        return $this->canAction('comments', $action, $comment);
    }

    public function authorizeComment(Comment $comment, string $action = 'view'): void
    {
        $this->authorizeAction('comments', $action, $comment, 'You are not allowed to access this comment');
    }

    public function canArticle(Article $article, string $action = 'view'): bool
    {
        return $this->canAction('articles', $action, $article);
    }

    public function authorizeArticle(Article $article, string $action = 'view'): void
    {
        $this->authorizeAction('articles', $action, $article, 'You are not allowed to access this article');
    }

    public function canPayment(Payment $payment, string $action = 'view'): bool
    {
        return $this->canAction('payments', $action, $payment);
    }

    public function authorizePayment(Payment $payment, string $action = 'view'): void
    {
        $this->authorizeAction('payments', $action, $payment, 'You are not allowed to access this payment');
    }

    public function canCertificate(Certificate $certificate, string $action = 'view'): bool
    {
        if ($this->user->isSuperUser()) {
            return true;
        }

        if ($action === 'view') {
            if ($this->viewScope('certificates') === self::ANY) {
                return true;
            }

            return $this->ownsCertificate($certificate);
        }

        return $this->canAction('certificates', $action, $certificate);
    }

    public function authorizeCertificate(Certificate $certificate, string $action = 'view'): void
    {
        $this->authorizeAction('certificates', $action, $certificate, 'You are not allowed to access this certificate');
    }

    public function canQuiz(Quiz $quiz, string $action = 'view'): bool
    {
        if ($this->user->isSuperUser()) {
            return true;
        }

        $permissions = self::$actionPermissions['quizzes'][$action] ?? null;
        if (! $permissions) {
            return false;
        }

        if ($this->user->hasAnyPermissionName($permissions['any'])) {
            return true;
        }

        if (! $this->user->hasAnyPermissionName($permissions['own'])) {
            return false;
        }

        $course = $quiz->relatedCourse();
        if ($course === null) {
            return false;
        }

        return (int) $course->teacher_id === (int) $this->user->id;
    }

    public function authorizeQuiz(Quiz $quiz, string $action = 'view'): void
    {
        if (! $this->canQuiz($quiz, $action)) {
            abort(403, 'You are not allowed to access this quiz');
        }
    }

    public function owns(string $domain, Model $model): bool
    {
        return match ($domain) {
            'courses' => $model instanceof Course && (int) $model->teacher_id === (int) $this->user->id,
            'articles' => $model instanceof Article && (int) $model->user_id === (int) $this->user->id,
            'comments' => $model instanceof Comment && $this->ownsComment($model),
            'payments' => $model instanceof Payment && $this->ownsPayment($model),
            'certificates' => $model instanceof Certificate && $this->ownsCertificate($model),
            'episodes' => $model instanceof Episode && $this->ownsEpisode($model),
            default => false,
        };
    }

    public function applyToCourses(Builder $query): Builder
    {
        $scope = $this->viewScope('courses');
        if ($scope === self::ANY) {
            return $query;
        }
        if ($scope === self::NONE) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('teacher_id', $this->user->id);
    }

    public function applyToArticles(Builder $query): Builder
    {
        $scope = $this->viewScope('articles');
        if ($scope === self::ANY) {
            return $query;
        }
        if ($scope === self::NONE) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('user_id', $this->user->id);
    }

    public function applyToComments(Builder $query): Builder
    {
        $scope = $this->viewScope('comments');
        if ($scope === self::ANY) {
            return $query;
        }
        if ($scope === self::NONE) {
            return $query->whereRaw('1 = 0');
        }

        $userId = $this->user->id;

        return $query->where(function (Builder $q) use ($userId) {
            $q->where(function (Builder $q2) use ($userId) {
                $q2->where('commentable_type', Course::class)
                    ->whereIn('commentable_id', Course::query()->where('teacher_id', $userId)->select('id'));
            })->orWhere(function (Builder $q2) use ($userId) {
                $q2->where('commentable_type', Episode::class)
                    ->whereIn('commentable_id', Episode::query()
                        ->whereHas('section.course', fn (Builder $c) => $c->where('teacher_id', $userId))
                        ->select('id'));
            })->orWhere(function (Builder $q2) use ($userId) {
                $q2->where('commentable_type', Article::class)
                    ->whereIn('commentable_id', Article::query()->where('user_id', $userId)->select('id'));
            });
        });
    }

    public function applyToPayments(Builder $query): Builder
    {
        $scope = $this->viewScope('payments');
        if ($scope === self::ANY) {
            return $query;
        }
        if ($scope === self::NONE) {
            return $query->whereRaw('1 = 0');
        }

        $userId = $this->user->id;
        $courseIds = Course::query()->where('teacher_id', $userId)->pluck('id');

        return $query->whereHas('items', function (Builder $itemQuery) use ($courseIds) {
            $itemQuery->where('payable_type', Course::class)
                ->whereIn('payable_id', $courseIds);
        });
    }

    public function applyToCertificates(Builder $query): Builder
    {
        $scope = $this->viewScope('certificates');
        if ($scope === self::ANY) {
            return $query;
        }

        if ($scope === self::NONE) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('course', fn (Builder $c) => $c->where('teacher_id', $this->user->id));
    }

    public function applyToViewableEngagement(Builder $query, string $morphPrefix = ''): Builder
    {
        if ($morphPrefix !== '') {
            if ($this->canViewGlobalPlatformMetrics()) {
                return $query;
            }

            if ($this->viewScope('analytics') === self::NONE && $this->viewScope('courses') !== self::OWN) {
                return $query->whereRaw('1 = 0');
            }

            $prefix = rtrim($morphPrefix, '.').'.';
            $userId = $this->user->id;
            $courseIds = Course::query()->where('teacher_id', $userId)->select('id');
            $episodeIds = Episode::query()
                ->whereHas('section.course', fn (Builder $c) => $c->where('teacher_id', $userId))
                ->select('id');

            return $query->where(function (Builder $q) use ($prefix, $courseIds, $episodeIds) {
                $q->where(function (Builder $q2) use ($prefix, $courseIds) {
                    $q2->where($prefix.'viewable_type', Course::class)
                        ->whereIn($prefix.'viewable_id', $courseIds);
                })->orWhere(function (Builder $q2) use ($prefix, $episodeIds) {
                    $q2->where($prefix.'viewable_type', Episode::class)
                        ->whereIn($prefix.'viewable_id', $episodeIds);
                });
            });
        }

        return $this->applyToOwnedMorphEngagement($query, 'viewable_type', 'viewable_id');
    }

    public function applyToLikes(Builder $query): Builder
    {
        return $this->applyToOwnedMorphEngagement($query, 'likeable_type', 'likeable_id');
    }

    public function applyToBookmarks(Builder $query): Builder
    {
        return $this->applyToOwnedMorphEngagement($query, 'bookmarkable_type', 'bookmarkable_id');
    }

    protected function applyToOwnedMorphEngagement(Builder $query, string $typeColumn, string $idColumn): Builder
    {
        if ($this->canViewGlobalPlatformMetrics()) {
            return $query;
        }

        $hasAnalyticsOwn = $this->viewScope('analytics') === self::OWN;
        $hasCourseOwn = $this->viewScope('courses') === self::OWN;
        $hasArticleOwn = $this->viewScope('articles') === self::OWN;

        if (! $hasAnalyticsOwn && ! $hasCourseOwn && ! $hasArticleOwn) {
            return $query->whereRaw('1 = 0');
        }

        $userId = $this->user->id;

        return $query->where(function (Builder $q) use ($typeColumn, $idColumn, $hasCourseOwn, $hasAnalyticsOwn, $hasArticleOwn, $userId) {
            if ($hasCourseOwn || $hasAnalyticsOwn) {
                $courseIds = Course::query()->where('teacher_id', $userId)->select('id');
                $episodeIds = Episode::query()
                    ->whereHas('section.course', fn (Builder $c) => $c->where('teacher_id', $userId))
                    ->select('id');

                $q->where(function (Builder $inner) use ($typeColumn, $idColumn, $courseIds) {
                    $inner->where($typeColumn, Course::class)->whereIn($idColumn, $courseIds);
                })->orWhere(function (Builder $inner) use ($typeColumn, $idColumn, $episodeIds) {
                    $inner->where($typeColumn, Episode::class)->whereIn($idColumn, $episodeIds);
                });
            }

            if ($hasArticleOwn) {
                $articleIds = Article::query()->where('user_id', $userId)->select('id');
                $q->orWhere(function (Builder $inner) use ($typeColumn, $idColumn, $articleIds) {
                    $inner->where($typeColumn, Article::class)->whereIn($idColumn, $articleIds);
                });
            }
        });
    }

    public function constrainJoinedPayments(Builder $query, string $paymentsAlias = 'payments'): Builder
    {
        $scope = $this->viewScope('payments');
        if ($scope === self::ANY) {
            return $query;
        }
        if ($scope === self::NONE) {
            return $query->whereRaw('1 = 0');
        }

        $paymentIds = $this->applyToPayments(Payment::query())->select('id');

        return $query->whereIn("{$paymentsAlias}.id", $paymentIds);
    }

    public function applyToVideoViews(Builder $query): Builder
    {
        if ($this->canViewGlobalPlatformMetrics()) {
            return $query;
        }

        if ($this->viewScope('analytics') === self::NONE && $this->viewScope('courses') !== self::OWN) {
            return $query->whereRaw('1 = 0');
        }

        $userId = $this->user->id;
        $courseIds = Course::query()->where('teacher_id', $userId)->select('id');
        $episodeIds = Episode::query()
            ->whereHas('section.course', fn (Builder $c) => $c->where('teacher_id', $userId))
            ->select('id');

        return $query->whereHas('video', function (Builder $videoQuery) use ($courseIds, $episodeIds) {
            $videoQuery->where(function (Builder $inner) use ($episodeIds) {
                $inner->where('videoable_type', Episode::class)
                    ->whereIn('videoable_id', $episodeIds);
            })->orWhere(function (Builder $inner) use ($courseIds) {
                $inner->where('videoable_type', Course::class)
                    ->whereIn('videoable_id', $courseIds);
            });
        });
    }

    public function applyToQuizzes(Builder $query): Builder
    {
        $scope = $this->viewScope('quizzes');
        if ($scope === self::ANY) {
            return $query;
        }
        if ($scope === self::NONE) {
            return $query->whereRaw('1 = 0');
        }

        $userId = $this->user->id;
        $courseIds = Course::query()->where('teacher_id', $userId)->pluck('id');
        $sectionIds = Section::query()->whereIn('course_id', $courseIds)->pluck('id');
        $episodeIds = Episode::query()->whereIn('section_id', $sectionIds)->pluck('id');

        return $query->where(function (Builder $q) use ($courseIds, $sectionIds, $episodeIds) {
            $q->where(function (Builder $q2) use ($courseIds) {
                $q2->where('quizzable_type', Course::class)->whereIn('quizzable_id', $courseIds);
            })->orWhere(function (Builder $q2) use ($sectionIds) {
                $q2->where('quizzable_type', Section::class)->whereIn('quizzable_id', $sectionIds);
            })->orWhere(function (Builder $q2) use ($episodeIds) {
                $q2->where('quizzable_type', Episode::class)->whereIn('quizzable_id', $episodeIds);
            });
        });
    }

    protected function ownsComment(Comment $comment): bool
    {
        $comment->loadMissing('commentable');

        return match ($comment->commentable_type) {
            Course::class => $comment->commentable instanceof Course
                && (int) $comment->commentable->teacher_id === (int) $this->user->id,
            Episode::class => $comment->commentable instanceof Episode
                && (int) $comment->commentable->section?->course?->teacher_id === (int) $this->user->id,
            Article::class => $comment->commentable instanceof Article
                && (int) $comment->commentable->user_id === (int) $this->user->id,
            default => false,
        };
    }

    protected function ownsPayment(Payment $payment): bool
    {
        $payment->loadMissing('items.payable');
        $userId = $this->user->id;

        foreach ($payment->items as $item) {
            if ($item->payable_type === Course::class && $item->payable instanceof Course) {
                if ((int) $item->payable->teacher_id === $userId) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function ownsCertificate(Certificate $certificate): bool
    {
        $certificate->loadMissing('course');

        return $certificate->course && (int) $certificate->course->teacher_id === (int) $this->user->id;
    }

    protected function ownsEpisode(Episode $episode): bool
    {
        $episode->loadMissing('section.course');

        return (int) $episode->section?->course?->teacher_id === (int) $this->user->id;
    }
}
