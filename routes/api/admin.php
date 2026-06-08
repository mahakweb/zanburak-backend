<?php

use Illuminate\Support\Facades\Route;

// Admin routes (auth + administrator)
Route::middleware(['auth:sanctum'])->prefix('admin')->as('api.admin.')->group(function () {
    // Route access listing (routes + required permissions)
    Route::get('/routes', [\App\Http\Controllers\Api\Admin\Security\RouteAccessController::class, 'index'])->name('routes.index');
    Route::post('/route-permissions/add', [\App\Http\Controllers\Api\Admin\Security\RouteAccessController::class, 'addPermissions'])->name('routes.permissions.add');
    Route::post('/route-permissions/remove', [\App\Http\Controllers\Api\Admin\Security\RouteAccessController::class, 'removePermission'])->name('routes.permissions.remove');
    Route::post('/route-permissions/remove-all', [\App\Http\Controllers\Api\Admin\Security\RouteAccessController::class, 'removeAllPermissions'])->name('routes.permissions.remove-all');

    // Payments
    Route::prefix('payments')->as('payments.')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'payments'])->name('index');
        Route::get('/stats', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'paymentStats'])->name('stats');
        Route::get('/export', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'exportPayments'])->name('export');
        Route::get('/{uuid}/details', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'paymentDetails'])->name('details');
        Route::post('/{uuid}/update-status', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'updatePaymentStatus'])->name('update-status');
        Route::delete('/{uuid}/delete', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'deletePayment'])->name('delete');
        Route::post('/create', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'createPayment'])->name('create');
        Route::get('/search', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'search'])->name('search');
        Route::get('/users', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'getUsers'])->name('users');
        Route::get('/courses', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'getCourses'])->name('courses');
        Route::get('/plans', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'getPlans'])->name('plans');
        Route::get('/paths', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'getPaths'])->name('paths');
    });

    // Certificates
    Route::prefix('certificates')->as('certificates.')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\Admin\CertificateController::class, 'certificates'])->name('index');
        Route::get('/stats', [\App\Http\Controllers\Api\Admin\CertificateController::class, 'certificateStats'])->name('stats');
        Route::get('/export', [\App\Http\Controllers\Api\Admin\CertificateController::class, 'exportCertificates'])->name('export');
        Route::get('/{uuid}/details', [\App\Http\Controllers\Api\Admin\CertificateController::class, 'certificateDetails'])->name('details');
        Route::post('/create', [\App\Http\Controllers\Api\Admin\CertificateController::class, 'createCertificate'])->name('create');
        Route::post('/{uuid}/update', [\App\Http\Controllers\Api\Admin\CertificateController::class, 'updateCertificate'])->name('update');
        Route::post('/{uuid}/issue', [\App\Http\Controllers\Api\Admin\CertificateController::class, 'issueCertificate'])->name('issue');
        Route::delete('/{uuid}/delete', [\App\Http\Controllers\Api\Admin\CertificateController::class, 'deleteCertificate'])->name('delete');
        Route::get('/search', [\App\Http\Controllers\Api\Admin\CertificateController::class, 'search'])->name('search');
        Route::get('/users', [\App\Http\Controllers\Api\Admin\CertificateController::class, 'getUsers'])->name('users');
        Route::get('/courses', [\App\Http\Controllers\Api\Admin\CertificateController::class, 'getCourses'])->name('courses');
    });

    // Dashboard
    Route::get('/dashboard/stats', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/dashboard/unapproved-comments-count', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'unapprovedCommentsCount'])->name('dashboard.unapproved-comments-count');

    // System resources
    Route::prefix('system')->as('system.')->group(function () {
        Route::get('/resources', [\App\Http\Controllers\Api\Admin\System\SystemResourceController::class, 'index'])->name('resources.index');
        Route::get('/resources/history', [\App\Http\Controllers\Api\Admin\System\SystemResourceController::class, 'history'])->name('resources.history');
    });

    // Plans
    Route::post('/plans', [\App\Http\Controllers\Api\Admin\PlanController::class, 'plans'])->name('plans.index');
    Route::post('/plan/create', [\App\Http\Controllers\Api\Admin\PlanController::class, 'store'])->name('plan.create');
    Route::get('/plan/{plan}', [\App\Http\Controllers\Api\Admin\PlanController::class, 'show'])->name('plan.show');
    Route::post('/plan/{plan}/update', [\App\Http\Controllers\Api\Admin\PlanController::class, 'update'])->name('plan.update');
    Route::delete('/plan/{plan}', [\App\Http\Controllers\Api\Admin\PlanController::class, 'destroy'])->name('plan.delete');

    // Projects
    Route::post('/projects', [\App\Http\Controllers\Api\Admin\ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [\App\Http\Controllers\Api\Admin\ProjectController::class, 'show'])->name('projects.show');
    Route::post('/projects/{project}/update', [\App\Http\Controllers\Api\Admin\ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [\App\Http\Controllers\Api\Admin\ProjectController::class, 'destroy'])->name('projects.delete');

    // Cooperations
    Route::post('/cooperations', [\App\Http\Controllers\Api\Admin\CooperationController::class, 'index'])->name('cooperations.index');
    Route::get('/cooperation/{cooperation}', [\App\Http\Controllers\Api\Admin\CooperationController::class, 'show'])->name('cooperations.show');
    Route::post('/cooperation/{cooperation}/update', [\App\Http\Controllers\Api\Admin\CooperationController::class, 'update'])->name('cooperations.update');
    Route::delete('/cooperation/{cooperation}', [\App\Http\Controllers\Api\Admin\CooperationController::class, 'destroy'])->name('cooperations.delete');


    // Paths
    Route::post('/paths', [\App\Http\Controllers\Api\Admin\PathController::class, 'paths'])->name('paths.index');
    Route::post('/path/create', [\App\Http\Controllers\Api\Admin\PathController::class, 'store'])->name('path.create');
    Route::get('/path/{path:slug}/edit', [\App\Http\Controllers\Api\Admin\PathController::class, 'edit'])->name('path.edit');
    Route::patch('/path/{path:slug}/update', [\App\Http\Controllers\Api\Admin\PathController::class, 'update'])->name('path.update');
    Route::post('/path/delete', [\App\Http\Controllers\Api\Admin\PathController::class, 'deletePaths'])->name('path.delete.bulk');
    Route::delete('/path/{path:slug}', [\App\Http\Controllers\Api\Admin\PathController::class, 'destroy'])->name('path.delete');
    Route::post('/path/removeFile', [\App\Http\Controllers\Api\Admin\PathController::class, 'removeFile'])->name('path.remove-file');
    Route::get('/path/search/courses', [\App\Http\Controllers\Api\Admin\PathController::class, 'searchCourses'])->name('path.search.courses');
    Route::get('/path/search/paths', [\App\Http\Controllers\Api\Admin\PathController::class, 'searchPaths'])->name('path.search.paths');
    Route::post('/path/uploadPoster', [\App\Http\Controllers\Api\Admin\PathController::class, 'uploadPoster'])->name('path.upload-poster');
    Route::post('/path/uploadIcon', [\App\Http\Controllers\Api\Admin\PathController::class, 'uploadIcon'])->name('path.upload-icon');
    Route::post('/path/uploadTrailer', [\App\Http\Controllers\Api\Admin\PathController::class, 'uploadTrailer'])->name('path.upload-trailer');

    // Users
    Route::post('/users', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'users'])->name('users');
    Route::post('/searchUser', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'searchUser'])->name('user.search');
    Route::post('/user/create', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'create'])->name('user.create');
    Route::post('/user/delete', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'deleteUsers'])->name('user.delete');
    Route::post('/user/{userId}/upload-image', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'uploadImage'])->name('user.upload-image');
    Route::post('/user/{username}/base', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'base'])->name('user.base');
    Route::post('/user/{username}/details', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'details'])->name('user.details');
    Route::post('/user/{username}/toggleActive', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'toggleActive'])->name('user.toggle-active');
    Route::post('/user/{username}/removeProvider', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'removeProvider'])->name('user.remove-provider');
    Route::post('/user/{username}/updateSocial', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'updateSocial'])->name('user.update-social');
    Route::post('/user/{username}/updateCommunications', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'updateCommunications'])->name('user.update-communications');
    Route::post('/user/{username}/updateInfo', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'updateInfo'])->name('user.update-info');
    Route::post('/user/{username}/updatePassword', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'updatePassword'])->name('user.update-password');
    Route::post('/user/{username}/access/addPermission', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'addPermission'])->name('user.access.add-permission');
    Route::post('/user/{username}/access/removePermission', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'removePermission'])->name('user.access.remove-permission');
    Route::post('/user/{username}/access/addRole', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'addRole'])->name('user.access.add-role');
    Route::post('/user/{username}/access/removeRole', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'removeRole'])->name('user.access.remove-role');
    Route::post('/user/{username}/toggleSuperUser', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'toggleSuperUser'])->name('user.toggle-superuser');
    Route::post('/user/{username}/removeLoginRecord', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'removeLoginRecord'])->name('user.remove-login-record');
    Route::post('/user/{username}/clearLoginHistory', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'clearLoginHistory'])->name('user.clear-login-history');
    Route::post('/user/{username}/terminateSession', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'terminateSession'])->name('user.terminate-session');
    Route::post('/user/{username}/terminateAllSession', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'terminateAllSession'])->name('user.terminate-all-session');
    Route::post('/user/{username}/security', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'security'])->name('user.security');
    Route::post('/security/access', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'allAccess'])->name('security.access');

    // User financial
    Route::post('/user/{username}/financial/summary', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'financialSummary'])->name('user.financial.summary');
    Route::post('/user/{username}/financial/payments', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'financialPayments'])->name('user.financial.payments');
    Route::post('/user/{username}/financial/wallets', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'walletTransactions'])->name('user.financial.wallets');
    Route::post('/user/{username}/financial/assign-plan', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'assignPlan'])->name('user.financial.assign-plan');

    // User courses & comments
    Route::post('/user/{username}/courses', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'courses'])->name('user.courses');
    Route::post('/user/{username}/courses/assign', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'assignCourse'])->name('user.courses.assign');
    Route::post('/user/{username}/courses/remove', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'removeCourse'])->name('user.courses.remove');
    Route::post('/user/{username}/comments', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'comments'])->name('user.comments');

    // Course meta
    Route::post('/levels', [\App\Http\Controllers\Api\Admin\Course\LevelController::class, 'levels'])->name('levels.index');
    Route::post('/level/create', [\App\Http\Controllers\Api\Admin\Course\LevelController::class, 'store'])->name('level.create');
    Route::post('/level/{level}/update', [\App\Http\Controllers\Api\Admin\Course\LevelController::class, 'update'])->name('level.update');
    Route::delete('/level/{level}/delete', [\App\Http\Controllers\Api\Admin\Course\LevelController::class, 'delete'])->name('level.delete');
    // Missions (gamification)
    Route::prefix('missions')->as('missions.')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\Admin\Mission\MissionController::class, 'stats'])->name('stats');
        Route::post('/list', [\App\Http\Controllers\Api\Admin\Mission\MissionController::class, 'index'])->name('index');
        Route::get('/categories', [\App\Http\Controllers\Api\Admin\Mission\MissionController::class, 'categories'])->name('categories');
        Route::post('/create', [\App\Http\Controllers\Api\Admin\Mission\MissionController::class, 'store'])->name('create');
        Route::post('/upload-icon', [\App\Http\Controllers\Api\Admin\Mission\MissionController::class, 'uploadIcon'])->name('upload-icon');
        Route::get('/{mission}', [\App\Http\Controllers\Api\Admin\Mission\MissionController::class, 'show'])->name('show');
        Route::get('/{mission}/participants', [\App\Http\Controllers\Api\Admin\Mission\MissionController::class, 'participants'])->name('participants');
        Route::post('/{mission}/toggle-active', [\App\Http\Controllers\Api\Admin\Mission\MissionController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{mission}/update', [\App\Http\Controllers\Api\Admin\Mission\MissionController::class, 'update'])->name('update');
        Route::delete('/{mission}', [\App\Http\Controllers\Api\Admin\Mission\MissionController::class, 'destroy'])->name('delete');
    });
    Route::post('/mission-category/create', [\App\Http\Controllers\Api\Admin\Mission\MissionCategoryController::class, 'store'])->name('mission-category.create');
    Route::post('/mission-category/{category}/update', [\App\Http\Controllers\Api\Admin\Mission\MissionCategoryController::class, 'update'])->name('mission-category.update');
    Route::delete('/mission-category/{category}', [\App\Http\Controllers\Api\Admin\Mission\MissionCategoryController::class, 'destroy'])->name('mission-category.delete');

    Route::post('/statuses', [\App\Http\Controllers\Api\Admin\Course\StatusController::class, 'statuses'])->name('statuses.index');
    Route::post('/status/create', [\App\Http\Controllers\Api\Admin\Course\StatusController::class, 'store'])->name('status.create');
    Route::post('/status/{status}/update', [\App\Http\Controllers\Api\Admin\Course\StatusController::class, 'update'])->name('status.update');
    Route::delete('/status/{status}/delete', [\App\Http\Controllers\Api\Admin\Course\StatusController::class, 'delete'])->name('status.delete');
    
    // Permissions
    Route::post('/permissions', [\App\Http\Controllers\Api\Admin\Permission\PermissionController::class, 'permissions'])->name('permissions.index');
    Route::post('/permission/create', [\App\Http\Controllers\Api\Admin\Permission\PermissionController::class, 'store'])->name('permission.create');
    Route::get('/permission/{permission}/details', [\App\Http\Controllers\Api\Admin\Permission\PermissionController::class, 'details'])->name('permission.details');
    Route::post('/permission/{permission}/update', [\App\Http\Controllers\Api\Admin\Permission\PermissionController::class, 'update'])->name('permission.update');
    Route::delete('/permission/{permission}/delete', [\App\Http\Controllers\Api\Admin\Permission\PermissionController::class, 'delete'])->name('permission.delete');
    
    // Roles
    Route::post('/roles', [\App\Http\Controllers\Api\Admin\Role\RoleController::class, 'roles'])->name('roles.index');
    Route::get('/permissions/all', [\App\Http\Controllers\Api\Admin\Role\RoleController::class, 'allPermissions'])->name('permissions.all');
    Route::get('/roles/all', [\App\Http\Controllers\Api\Admin\Role\RoleController::class, 'allRoles'])->name('roles.all');
    Route::post('/role/create', [\App\Http\Controllers\Api\Admin\Role\RoleController::class, 'store'])->name('role.create');
    Route::get('/role/{role}/details', [\App\Http\Controllers\Api\Admin\Role\RoleController::class, 'details'])->name('role.details');
    Route::post('/role/{role}/update', [\App\Http\Controllers\Api\Admin\Role\RoleController::class, 'update'])->name('role.update');
    Route::delete('/role/{role}/delete', [\App\Http\Controllers\Api\Admin\Role\RoleController::class, 'delete'])->name('role.delete');
    Route::post('/categories', [\App\Http\Controllers\Api\Admin\Course\CategoryController::class, 'categories'])->name('categories.index');
    Route::post('/category/create', [\App\Http\Controllers\Api\Admin\Course\CategoryController::class, 'store'])->name('category.create');
    Route::post('/category/edit', [\App\Http\Controllers\Api\Admin\Course\CategoryController::class, 'edit'])->name('category.edit');
    Route::post('/category/update', [\App\Http\Controllers\Api\Admin\Course\CategoryController::class, 'update'])->name('category.update');
    Route::post('/category/delete', [\App\Http\Controllers\Api\Admin\Course\CategoryController::class, 'deleteCategories'])->name('category.delete');
    Route::post('/category/uploadIcon', [\App\Http\Controllers\Api\Admin\Course\CategoryController::class, 'uploadIcon'])->name('category.upload-icon');

    // Admin Comments
    Route::post('/comments', [\App\Http\Controllers\Api\Admin\Comment\CommentController::class, 'index'])->name('comments.index');
    Route::post('/comments/toggle-approval', [\App\Http\Controllers\Api\Admin\Comment\CommentController::class, 'toggleApproval'])->name('comments.toggle-approval');
    Route::post('/comments/send-reply', [\App\Http\Controllers\Api\Admin\Comment\CommentController::class, 'sendReply'])->name('comments.send-reply');
    Route::post('/comments/update', [\App\Http\Controllers\Api\Admin\Comment\CommentController::class, 'update'])->name('comments.update');
    Route::post('/comments/delete', [\App\Http\Controllers\Api\Admin\Comment\CommentController::class, 'delete'])->name('comments.delete');

    // Course management
    Route::post('/searchCourse', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'searchCourse'])->name('course.search');
    Route::post('/course/{courseSlug}/dataForCreateEpisode', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'createEpisodeData'])->name('course.data-for-create-episode');
    Route::post('/course/{courseSlug}/createNullEpisode', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'createNullEpisode'])->name('course.create-null-episode');
    Route::post('/course/{courseSlug}/createEpisode', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'updateEpisode'])->name('course.create-episode');
    Route::post('/course/{courseSlug}/updateEpisode', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'updateEpisode'])->name('course.update-episode');
    Route::post('/course/{courseSlug}/section/{sectionSlug}/episode/{episodeSlug}/edit', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'getEpisodeForEdit'])->name('course.episode.edit');
    Route::post('/course/{courseSlug}/episode/{episodeSlug}/details', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'getEpisodeDetails'])->name('course.episode.details');
    Route::delete('/course/{courseSlug}/episode/{episodeSlug}/delete', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'deleteEpisode'])->name('course.episode.delete');
    Route::post('/course/{courseSlug}/episode/uploadAttachedFile', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'uploadAttachedFile'])->name('course.episode.upload-file');
    Route::post('/course/{courseSlug}/episode/status', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'episodeStatus'])->name('course.episode.status');
    Route::post('/course/{courseSlug}/episode/removeFile', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'removeFile'])->name('course.episode.remove-file');
    Route::post('/course/layouts/getInitData', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'getInitData'])->name('course.layouts.init');
    Route::post('/courses', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'courses'])->name('courses');
    Route::post('/course/{courseSlug}/section/create', [\App\Http\Controllers\Api\Admin\Course\SectionController::class, 'createSection'])->name('course.section.create');
    Route::post('/course/{courseSlug}/section/{section}/edit', [\App\Http\Controllers\Api\Admin\Course\SectionController::class, 'editSection'])->name('course.section.edit');
    Route::delete('/course/{courseSlug}/section/{section}/delete', [\App\Http\Controllers\Api\Admin\Course\SectionController::class, 'deleteSection'])->name('course.section.delete');
    Route::post('/course/{courseSlug}/baseDetails', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'baseDetails'])->name('course.base-details');
    Route::post('/course/{courseSlug}/details', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'getCourseDetails'])->name('course.details');
    Route::post('/course/{courseSlug}/overview', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'overview'])->name('course.overview');
    Route::post('/course/{courseSlug}/users', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'users'])->name('course.users');
    Route::post('/course/{courseSlug}/users/assign', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'assignToUser'])->name('course.users.assign');
    Route::post('/course/{courseSlug}/episodes', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'episodes'])->name('course.episodes');
    Route::post('/course/{courseSlug}/episodes/reorder', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'reorderEpisodes'])->name('course.episodes.reorder');
    Route::post('/course/{courseSlug}/comments', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'comments'])->name('course.comments');
    Route::post('/course/delete', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'deleteCourses'])->name('course.delete');
    Route::post('/course/create', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'store'])->name('course.create');
    Route::post('/course/edit', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'edit'])->name('course.edit');
    Route::post('/course/update', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'update'])->name('course.update');
    Route::post('/course/removeFile', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'removeFile'])->name('course.remove-file');
    Route::post('/course/uploadPoster', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'uploadPoster'])->name('course.upload-poster');
    Route::post('/course/uploadAttachedFile', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'uploadAttachedFile'])->name('course.upload-attached-file');
    Route::post('/video/upload', [\App\Http\Controllers\Api\Admin\Course\VideoController::class, 'upload'])->name('video.upload');
    Route::post('/video/process/{video}', [\App\Http\Controllers\Api\Admin\Course\VideoController::class, 'process'])->name('video.process');

    // Discounts
    Route::get('/discounts', [\App\Http\Controllers\Api\Admin\Discount\DiscountController::class, 'index'])->name('discounts.index');
    Route::get('/discount/{id}', [\App\Http\Controllers\Api\Admin\Discount\DiscountController::class, 'show'])->name('discounts.show');
    Route::post('/discount/create', [\App\Http\Controllers\Api\Admin\Discount\DiscountController::class, 'store'])->name('discounts.create');
    Route::put('/discount/{id}', [\App\Http\Controllers\Api\Admin\Discount\DiscountController::class, 'update'])->name('discounts.update');
    Route::post('/discount/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\Discount\DiscountController::class, 'toggleStatus'])->name('discounts.toggle-status');
    Route::delete('/discount/{id}', [\App\Http\Controllers\Api\Admin\Discount\DiscountController::class, 'destroy'])->name('discounts.delete');
    Route::get('/discount/search/eligibility', [\App\Http\Controllers\Api\Admin\Discount\DiscountController::class, 'search'])->name('discounts.search.eligibility');

    // FAQs
    Route::get('/faqs', [\App\Http\Controllers\Api\Admin\FaqController::class, 'index'])->name('faqs.index');
    Route::post('/faqs/categories', [\App\Http\Controllers\Api\Admin\FaqController::class, 'categories'])->name('faqs.categories');
    Route::post('/faqs/category/create', [\App\Http\Controllers\Api\Admin\FaqController::class, 'createCategory'])->name('faqs.category.create');
    Route::post('/faqs/category/{category}/update', [\App\Http\Controllers\Api\Admin\FaqController::class, 'updateCategory'])->name('faqs.category.update');
    Route::delete('/faqs/category/{category}', [\App\Http\Controllers\Api\Admin\FaqController::class, 'deleteCategory'])->name('faqs.category.delete');
    Route::post('/faqs/create', [\App\Http\Controllers\Api\Admin\FaqController::class, 'createFaq'])->name('faqs.create');
    Route::post('/faqs/{faq}/update', [\App\Http\Controllers\Api\Admin\FaqController::class, 'updateFaq'])->name('faqs.update');
    Route::delete('/faqs/{faq}', [\App\Http\Controllers\Api\Admin\FaqController::class, 'deleteFaq'])->name('faqs.delete');
    Route::post('/faqs/reorder', [\App\Http\Controllers\Api\Admin\FaqController::class, 'reorderFaqs'])->name('faqs.reorder');

    // Discuss (Questions & Answers)
    Route::post('/discuss/questions', [\App\Http\Controllers\Api\Admin\DiscussController::class, 'index'])->name('discuss.questions.index');
    Route::get('/discuss/question/{question}', [\App\Http\Controllers\Api\Admin\DiscussController::class, 'show'])->name('discuss.question.show');
    Route::post('/discuss/question/create', [\App\Http\Controllers\Api\Admin\DiscussController::class, 'create'])->name('discuss.question.create');
    Route::post('/discuss/question/{question}/update', [\App\Http\Controllers\Api\Admin\DiscussController::class, 'update'])->name('discuss.question.update');
    Route::delete('/discuss/question/{question}', [\App\Http\Controllers\Api\Admin\DiscussController::class, 'delete'])->name('discuss.question.delete');
    Route::post('/discuss/question/{question}/toggle-publish', [\App\Http\Controllers\Api\Admin\DiscussController::class, 'togglePublish'])->name('discuss.question.toggle-publish');
    Route::post('/discuss/question/{question}/set-best-answer', [\App\Http\Controllers\Api\Admin\DiscussController::class, 'setBestAnswer'])->name('discuss.question.set-best-answer');
    Route::post('/discuss/question/{question}/remove-best-answer', [\App\Http\Controllers\Api\Admin\DiscussController::class, 'removeBestAnswer'])->name('discuss.question.remove-best-answer');
    Route::post('/discuss/question/{question}/answers', [\App\Http\Controllers\Api\Admin\DiscussController::class, 'getAnswers'])->name('discuss.question.answers');
    Route::post('/discuss/question/{question}/answer/create', [\App\Http\Controllers\Api\Admin\DiscussController::class, 'createAnswer'])->name('discuss.answer.create');
    Route::post('/discuss/answer/{answer}/update', [\App\Http\Controllers\Api\Admin\DiscussController::class, 'updateAnswer'])->name('discuss.answer.update');
    Route::delete('/discuss/answer/{answer}', [\App\Http\Controllers\Api\Admin\DiscussController::class, 'deleteAnswer'])->name('discuss.answer.delete');
    Route::post('/discuss/answer/{answer}/toggle-pin', [\App\Http\Controllers\Api\Admin\DiscussController::class, 'togglePinAnswer'])->name('discuss.answer.toggle-pin');
    Route::post('/discuss/answer/{answer}/toggle-publish', [\App\Http\Controllers\Api\Admin\DiscussController::class, 'togglePublishAnswer'])->name('discuss.answer.toggle-publish');

    // Question Categories
    Route::post('/question-categories', [\App\Http\Controllers\Api\Admin\QuestionCategoryController::class, 'index'])->name('question-categories.index');
    Route::get('/question-categories/tree', [\App\Http\Controllers\Api\Admin\QuestionCategoryController::class, 'tree'])->name('question-categories.tree');
    Route::get('/question-category/{category}', [\App\Http\Controllers\Api\Admin\QuestionCategoryController::class, 'show'])->name('question-category.show');
    Route::post('/question-category/create', [\App\Http\Controllers\Api\Admin\QuestionCategoryController::class, 'create'])->name('question-category.create');
    Route::post('/question-category/{category}/update', [\App\Http\Controllers\Api\Admin\QuestionCategoryController::class, 'update'])->name('question-category.update');
    Route::delete('/question-category/{category}', [\App\Http\Controllers\Api\Admin\QuestionCategoryController::class, 'delete'])->name('question-category.delete');

    // Reports
    Route::post('/reports', [\App\Http\Controllers\Api\Admin\ReportController::class, 'reports'])->name('reports.index');
    Route::get('/reports/stats', [\App\Http\Controllers\Api\Admin\ReportController::class, 'stats'])->name('reports.stats');
    Route::get('/report/{id}', [\App\Http\Controllers\Api\Admin\ReportController::class, 'show'])->name('report.show');
    Route::post('/report/{id}/update-status', [\App\Http\Controllers\Api\Admin\ReportController::class, 'updateStatus'])->name('report.update-status');
    Route::delete('/report/{id}', [\App\Http\Controllers\Api\Admin\ReportController::class, 'delete'])->name('report.delete');
    Route::post('/reports/delete-multiple', [\App\Http\Controllers\Api\Admin\ReportController::class, 'deleteMultiple'])->name('reports.delete-multiple');
    Route::post('/report/{id}/deactivate-content', [\App\Http\Controllers\Api\Admin\ReportController::class, 'deactivateContent'])->name('report.deactivate-content');
    Route::post('/report/{id}/activate-content', [\App\Http\Controllers\Api\Admin\ReportController::class, 'activateContent'])->name('report.activate-content');
    Route::post('/report/{id}/delete-content', [\App\Http\Controllers\Api\Admin\ReportController::class, 'deleteContent'])->name('report.delete-content');

    // Sales Reports
    Route::post('/sales-report', [\App\Http\Controllers\Api\Admin\SalesReportController::class, 'salesReport'])->name('sales-report.index');
    Route::get('/sales-report/stats', [\App\Http\Controllers\Api\Admin\SalesReportController::class, 'stats'])->name('sales-report.stats');
    Route::get('/sales-report/analytics', [\App\Http\Controllers\Api\Admin\SalesReportController::class, 'analytics'])->name('sales-report.analytics');
    Route::get('/sales-report/export', [\App\Http\Controllers\Api\Admin\SalesReportController::class, 'export'])->name('sales-report.export');

    // Content Views Analytics
    Route::post('/views', [\App\Http\Controllers\Api\Admin\ViewController::class, 'index'])->name('views.index');
    Route::get('/views/stats', [\App\Http\Controllers\Api\Admin\ViewController::class, 'stats'])->name('views.stats');
    Route::get('/views/ip-info', [\App\Http\Controllers\Api\Admin\ViewController::class, 'ipInfo'])->name('views.ip-info');

    // Likes & Bookmarks Analytics
    Route::get('/engagement/overview/stats', [\App\Http\Controllers\Api\Admin\EngagementController::class, 'overviewStats'])->name('engagement.overview.stats');
    Route::get('/engagement/user-detail', [\App\Http\Controllers\Api\Admin\EngagementController::class, 'userDetail'])->name('engagement.user-detail');
    Route::get('/likes/stats', [\App\Http\Controllers\Api\Admin\EngagementController::class, 'likeStats'])->name('likes.stats');
    Route::post('/likes', [\App\Http\Controllers\Api\Admin\EngagementController::class, 'likeIndex'])->name('likes.index');
    Route::get('/bookmarks/stats', [\App\Http\Controllers\Api\Admin\EngagementController::class, 'bookmarkStats'])->name('bookmarks.stats');
    Route::post('/bookmarks', [\App\Http\Controllers\Api\Admin\EngagementController::class, 'bookmarkIndex'])->name('bookmarks.index');

    // User Activity Reports
    Route::post('/user-activity-report', [\App\Http\Controllers\Api\Admin\UserActivityReportController::class, 'activities'])->name('user-activity-report.index');
    Route::get('/user-activity-report/stats', [\App\Http\Controllers\Api\Admin\UserActivityReportController::class, 'stats'])->name('user-activity-report.stats');
    Route::get('/user-activity-report/analytics', [\App\Http\Controllers\Api\Admin\UserActivityReportController::class, 'analytics'])->name('user-activity-report.analytics');
    Route::get('/user-activity-report/export', [\App\Http\Controllers\Api\Admin\UserActivityReportController::class, 'export'])->name('user-activity-report.export');

    // Notification Management
    Route::prefix('notification-management')->as('notification-management.')->group(function () {
        // Event Groups
        Route::post('/event-groups', [\App\Http\Controllers\Api\Admin\NotificationManagementController::class, 'eventGroups'])->name('event-groups.index');
        Route::get('/event-groups/all', [\App\Http\Controllers\Api\Admin\NotificationManagementController::class, 'getAllEventGroups'])->name('event-groups.all');
        Route::post('/event-group/create', [\App\Http\Controllers\Api\Admin\NotificationManagementController::class, 'createEventGroup'])->name('event-group.create');
        Route::get('/event-group/{eventGroup}', [\App\Http\Controllers\Api\Admin\NotificationManagementController::class, 'getEventGroup'])->name('event-group.show');
        Route::post('/event-group/{eventGroup}/update', [\App\Http\Controllers\Api\Admin\NotificationManagementController::class, 'updateEventGroup'])->name('event-group.update');
        Route::delete('/event-group/{eventGroup}', [\App\Http\Controllers\Api\Admin\NotificationManagementController::class, 'deleteEventGroup'])->name('event-group.delete');

        // Events
        Route::post('/events', [\App\Http\Controllers\Api\Admin\NotificationManagementController::class, 'events'])->name('events.index');
        Route::post('/event/create', [\App\Http\Controllers\Api\Admin\NotificationManagementController::class, 'createEvent'])->name('event.create');
        Route::get('/event/{event}', [\App\Http\Controllers\Api\Admin\NotificationManagementController::class, 'getEvent'])->name('event.show');
        Route::post('/event/{event}/update', [\App\Http\Controllers\Api\Admin\NotificationManagementController::class, 'updateEvent'])->name('event.update');
        Route::delete('/event/{event}', [\App\Http\Controllers\Api\Admin\NotificationManagementController::class, 'deleteEvent'])->name('event.delete');
    });
});


