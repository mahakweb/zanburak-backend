<?php

use Illuminate\Support\Facades\Route;

// Panel routes
Route::middleware('auth:sanctum')->prefix('panel')->as('api.panel.')->group(function () {
    Route::post('/index', [\App\Http\Controllers\Api\PanelController::class, 'index'])->name('index');
    Route::post('/financial', [\App\Http\Controllers\Api\PanelController::class, 'financial'])->name('financial');
    Route::post('/increase-wallet-balance', [\App\Http\Controllers\Api\PanelController::class, 'increamentWalletAmount'])->name('increase-wallet-balance');
    Route::post('/courses', [\App\Http\Controllers\Api\PanelController::class, 'courses'])->name('courses');
    Route::post('/missions', [\App\Http\Controllers\Api\PanelController::class, 'missions'])->name('missions');
    Route::post('/questions', [\App\Http\Controllers\Api\PanelController::class, 'questions'])->name('questions');
    Route::post('/certifications', [\App\Http\Controllers\Api\PanelController::class, 'certifications'])->name('certifications');
    Route::get('/certificate-templates', [\App\Http\Controllers\Api\PanelController::class, 'certificateTemplates'])->name('certificate-templates');
    Route::post('/certificates/{uuid}/template', [\App\Http\Controllers\Api\PanelController::class, 'updateCertificateTemplate'])->name('certificates.template');
    Route::post('/followed', [\App\Http\Controllers\Api\PanelController::class, 'followed'])->name('followed');
    Route::post('/comments', [\App\Http\Controllers\Api\PanelController::class, 'comments'])->name('comments');

    Route::prefix('notifications')->as('notifications.')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\Panel\NotificationController::class, 'notifications'])->name('index');
    });
    Route::prefix('notification')->as('notification.')->group(function () {
        Route::post('/details', [\App\Http\Controllers\Api\Panel\NotificationController::class, 'notificationDetails'])->name('details');
        Route::post('/unread', [\App\Http\Controllers\Api\Panel\NotificationController::class, 'unreadNotifications'])->name('unread');
        Route::post('/delete', [\App\Http\Controllers\Api\Panel\NotificationController::class, 'deleteNotification'])->name('delete');
        Route::post('/bulk-delete', [\App\Http\Controllers\Api\Panel\NotificationController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/delete-all', [\App\Http\Controllers\Api\Panel\NotificationController::class, 'deleteAll'])->name('delete-all');
        Route::post('/bulk-mark-read', [\App\Http\Controllers\Api\Panel\NotificationController::class, 'bulkMarkAsRead'])->name('bulk-mark-read');
        Route::post('/bulk-mark-unread', [\App\Http\Controllers\Api\Panel\NotificationController::class, 'bulkMarkAsUnread'])->name('bulk-mark-unread');
        Route::post('/mark-all-read', [\App\Http\Controllers\Api\Panel\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::post('/mark-all-unread', [\App\Http\Controllers\Api\Panel\NotificationController::class, 'markAllAsUnread'])->name('mark-all-unread');
    });

    Route::prefix('vip')->as('vip.')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\Panel\VipController::class, 'index'])->name('index');
        Route::post('/get-plans-list', [\App\Http\Controllers\Api\Panel\VipController::class, 'getPlansList'])->name('plans');
        Route::post('/purchase', [\App\Http\Controllers\Api\Panel\VipController::class, 'purchase'])->name('purchase');
    });
    Route::get('/vip/purchase/callback', [\App\Http\Controllers\Api\Panel\VipController::class, 'vipCallback'])->name('vip.callback');

    Route::prefix('profile')->as('profile.')->group(function () {
        Route::post('/get-mobile-info', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'getMobileInfo'])->name('get-mobile-info');
        Route::post('/send-otp', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'sendOtp'])->name('send-otp');
        Route::post('/verify-otp', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'verifyOtp'])->name('verify-otp');
        Route::post('/change-password', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'changePassword'])->name('change-password');
        Route::post('/information-management', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'getUserPreferences'])->name('information-management');
        Route::post('/update-preference', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'updateSinglePreference'])->name('update-preference');
        Route::post('/toggle-all-notifications', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'toggleAllNotifications'])->name('toggle-all-notifications');
        Route::post('/bulk-update-preferences', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'bulkUpdatePreferences'])->name('bulk-update-preferences');
        Route::post('/test-notification', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'testNotification'])->name('test-notification');
        Route::post('/userData', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'getUserData'])->name('user-data');
        Route::post('/update', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'updateProfile'])->name('update');
        Route::post('/change-profile-pic', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'changeProfilePic'])->name('change-profile-pic');
        Route::post('/change-cover-pic', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'changeCoverPic'])->name('change-cover-pic');
        Route::post('/invite-info', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'getInviteInfo'])->name('invite-info');
    });

    Route::prefix('access-tokens')->as('access-tokens.')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'accessTokens'])->name('index');
        Route::post('/terminate', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'terminateSession'])->name('terminate');
        Route::post('/terminateAll', [\App\Http\Controllers\Api\Panel\ProfileController::class, 'terminateAllSession'])->name('terminate-all');
    });

    // Scores
    Route::prefix('scores')->as('scores.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Panel\ScoresController::class, 'index'])->name('index');
        Route::post('/convert-to-money', [\App\Http\Controllers\Api\Panel\ScoresController::class, 'convertToMoney'])->name('convert-to-money');
        Route::get('/conversion-info', [\App\Http\Controllers\Api\Panel\ScoresController::class, 'conversionInfo'])->name('conversion-info');
    });
});


