<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route as RouteFacade;

class RoutePermissionsSeeder extends Seeder
{
    /**
     * Seed permission_routes for admin API routes (api.admin.*).
     */
    public function run(): void
    {
        $map = $this->routePermissionMap();
        $permissions = DB::table('permissions')->pluck('id', 'name');
        $now = now();
        $rows = [];
        $seen = [];

        $adminRoutes = collect(RouteFacade::getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter(fn ($name) => is_string($name) && str_starts_with($name, 'api.admin.'))
            ->unique()
            ->sort()
            ->values();

        $unmapped = [];

        foreach ($adminRoutes as $routeName) {
            $permissionNames = $map[$routeName] ?? [];

            if ($permissionNames === []) {
                $unmapped[] = $routeName;
                continue;
            }

            foreach ($permissionNames as $permName) {
                if (!isset($permissions[$permName])) {
                    $this->command?->warn("Unknown permission [{$permName}] for route [{$routeName}]");
                    continue;
                }

                $key = $permissions[$permName] . '|' . $routeName;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $rows[] = [
                    'permission_id' => $permissions[$permName],
                    'route_name' => $routeName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('permission_routes')->upsert(
                $rows,
                ['permission_id', 'route_name'],
                ['updated_at']
            );
        }

        $this->command?->info(sprintf(
            'Seeded %d route-permission mappings for %d admin routes (%d unmapped).',
            count($rows),
            $adminRoutes->count(),
            count($unmapped)
        ));

        foreach ($unmapped as $routeName) {
            $this->command?->warn("No permission mapping for route: {$routeName}");
        }
    }

    /**
     * Map admin API route names to required permissions.
     * Middleware uses OR logic: the user needs at least one listed permission.
     */
    private function routePermissionMap(): array
    {
        $map = [];

        $assign = static function (array|string $routes, array $permissions) use (&$map): void {
            foreach ((array) $routes as $route) {
                $map[$route] = array_values(array_unique(array_merge($map[$route] ?? [], $permissions)));
            }
        };

        $coursesList = ['courses.list', 'courses.list.any', 'courses.view', 'courses.view.own', 'courses.view.any'];
        $coursesView = ['courses.view', 'courses.view.own', 'courses.view.any', 'courses.overview.view'];
        $coursesUpdate = ['courses.update', 'courses.update.own', 'courses.update.any'];
        $coursesDelete = ['courses.delete', 'courses.delete.own', 'courses.delete.any'];
        $coursesAssign = ['courses.assign_user', 'courses.assign_user.own', 'courses.assign_user.any'];
        $coursesReorder = ['courses.reorder_episodes', 'courses.reorder_episodes.own', 'courses.reorder_episodes.any'];
        $episodesCreate = ['episodes.create', 'episodes.create.own', 'episodes.create.any'];
        $episodesEdit = ['episodes.edit', 'episodes.edit.own', 'episodes.edit.any', 'episodes.get_for_edit'];
        $episodesDelete = ['episodes.delete', 'episodes.delete.own', 'episodes.delete.any'];
        $episodesManage = ['episodes.upload_file', 'episodes.status', 'episodes.reorder', 'episodes.reorder.own', 'episodes.reorder.any'];
        $videosUpload = ['videos.upload', 'videos.upload.own', 'videos.upload.any'];
        $videosProcess = ['videos.process', 'videos.process.own', 'videos.process.any'];
        $commentsView = ['comments.view', 'comments.view.own', 'comments.view.any', 'comments.course.view.own', 'comments.course.view.any'];
        $commentsModerate = ['comments.moderate', 'comments.moderate.own', 'comments.moderate.any'];
        $commentsReply = ['comments.reply', 'comments.reply.own', 'comments.reply.any'];
        $commentsDelete = ['comments.delete.own', 'comments.delete.any'];
        $paymentsView = ['payments.view', 'payments.view.own', 'payments.view.any'];
        $usersView = ['users.view'];
        $discussRead = ['discuss.list', 'discuss.show'];
        $discussWrite = ['discuss.create', 'discuss.update', 'discuss.delete', 'discuss.answer.create'];
        $articlesRead = ['articles.view', 'articles.list', 'articles.view.own', 'articles.view.any', 'articles.list.any'];
        $articlesWrite = ['articles.create', 'articles.update', 'articles.update.own', 'articles.update.any', 'articles.delete', 'articles.delete.own', 'articles.delete.any'];
        $notificationsRead = ['notifications.view', 'notifications.details'];
        $notificationsWrite = ['notifications.view', 'notifications.delete'];
        $marketingAccess = ['marketing.view', 'marketing.manage', 'marketing.update'];
        $securityView = ['security.access.view', 'security.routes.view'];
        $securityManage = ['security.access.manage', 'security.routes.manage'];

        // Security / route access
        $assign('api.admin.routes.index', ['security.routes.view']);
        $assign([
            'api.admin.routes.permissions.add',
            'api.admin.routes.permissions.remove',
            'api.admin.routes.permissions.remove-all',
        ], ['security.routes.manage']);
        $assign('api.admin.security.access', array_merge($securityView, $securityManage));

        // Dashboard
        $assign([
            'api.admin.dashboard.stats',
            'api.admin.dashboard.unapproved-comments-count',
        ], ['dashboard.view']);

        // System resources
        $assign([
            'api.admin.system.resources.index',
            'api.admin.system.resources.history',
        ], ['system.resources.view']);

        // Payments
        $assign('api.admin.payments.index', $paymentsView);
        $assign('api.admin.payments.stats', ['payments.stats']);
        $assign('api.admin.payments.export', ['payments.export', 'payments.export.any']);
        $assign('api.admin.payments.details', array_merge($paymentsView, ['payments.details']));
        $assign('api.admin.payments.update-status', ['payments.update_status', 'payments.update_status.any']);
        $assign('api.admin.payments.delete', ['payments.delete', 'payments.delete.any']);
        $assign('api.admin.payments.create', ['payments.create']);
        $assign('api.admin.payments.search', ['payments.search']);
        $assign('api.admin.payments.users', ['payments.search.users']);
        $assign('api.admin.payments.courses', ['payments.search.courses']);
        $assign('api.admin.payments.plans', ['payments.search.plans']);
        $assign('api.admin.payments.paths', ['payments.search.paths']);

        // Certificates
        $assign([
            'api.admin.certificates.index',
            'api.admin.certificates.stats',
            'api.admin.certificates.export',
            'api.admin.certificates.details',
            'api.admin.certificates.search',
            'api.admin.certificates.users',
            'api.admin.certificates.courses',
        ], ['certificates.view']);
        $assign('api.admin.certificates.create', ['certificates.create']);
        $assign('api.admin.certificates.update', ['certificates.update']);
        $assign('api.admin.certificates.issue', ['certificates.create']);
        $assign('api.admin.certificates.revoke', ['certificates.update']);
        $assign('api.admin.certificates.delete', ['certificates.delete']);

        // Certificate Templates
        $assign([
            'api.admin.certificate-templates.index',
            'api.admin.certificate-templates.show',
        ], ['certificates.templates.view']);
        $assign([
            'api.admin.certificate-templates.store',
            'api.admin.certificate-templates.duplicate',
        ], ['certificates.templates.create']);
        $assign([
            'api.admin.certificate-templates.update',
            'api.admin.certificate-templates.upload',
        ], ['certificates.templates.update']);
        $assign('api.admin.certificate-templates.delete', ['certificates.templates.delete']);

        // Plans
        $assign('api.admin.plans.index', ['plans.view']);
        $assign('api.admin.plan.create', ['plans.create']);
        $assign('api.admin.plan.show', ['plans.show', 'plans.view']);
        $assign('api.admin.plan.update', ['plans.update']);
        $assign('api.admin.plan.delete', ['plans.delete']);

        // Projects & cooperations
        $assign([
            'api.admin.projects.index',
            'api.admin.projects.show',
            'api.admin.projects.update',
            'api.admin.projects.delete',
            'api.admin.cooperations.index',
            'api.admin.cooperations.show',
            'api.admin.cooperations.update',
            'api.admin.cooperations.delete',
        ], array_merge($usersView, $marketingAccess));

        // Paths
        $assign('api.admin.paths.index', ['paths.view']);
        $assign('api.admin.path.create', ['paths.create']);
        $assign('api.admin.path.edit', ['paths.view', 'paths.edit']);
        $assign('api.admin.path.update', ['paths.update', 'paths.edit']);
        $assign('api.admin.path.delete', ['paths.delete']);
        $assign('api.admin.path.delete.bulk', ['paths.delete']);
        $assign('api.admin.path.remove-file', ['paths.remove_file']);
        $assign('api.admin.path.search.courses', ['paths.search.courses']);
        $assign('api.admin.path.search.paths', ['paths.search.paths']);
        $assign('api.admin.path.upload-poster', ['paths.upload.poster']);
        $assign('api.admin.path.upload-icon', ['paths.upload.icon']);
        $assign('api.admin.path.upload-trailer', ['paths.upload.trailer']);

        // Users
        $assign([
            'api.admin.users',
            'api.admin.users.stats',
            'api.admin.user.search',
            'api.admin.user.base',
            'api.admin.user.details',
            'api.admin.user.security',
            'api.admin.user.comments',
            'api.admin.user.courses',
        ], array_merge($usersView, ['admin.search_user', 'articles.update.any', 'courses.assign_user', 'courses.assign_user.any']));
        $assign('api.admin.user.create', ['users.create']);
        $assign('api.admin.user.delete', ['users.delete']);
        $assign([
            'api.admin.user.upload-image',
            'api.admin.user.remove-provider',
            'api.admin.user.update-social',
            'api.admin.user.update-communications',
            'api.admin.user.update-info',
        ], ['users.update']);
        $assign('api.admin.user.update-password', ['users.reset_password']);
        $assign('api.admin.user.toggle-active', ['users.deactivate', 'users.reactivate']);
        $assign([
            'api.admin.user.access.add-permission',
            'api.admin.user.access.remove-permission',
        ], ['users.manage_permissions']);
        $assign([
            'api.admin.user.access.add-role',
            'api.admin.user.access.remove-role',
        ], ['users.manage_roles']);
        $assign('api.admin.user.toggle-superuser', ['users.manage_roles', 'security.access.manage']);
        $assign([
            'api.admin.user.remove-login-record',
            'api.admin.user.clear-login-history',
            'api.admin.user.terminate-session',
            'api.admin.user.terminate-all-session',
        ], ['users.sessions.view', 'users.sessions.terminate']);
        $assign([
            'api.admin.user.financial.summary',
            'api.admin.user.financial.payments',
            'api.admin.user.financial.wallets',
        ], array_merge($usersView, $paymentsView));
        $assign('api.admin.user.financial.assign-plan', ['users.update', 'plans.view']);
        $assign('api.admin.user.courses.assign', $coursesAssign);
        $assign('api.admin.user.courses.remove', $coursesAssign);

        // Missions (gamification)
        $assign([
            'api.admin.missions.stats',
            'api.admin.missions.index',
            'api.admin.missions.categories',
            'api.admin.missions.show',
            'api.admin.missions.participants',
        ], ['missions.view']);
        $assign('api.admin.missions.create', ['missions.create']);
        $assign('api.admin.missions.upload-icon', ['missions.create', 'missions.update']);
        $assign([
            'api.admin.missions.update',
            'api.admin.missions.toggle-active',
        ], ['missions.update']);
        $assign('api.admin.missions.delete', ['missions.delete']);
        $assign([
            'api.admin.mission-category.create',
            'api.admin.mission-category.update',
            'api.admin.mission-category.delete',
        ], ['mission-categories.manage']);

        // Levels & statuses
        $assign('api.admin.levels.index', ['levels.view']);
        $assign('api.admin.level.create', ['levels.create']);
        $assign('api.admin.level.update', ['levels.update']);
        $assign('api.admin.level.delete', ['levels.delete']);
        $assign('api.admin.statuses.index', ['statuses.view']);
        $assign('api.admin.status.create', ['statuses.create']);
        $assign('api.admin.status.update', ['statuses.update']);
        $assign('api.admin.status.delete', ['statuses.delete']);

        // Permissions & roles
        $assign([
            'api.admin.permissions.index',
            'api.admin.permissions.all',
        ], $securityView);
        $assign([
            'api.admin.permission.create',
            'api.admin.permission.update',
            'api.admin.permission.delete',
        ], $securityManage);
        $assign([
            'api.admin.roles.index',
            'api.admin.roles.all',
        ], $securityView);
        $assign([
            'api.admin.role.create',
            'api.admin.role.update',
            'api.admin.role.delete',
        ], $securityManage);

        // Categories
        $assign('api.admin.categories.index', ['categories.view']);
        $assign('api.admin.category.create', ['categories.create']);
        $assign('api.admin.category.edit', ['categories.edit']);
        $assign('api.admin.category.update', ['categories.edit']);
        $assign('api.admin.category.delete', ['categories.delete']);
        $assign('api.admin.category.upload-icon', ['categories.upload_icon']);

        // Comments
        $assign('api.admin.comments.index', $commentsView);
        $assign('api.admin.comments.stats', $commentsView);
        $assign('api.admin.comments.bulk', array_merge($commentsView, $commentsModerate, $commentsDelete));
        $assign('api.admin.comments.toggle-approval', array_merge($commentsView, $commentsModerate));
        $assign('api.admin.comments.send-reply', array_merge($commentsView, $commentsReply));
        $assign('api.admin.comments.update', array_merge($commentsView, $commentsModerate));
        $assign('api.admin.comments.delete', array_merge($commentsView, $commentsDelete, ['courses.comments.manage']));

        // Courses
        $assign(['api.admin.courses', 'api.admin.course.search'], $coursesList);
        $assign(['api.admin.course.create', 'api.admin.course.layouts.init'], ['courses.create']);
        $assign(['api.admin.course.edit', 'api.admin.course.update'], $coursesUpdate);
        $assign('api.admin.course.delete', $coursesDelete);
        $assign([
            'api.admin.course.remove-file',
            'api.admin.course.upload-poster',
            'api.admin.course.upload-attached-file',
        ], $coursesUpdate);
        $assign([
            'api.admin.course.base-details',
            'api.admin.course.details',
            'api.admin.course.overview',
            'api.admin.course.episodes',
            'api.admin.course.comments',
        ], $coursesView);
        $assign('api.admin.course.users', array_merge($coursesView, $coursesAssign));
        $assign('api.admin.course.users.assign', $coursesAssign);
        $assign('api.admin.course.users.remove', $coursesAssign);
        $assign('api.admin.course.episodes.reorder', $coursesReorder);
        $assign([
            'api.admin.course.section.create',
            'api.admin.course.section.edit',
            'api.admin.course.section.delete',
        ], $coursesUpdate);
        $assign([
            'api.admin.course.data-for-create-episode',
            'api.admin.course.create-null-episode',
            'api.admin.course.create-episode',
        ], $episodesCreate);
        $assign([
            'api.admin.course.update-episode',
            'api.admin.course.episode.edit',
            'api.admin.course.episode.details',
        ], $episodesEdit);
        $assign('api.admin.course.episode.delete', $episodesDelete);
        $assign([
            'api.admin.course.episode.upload-file',
            'api.admin.course.episode.status',
            'api.admin.course.episode.remove-file',
        ], array_merge($episodesEdit, $episodesManage));
        $assign('api.admin.video.upload', $videosUpload);
        $assign('api.admin.video.process', $videosProcess);

        // Discounts
        $assign('api.admin.discounts.index', ['discounts.view']);
        $assign('api.admin.discounts.show', ['discounts.show', 'discounts.view']);
        $assign('api.admin.discounts.create', ['discounts.create']);
        $assign('api.admin.discounts.update', ['discounts.update']);
        $assign('api.admin.discounts.toggle-status', ['discounts.toggle_status']);
        $assign('api.admin.discounts.delete', ['discounts.delete']);
        $assign('api.admin.discounts.search.eligibility', ['discounts.search_eligibility']);

        // FAQs (reuse article permissions — aligned with admin UI)
        $assign([
            'api.admin.faqs.index',
            'api.admin.faqs.categories',
        ], $articlesRead);
        $assign([
            'api.admin.faqs.category.create',
            'api.admin.faqs.category.update',
            'api.admin.faqs.category.delete',
            'api.admin.faqs.create',
            'api.admin.faqs.update',
            'api.admin.faqs.delete',
            'api.admin.faqs.reorder',
        ], array_merge($articlesRead, $articlesWrite));

        // Discuss
        $assign('api.admin.discuss.questions.index', $discussRead);
        $assign('api.admin.discuss.question.show', $discussRead);
        $assign('api.admin.discuss.question.create', ['discuss.create']);
        $assign('api.admin.discuss.question.update', ['discuss.update']);
        $assign('api.admin.discuss.question.delete', ['discuss.delete']);
        $assign('api.admin.discuss.questions.bulk', array_merge($discussWrite, ['discuss.update']));
        $assign([
            'api.admin.discuss.question.toggle-publish',
            'api.admin.discuss.question.set-best-answer',
            'api.admin.discuss.question.remove-best-answer',
        ], ['discuss.update']);
        $assign('api.admin.discuss.question.answers', $discussRead);
        $assign('api.admin.discuss.answer.create', ['discuss.answer.create']);
        $assign('api.admin.discuss.answer.update', ['discuss.update']);
        $assign('api.admin.discuss.answer.delete', ['discuss.delete']);
        $assign('api.admin.discuss.answer.toggle-pin', ['discuss.toggle_pin']);
        $assign('api.admin.discuss.answer.toggle-publish', ['discuss.update']);

        // Question categories
        $assign([
            'api.admin.question-categories.index',
            'api.admin.question-categories.tree',
            'api.admin.question-category.show',
            'api.admin.question-category.questions',
        ], $discussRead);
        $assign([
            'api.admin.question-category.create',
            'api.admin.question-category.update',
            'api.admin.question-category.delete',
            'api.admin.question-categories.bulk',
        ], $discussWrite);

        // Articles
        $articlesCreate = ['articles.create'];
        $articlesUpdate = ['articles.update', 'articles.update.own', 'articles.update.any'];
        $articlesDelete = ['articles.delete', 'articles.delete.own', 'articles.delete.any'];
        $articlesPublish = ['articles.publish', 'articles.publish.any', 'articles.publish.own', 'articles.unpublish'];
        $articlesStats = ['articles.stats.view', 'articles.list.any', 'articles.view.any'];
        $articlesCategoriesManage = ['articles.categories.manage', 'articles.update.any'];

        $assign([
            'api.admin.articles.index',
            'api.admin.article.show',
            'api.admin.article-categories.index',
            'api.admin.article-category.articles',
        ], $articlesRead);
        $assign([
            'api.admin.articles.stats',
            'api.admin.articles.analytics',
            'api.admin.article.analytics',
        ], $articlesStats);
        $assign('api.admin.article.create', $articlesCreate);
        $assign([
            'api.admin.article.update',
            'api.admin.article.restore',
            'api.admin.article.upload-cover',
            'api.admin.article.store-cover',
            'api.admin.article.remove-cover',
        ], $articlesUpdate);
        $assign([
            'api.admin.article.delete',
            'api.admin.article.force-delete',
        ], $articlesDelete);
        $assign('api.admin.article.toggle-publish', $articlesPublish);
        $assign('api.admin.articles.bulk', array_merge($articlesWrite, $articlesPublish));
        $assign([
            'api.admin.article-category.create',
            'api.admin.article-category.update',
            'api.admin.article-category.delete',
            'api.admin.article-categories.bulk',
        ], $articlesCategoriesManage);

        // Tags
        $tagsRead = ['tags.view'];
        $tagsWrite = ['tags.create', 'tags.update', 'tags.delete'];
        $assign([
            'api.admin.tags.stats',
            'api.admin.tags.index',
            'api.admin.tags.search',
            'api.admin.tag.show',
            'api.admin.tag.questions',
            'api.admin.tag.courses',
            'api.admin.tag.articles',
            'api.admin.tag.followers',
            'api.admin.tag.analytics',
        ], $tagsRead);
        $assign([
            'api.admin.tag.create',
            'api.admin.tag.update',
            'api.admin.tag.delete',
            'api.admin.tags.bulk-delete',
        ], $tagsWrite);
        $assign('api.admin.tag.merge', ['tags.merge']);

        // Reports & analytics
        $assign([
            'api.admin.reports.index',
            'api.admin.reports.stats',
            'api.admin.reports.delete-multiple',
            'api.admin.report.show',
            'api.admin.report.update-status',
            'api.admin.report.delete',
            'api.admin.report.deactivate-content',
            'api.admin.report.activate-content',
            'api.admin.report.delete-content',
        ], ['analytics.view']);
        $assign([
            'api.admin.sales-report.index',
            'api.admin.sales-report.stats',
            'api.admin.sales-report.analytics',
            'api.admin.sales-report.export',
        ], array_merge($paymentsView, ['payments.stats', 'analytics.view']));
        $assign([
            'api.admin.views.index',
            'api.admin.views.stats',
            'api.admin.views.ip-info',
            'api.admin.engagement.overview.stats',
            'api.admin.engagement.user-detail',
            'api.admin.likes.stats',
            'api.admin.likes.index',
            'api.admin.bookmarks.stats',
            'api.admin.bookmarks.index',
        ], ['analytics.view']);
        $assign([
            'api.admin.user-activity-report.index',
            'api.admin.user-activity-report.stats',
            'api.admin.user-activity-report.analytics',
            'api.admin.user-activity-report.export',
        ], array_merge($usersView, ['analytics.view']));

        // Notification management
        $assign([
            'api.admin.notification-management.event-groups.index',
            'api.admin.notification-management.event-groups.all',
            'api.admin.notification-management.event-group.show',
            'api.admin.notification-management.events.index',
            'api.admin.notification-management.event.show',
        ], $notificationsRead);
        $assign([
            'api.admin.notification-management.event-group.create',
            'api.admin.notification-management.event-group.update',
            'api.admin.notification-management.event.create',
            'api.admin.notification-management.event.update',
        ], $notificationsWrite);
        $assign([
            'api.admin.notification-management.event-group.delete',
            'api.admin.notification-management.event.delete',
        ], ['notifications.delete']);

        // Quizzes (LMS)
        $assign('api.admin.quizzes.index', ['quizzes.view']);
        $assign('api.admin.quizzes.store', ['quizzes.create']);
        $assign([
            'api.admin.quizzes.search.courses',
            'api.admin.quizzes.search.sections',
            'api.admin.quizzes.search.episodes',
            'api.admin.quizzes.search.questions',
        ], ['quizzes.view']);
        $assign('api.admin.quizzes.show', ['quizzes.view']);
        $assign('api.admin.quizzes.update', ['quizzes.update']);
        $assign('api.admin.quizzes.delete', ['quizzes.delete']);
        $assign([
            'api.admin.quizzes.reports.summary',
            'api.admin.quizzes.reports.passed',
            'api.admin.quizzes.reports.failed',
            'api.admin.quizzes.reports.review-queue',
            'api.admin.quizzes.for-course',
        ], ['quizzes.reports']);
        $assign([
            'api.admin.quizzes.answers.grade',
            'api.admin.quizzes.attempts.complete-review',
        ], ['quizzes.review']);
        $assign('api.admin.quiz-questions.index', ['quiz_questions.view']);
        $assign('api.admin.quiz-questions.store', ['quiz_questions.create']);
        $assign('api.admin.quiz-questions.categories', ['quiz_questions.view']);
        $assign('api.admin.quiz-questions.categories.store', ['quiz_questions.create']);
        $assign('api.admin.quiz-questions.tags', ['quiz_questions.view']);
        $assign('api.admin.quiz-questions.show', ['quiz_questions.view']);
        $assign('api.admin.quiz-questions.update', ['quiz_questions.update']);
        $assign('api.admin.quiz-questions.delete', ['quiz_questions.delete']);

        return $map;
    }
}
