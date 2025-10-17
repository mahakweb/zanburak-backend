<?php

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        $userData = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'username' => $user->username,
            'profile_pic' => $user->profile_pic,
            'cover_pic' => $user->cover_pic,
            'wallet_balance' => $user->wallet_balance,
            'last_seen' => $user->last_seen,
            'active' => $user->active,
            'is_superuser' => $user->is_superuser,
            'permissions' => $user->permissions->pluck('name'),
            'roles' => $user->roles->pluck('name'),
        ];
        return $userData;
    });

    Route::post('auth/logout', [\App\Http\Controllers\Api\Auth\LoginController::class, 'logout']);
});

Route::post('get-plans-list', [\App\Http\Controllers\Api\IndexController::class, 'plansList']);


Route::post('auth/login', [\App\Http\Controllers\Api\Auth\LoginController::class, 'login']);
Route::post('auth/register', [\App\Http\Controllers\Api\Auth\RegisterController::class, 'register']);

Route::post('auth/password/email', [\App\Http\Controllers\Api\Auth\ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('auth/password/reset', [\App\Http\Controllers\Api\Auth\ResetPasswordController::class, 'reset']);

Route::middleware('auth:sanctum')->post('/email/resend', [\App\Http\Controllers\Api\Auth\EmailVerificationController::class, 'resend']);

Route::middleware('web')->get('/email/verify/{id}/{hash}', [\App\Http\Controllers\Api\Auth\EmailVerificationController::class, 'verify'])->name('verification.verify');


// login with provider
Route::get('oauth/{driver}', [\App\Http\Controllers\Api\Auth\OAuthController::class, 'redirect']);
Route::get('oauth/{driver}/callback', [\App\Http\Controllers\Api\Auth\OAuthController::class, 'callback']);
// start profile routes

Route::post('/@{username}', [\App\Http\Controllers\Api\Profile\ProfileController::class, 'index']);
Route::post('/@{username}/about', [\App\Http\Controllers\Api\Profile\ProfileController::class, 'about']);
Route::post('/@{username}/discuss', [\App\Http\Controllers\Api\Profile\ProfileController::class, 'discuss']);
Route::post('/@{username}/discuss/answers', [\App\Http\Controllers\Api\Profile\ProfileController::class, 'answers']);

// end profile routes

// search routes
Route::post('/search', [\App\Http\Controllers\Api\IndexController::class, 'search']);
// search routes

// editor upload routes

Route::middleware('auth:sanctum')->post('/editor/uploadImage', [\App\Http\Controllers\Api\IndexController::class, 'editorUploadImage']);

// editor upload routes

// request project routes

Route::middleware('auth:sanctum')->post('/request-project/check', [\App\Http\Controllers\Api\IndexController::class, 'requestProjectCheck']);
Route::middleware('auth:sanctum')->post('/request-project', [\App\Http\Controllers\Api\IndexController::class, 'requestProject']);
Route::middleware('auth:sanctum')->post('/request-project/upload-file', [\App\Http\Controllers\Api\IndexController::class, 'dropzoneUpload']);

// request project routes



// start certificate routes

Route::get('/certificate/{uuid}', [\App\Http\Controllers\Api\CertificateController::class, 'index']);

// end certificate routes

// start bookmark routes
Route::middleware('auth:sanctum')->post('/toggleBookmark', [\App\Http\Controllers\Api\BookmarkController::class, 'toggleBookmark']);
// end bookmark routes

// start like routes
Route::middleware('auth:sanctum')->post('/toggleLike', [\App\Http\Controllers\Api\LikeController::class, 'toggleLike']);
// end like routes

// start follow routes
Route::middleware('auth:sanctum')->post('/toggleFollow', [\App\Http\Controllers\Api\FollowController::class, 'toggleFollow']);
// end follow routes


// start report routes
Route::middleware('auth:sanctum')->post('/sendReport', [\App\Http\Controllers\Api\ReportController::class, 'sendReport']);
// end report routes

// start rating routes
Route::middleware('auth:sanctum')->post('/setRate', [\App\Http\Controllers\Api\RatingController::class, 'setRate']);
// end rating routes


// start discuss routes

Route::post('/discuss', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'discussions']);
Route::middleware('auth:sanctum')->get('/discuss/{questionSlug}/edit', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'getQuestionForEdit']);
Route::middleware('auth:sanctum')->put('/discuss/{questionSlug}/edit', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'updateQuestion']);
Route::middleware('auth:sanctum')->put('/discuss/{questionSlug}/editAnswer', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'updateAnswer']);
Route::middleware('auth:sanctum')->put('/discuss/{questionSlug}/setBestAnswer', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'setBestAnswer']);
Route::middleware('auth:sanctum')->delete('/discuss/{questionSlug}/delete', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'deleteQuestion']);
Route::middleware('auth:sanctum')->delete('/discuss/{questionSlug}/deleteAnswer', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'deleteAnswer']);
Route::post('/discuss/{questionSlug}', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'discuss']);
Route::post('/discuss/{questionSlug}/answers', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'answers']);
Route::middleware('auth:sanctum')->post('/discuss/{questionSlug}/newAnswer', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'newAnswer']);
Route::middleware('auth:sanctum')->post('/discuss/layouts/like-dislike', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'likeDislike']);
Route::middleware('auth:sanctum')->post('/discuss/layouts/toggle-pin', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'togglePin']);
Route::post('/discuss/layouts/getInitData', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'getInitData']);
Route::middleware('auth:sanctum')->post('/discuss/layouts/create', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'create']);
Route::post('/discuss/layouts/similarQuestions', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'similarQuestions']);

// end discuss routes


// start index page route
Route::get('/index/latestCourses', [\App\Http\Controllers\Api\IndexController::class, 'latestCourses']);
// end index page route

// start paths route
Route::get('/paths', [\App\Http\Controllers\Api\IndexController::class, 'paths']);
Route::get('/path/{pathSlug}', [\App\Http\Controllers\Api\IndexController::class, 'getPath']);
// end paths route

// start comments route
Route::get('/comments', [\App\Http\Controllers\Api\CommentController::class, 'index']);
Route::middleware('auth:sanctum')->post('/comments/store', [\App\Http\Controllers\Api\CommentController::class, 'store']);
// end comments route

Route::get('/courses', [\App\Http\Controllers\Api\Course\CourseController::class, 'courses']);
Route::get('/courses-page-filter', [\App\Http\Controllers\Api\Course\CourseController::class, 'filters']);
Route::get('/course/{courseSlug}', [\App\Http\Controllers\Api\Course\CourseController::class, 'getCourse']);

Route::get('/course/{courseSlug}/episode/{episodeSlug}', [\App\Http\Controllers\Api\Course\EpisodeController::class, 'getEpisode']);

// Route::middleware('auth:sanctum')->get('/cart', [\App\Http\Controllers\Api\CartController::class, 'cartDetails']);
// Route::middleware('auth:sanctum')->post('/cart/add/{course}', [\App\Http\Controllers\Api\CartController::class, 'addToCart']);
// Route::middleware('auth:sanctum')->delete('/cart/delete/{course}', [\App\Http\Controllers\Api\CartController::class, 'deleteFromCart']);
// Route::middleware('auth:sanctum')->post('/cart/payment', [\App\Http\Controllers\Api\CartController::class, 'payment']);
Route::get('/cart/payment/callback', [\App\Http\Controllers\Api\CartController::class, 'callback'])->name('api.payment-callback');

Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\CartController::class, 'index']);
    Route::post('/add', [\App\Http\Controllers\Api\CartController::class, 'add']);
    Route::delete('/remove/{id}', [\App\Http\Controllers\Api\CartController::class, 'remove']);
    Route::delete('/clear', [\App\Http\Controllers\Api\CartController::class, 'clear']);
    Route::post('/discount/apply', [\App\Http\Controllers\Api\DiscountController::class, 'applyDiscount']);
    Route::post('/discount/remove', [\App\Http\Controllers\Api\DiscountController::class, 'removeDiscount']);
    Route::post('/cart/validate-discount', [\App\Http\Controllers\Api\DiscountController::class, 'validateDiscount']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/cart/pay', [\App\Http\Controllers\Api\PaymentController::class, 'store']);        
    Route::post('/payment/retry/{uuid}', [\App\Http\Controllers\Api\PaymentController::class, 'retry']);  
});
Route::get('/payment/verify/{uuid}', [\App\Http\Controllers\Api\PaymentController::class, 'callback'])->name('api.payment.callback');

// Get detail of receipt
Route::middleware('auth:sanctum')->post('/payment/receipt/detail', [\App\Http\Controllers\Api\PaymentController::class, 'receipt']);



// Panel routes
Route::middleware('auth:sanctum')->post('/panel/index', [\App\Http\Controllers\Api\PanelController::class, 'index']);
Route::middleware('auth:sanctum')->post('/panel/financial', [\App\Http\Controllers\Api\PanelController::class, 'financial']);
Route::middleware('auth:sanctum')->post('/panel/increase-wallet-balance', [\App\Http\Controllers\Api\PanelController::class, 'increamentWalletAmount']);
Route::middleware('auth:sanctum')->post('/panel/courses', [\App\Http\Controllers\Api\PanelController::class, 'courses']);
Route::middleware('auth:sanctum')->post('/panel/missions', [\App\Http\Controllers\Api\PanelController::class, 'missions']);
Route::middleware('auth:sanctum')->post('/panel/questions', [\App\Http\Controllers\Api\PanelController::class, 'questions']);
Route::middleware('auth:sanctum')->post('/panel/certifications', [\App\Http\Controllers\Api\PanelController::class, 'certifications']);
Route::middleware('auth:sanctum')->post('/panel/followed', [\App\Http\Controllers\Api\PanelController::class, 'followed']);
Route::middleware('auth:sanctum')->post('/panel/comments', [\App\Http\Controllers\Api\PanelController::class, 'comments']);
Route::middleware('auth:sanctum')->post('/panel/notifications', [\App\Http\Controllers\Api\Panel\NotificationController::class, 'notifications']);
Route::middleware('auth:sanctum')->post('/panel/notification/details', [\App\Http\Controllers\Api\Panel\NotificationController::class, 'notificationDetails']);
Route::middleware('auth:sanctum')->post('/panel/notification/unread', [\App\Http\Controllers\Api\Panel\NotificationController::class, 'unreadNotifications']);
Route::middleware('auth:sanctum')->post('/panel/notification/delete', [\App\Http\Controllers\Api\Panel\NotificationController::class, 'deleteNotification']);
Route::middleware('auth:sanctum')->post('/panel/vip', [\App\Http\Controllers\Api\Panel\VipController::class, 'index']);
Route::middleware('auth:sanctum')->post('/panel/vip/get-plans-list', [\App\Http\Controllers\Api\Panel\VipController::class, 'getPlansList']);
Route::middleware('auth:sanctum')->post('/panel/vip/purchase', [\App\Http\Controllers\Api\Panel\VipController::class, 'purchase']);
Route::get('/panel/vip/purchase/callback', [\App\Http\Controllers\Api\Panel\VipController::class, 'vipCallback'])->name('api.vip-callback');
Route::middleware('auth:sanctum')->post('/panel/profile/get-mobile-info', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'getMobileInfo']);
Route::middleware('auth:sanctum')->post('/panel/profile/send-otp', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'sendOtp']);
Route::middleware('auth:sanctum')->post('/panel/profile/verify-otp', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'verifyOtp']);
Route::middleware('auth:sanctum')->post('/panel/profile/change-password', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'changePassword']);
Route::middleware('auth:sanctum')->post('/panel/profile/information-management', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'getUserPreferences']);
Route::middleware('auth:sanctum')->post('/panel/profile/update-preference', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'updateSinglePreference']);
Route::middleware('auth:sanctum')->post('/panel/profile/userData', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'getUserData']);
Route::middleware('auth:sanctum')->post('/panel/profile/update', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'updateProfile']);
Route::middleware('auth:sanctum')->post('/panel/profile/change-profile-pic', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'changeProfilePic']);
Route::middleware('auth:sanctum')->post('/panel/profile/change-cover-pic', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'changeCoverPic']);
Route::middleware('auth:sanctum')->post('/panel/access-tokens', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'accessTokens']);
Route::middleware('auth:sanctum')->post('/panel/access-tokens/terminate', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'terminateSession']);
Route::middleware('auth:sanctum')->post('/panel/access-tokens/terminateAll', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'terminateAllSession']);



// Admin Payment Management Routes
Route::middleware('auth:sanctum')->prefix('admin/payments')->group(function () {
    Route::post('/', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'payments']);
    Route::get('/stats', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'paymentStats']);
    Route::get('/export', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'exportPayments']);
    Route::get('/{uuid}/details', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'paymentDetails']);
    Route::post('/{uuid}/update-status', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'updatePaymentStatus']);
    Route::delete('/{uuid}/delete', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'deletePayment']);
    
    // Payment creation routes
    Route::post('/create', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'createPayment']);
    Route::get('/search', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'search']);
    Route::get('/users', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'getUsers']);
    Route::get('/courses', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'getCourses']);
    Route::get('/plans', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'getPlans']);
    Route::get('/paths', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'getPaths']);
});

// Admin Plan Management Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/admin/plans', [\App\Http\Controllers\Api\Admin\PlanController::class, 'plans']);
    Route::post('/admin/plan/create', [\App\Http\Controllers\Api\Admin\PlanController::class, 'store']);
    Route::get('/admin/plan/{plan}', [\App\Http\Controllers\Api\Admin\PlanController::class, 'show']);
    Route::post('/admin/plan/{plan}/update', [\App\Http\Controllers\Api\Admin\PlanController::class, 'update']);
    Route::delete('/admin/plan/{plan}', [\App\Http\Controllers\Api\Admin\PlanController::class, 'destroy']);

    // Admin Projects Management Routes
    Route::post('/admin/projects', [\App\Http\Controllers\Api\Admin\ProjectController::class, 'index']);
    Route::get('/admin/projects/{project}', [\App\Http\Controllers\Api\Admin\ProjectController::class, 'show']);
    Route::post('/admin/projects/{project}/update', [\App\Http\Controllers\Api\Admin\ProjectController::class, 'update']);
    Route::delete('/admin/projects/{project}', [\App\Http\Controllers\Api\Admin\ProjectController::class, 'destroy']);

    // Admin Paths Management Routes
    Route::post('/admin/paths', [\App\Http\Controllers\Api\Admin\PathController::class, 'paths']);
    Route::post('/admin/path/create', [\App\Http\Controllers\Api\Admin\PathController::class, 'store']);
    Route::get('/admin/path/{path:slug}/edit', [\App\Http\Controllers\Api\Admin\PathController::class, 'edit']);
    Route::patch('/admin/path/{path:slug}/update', [\App\Http\Controllers\Api\Admin\PathController::class, 'update']);
    Route::delete('/admin/path/{path:slug}', [\App\Http\Controllers\Api\Admin\PathController::class, 'destroy']);
    Route::post('/admin/path/removeFile', [\App\Http\Controllers\Api\Admin\PathController::class, 'removeFile']);
    Route::get('/admin/path/search/courses', [\App\Http\Controllers\Api\Admin\PathController::class, 'searchCourses']);
    Route::get('/admin/path/search/paths', [\App\Http\Controllers\Api\Admin\PathController::class, 'searchPaths']);
    Route::post('/admin/path/uploadPoster', [\App\Http\Controllers\Api\Admin\PathController::class, 'uploadPoster']);
    Route::post('/admin/path/uploadIcon', [\App\Http\Controllers\Api\Admin\PathController::class, 'uploadIcon']);
    Route::post('/admin/path/uploadTrailer', [\App\Http\Controllers\Api\Admin\PathController::class, 'uploadTrailer']);
});

// start admin routes
Route::middleware('auth:sanctum')->post('/admin/users', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'users']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/base', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'base']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/details', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'details']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/toggleActive', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'toggleActive']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/removeProvider', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'removeProvider']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/updateSocial', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'updateSocial']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/updateCommunications', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'updateCommunications']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/updateInfo', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'updateInfo']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/updatePassword', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'updatePassword']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/access/addPermission', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'addPermission']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/access/removePermission', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'removePermission']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/access/addRole', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'addRole']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/access/removeRole', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'removeRole']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/toggleSuperUser', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'toggleSuperUser']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/removeLoginRecord', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'removeLoginRecord']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/clearLoginHistory', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'clearLoginHistory']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/terminateSession', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'terminateSession']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/terminateAllSession', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'terminateAllSession']);
Route::middleware('auth:sanctum')->post('/admin/user/{username}/security', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'security']);
Route::middleware('auth:sanctum')->post('/admin/security/access', [\App\Http\Controllers\Api\Admin\User\UserController::class, 'allAccess']);
Route::middleware('auth:sanctum')->post('/admin/levels', [\App\Http\Controllers\Api\Admin\Course\LevelController::class, 'levels']);
Route::middleware('auth:sanctum')->post('/admin/level/create', [\App\Http\Controllers\Api\Admin\Course\LevelController::class, 'store']);
Route::middleware('auth:sanctum')->post('/admin/level/{level}/update', [\App\Http\Controllers\Api\Admin\Course\LevelController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/admin/level/{level}/delete', [\App\Http\Controllers\Api\Admin\Course\LevelController::class, 'delete']);
Route::middleware('auth:sanctum')->post('/admin/statuses', [\App\Http\Controllers\Api\Admin\Course\StatusController::class, 'statuses']);
Route::middleware('auth:sanctum')->post('/admin/status/create', [\App\Http\Controllers\Api\Admin\Course\StatusController::class, 'store']);
Route::middleware('auth:sanctum')->post('/admin/status/{status}/update', [\App\Http\Controllers\Api\Admin\Course\StatusController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/admin/status/{status}/delete', [\App\Http\Controllers\Api\Admin\Course\StatusController::class, 'delete']);
Route::middleware('auth:sanctum')->post('/admin/categories', [\App\Http\Controllers\Api\Admin\Course\CategoryController::class, 'categories']);
Route::middleware('auth:sanctum')->post('/admin/category/create', [\App\Http\Controllers\Api\Admin\Course\CategoryController::class, 'store']);
Route::middleware('auth:sanctum')->post('/admin/category/edit', [\App\Http\Controllers\Api\Admin\Course\CategoryController::class, 'edit']);
Route::middleware('auth:sanctum')->post('/admin/category/update', [\App\Http\Controllers\Api\Admin\Course\CategoryController::class, 'update']);
Route::middleware('auth:sanctum')->post('/admin/category/delete', [\App\Http\Controllers\Api\Admin\Course\CategoryController::class, 'deleteCategories']);
Route::middleware('auth:sanctum')->post('/admin/category/uploadIcon', [\App\Http\Controllers\Api\Admin\Course\CategoryController::class, 'uploadIcon']);
Route::middleware('auth:sanctum')->post('/admin/comments/toggle-approval', [\App\Http\Controllers\Api\Admin\Comment\CommentController::class, 'toggleApproval']);
Route::middleware('auth:sanctum')->post('/admin/comments/send-reply', [\App\Http\Controllers\Api\Admin\Comment\CommentController::class, 'sendReply']);
Route::middleware('auth:sanctum')->post('/admin/searchUser', [\App\Http\Controllers\Api\Admin\HomeController::class, 'searchUser']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/dataForCreateEpisode', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'createEpisodeData']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/createNullEpisode', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'createNullEpisode']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/createEpisode', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'updateEpisode']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/updateEpisode', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'updateEpisode']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/section/{sectionSlug}/episode/{episodeSlug}/edit', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'getEpisodeForEdit']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/episode/uploadAttachedFile', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'uploadAttachedFile']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/episode/status', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'episodeStatus']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/episode/removeFile', [\App\Http\Controllers\Api\Admin\Course\EpisodeController::class, 'removeFile']);
Route::middleware('auth:sanctum')->post('/admin/course/layouts/getInitData', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'getInitData']);
Route::middleware('auth:sanctum')->post('/admin/courses', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'courses']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/section/create', [\App\Http\Controllers\Api\Admin\Course\SectionController::class, 'createSection']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/section/{section}/edit', [\App\Http\Controllers\Api\Admin\Course\SectionController::class, 'editSection']);
Route::middleware('auth:sanctum')->delete('/admin/course/{courseSlug}/section/{section}/delete', [\App\Http\Controllers\Api\Admin\Course\SectionController::class, 'deleteSection']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/baseDetails', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'baseDetails']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/overview', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'overview']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/users', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'users']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/users/assign', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'assignToUser']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/episodes', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'episodes']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/episodes/reorder', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'reorderEpisodes']);
Route::middleware('auth:sanctum')->post('/admin/course/{courseSlug}/comments', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'comments']);
Route::middleware('auth:sanctum')->post('/admin/course/delete', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'deleteCourses']);
Route::middleware('auth:sanctum')->post('/admin/course/create', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'store']);
Route::middleware('auth:sanctum')->post('/admin/course/edit', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'edit']);
Route::middleware('auth:sanctum')->post('/admin/course/update', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'update']);
Route::middleware('auth:sanctum')->post('/admin/course/removeFile', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'removeFile']);
Route::middleware('auth:sanctum')->post('/admin/course/uploadPoster', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'uploadPoster']);
Route::middleware('auth:sanctum')->post('/admin/course/uploadAttachedFile', [\App\Http\Controllers\Api\Admin\Course\CourseController::class, 'uploadAttachedFile']);
Route::middleware('auth:sanctum')->post('/admin/video/upload', [\App\Http\Controllers\Api\Admin\Course\VideoController::class, 'upload']);
Route::middleware('auth:sanctum')->post('/admin/video/process/{video}', [\App\Http\Controllers\Api\Admin\Course\VideoController::class, 'process']);

// Discount management routes
Route::middleware('auth:sanctum')->get('/admin/discounts', [\App\Http\Controllers\Api\Admin\Discount\DiscountController::class, 'index']);
Route::middleware('auth:sanctum')->get('/admin/discount/{id}', [\App\Http\Controllers\Api\Admin\Discount\DiscountController::class, 'show']);
Route::middleware('auth:sanctum')->post('/admin/discount/create', [\App\Http\Controllers\Api\Admin\Discount\DiscountController::class, 'store']);
Route::middleware('auth:sanctum')->put('/admin/discount/{id}', [\App\Http\Controllers\Api\Admin\Discount\DiscountController::class, 'update']);
Route::middleware('auth:sanctum')->post('/admin/discount/{id}/toggle-status', [\App\Http\Controllers\Api\Admin\Discount\DiscountController::class, 'toggleStatus']);
Route::middleware('auth:sanctum')->delete('/admin/discount/{id}', [\App\Http\Controllers\Api\Admin\Discount\DiscountController::class, 'destroy']);

// Search route for discount eligibility
Route::middleware('auth:sanctum')->get('/admin/discount/search/eligibility', [\App\Http\Controllers\Api\Admin\Discount\DiscountController::class, 'search']);
// end admin routes

Route::get('episode/{episode}/playlist', [\App\Http\Controllers\Api\VideoController::class, 'episodeVideo'])->name('api.episode-video');
Route::get('course/{course}/playlist', [\App\Http\Controllers\Api\VideoController::class, 'courseVideo'])->name('api.course-video');
Route::get('/key', [\App\Http\Controllers\Api\VideoController::class, 'videoKey'])->name('api.video-key')->middleware('signed');
Route::get('/stream', [\App\Http\Controllers\Api\VideoController::class, 'videoM3u8'])->name('api.video-m3u8')->middleware('signed');
Route::get('/stream.m3u8', [\App\Http\Controllers\Api\VideoController::class, 'videoPlaylist'])->name('api.video-playlist')->middleware('signed');

// set view video
Route::middleware('auth:sanctum')->post('/video-views/{video}/getWatched', [\App\Http\Controllers\Api\VideoViewsController::class, 'getWatched']);
Route::middleware('auth:sanctum')->post('/video-views/{video}/setWatched', [\App\Http\Controllers\Api\VideoViewsController::class, 'setWatched']);
// download episode video
Route::middleware('auth:sanctum')->post('/episode/{episode}/checkDownload', [\App\Http\Controllers\Api\Course\EpisodeController::class, 'episodeDownloadCheck']);
Route::middleware('signed')->get('/episode/{episode}/download', [\App\Http\Controllers\Api\Course\EpisodeController::class, 'episodeDownload'])->name('api.episode-download');



//chat routes

Route::middleware('auth:sanctum')->get('/messages', [\App\Http\Controllers\Api\Chat\ChatController::class, 'getMessages']);
Route::middleware('auth:sanctum')->get('/messages/{contact}', [\App\Http\Controllers\Api\Chat\ChatController::class, 'getMessagesContact']);
Route::middleware('auth:sanctum')->get('/user/contacts', [\App\Http\Controllers\Api\Chat\ContactController::class, 'index']);
Route::middleware('auth:sanctum')->get('/chats', [\App\Http\Controllers\Api\Chat\ChatController::class, 'chats']);
Route::middleware('auth:sanctum')->get('/chat/@{username}', [\App\Http\Controllers\Api\Chat\ChatController::class, 'getMessages']);
Route::middleware('auth:sanctum')->post('/chat/@{username}/send-message', [\App\Http\Controllers\Api\Chat\ChatController::class, 'sendMessage']);
Route::middleware('auth:sanctum')->post('/chat/mark-as-read/@{username}', [\App\Http\Controllers\Api\Chat\ChatController::class, 'markAsRead']);
Route::middleware('auth:sanctum')->post('/chat/delete-message/{message}', [\App\Http\Controllers\Api\Chat\ChatController::class, 'deleteMessage']);
Route::middleware('auth:sanctum')->post('/chat/edit-message/{message}', [\App\Http\Controllers\Api\Chat\ChatController::class, 'editMessage']);



Broadcast::routes(['middleware' => 'auth:sanctum']);
