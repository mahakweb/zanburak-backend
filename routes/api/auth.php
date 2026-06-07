<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authenticated user & logout
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'mobile' => $user->mobile,
            'mobile_verified_at' => $user->mobile_verified_at,
            'username' => $user->username,
            'profile_pic' => $user->profile_pic,
            'cover_pic' => $user->cover_pic,
            'wallet_balance' => $user->wallet_balance,
            'score' => $user->currentScore(),
            'last_seen' => $user->last_seen,
            'active' => $user->active,
            'is_superuser' => $user->is_superuser,
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'roles' => $user->roles->pluck('name'),
        ];
    })->name('api.auth.user');

    Route::post('auth/logout', [\App\Http\Controllers\Api\Auth\LoginController::class, 'logout'])->name('api.auth.logout');
});

// Unified auth endpoints
Route::prefix('auth')->as('api.auth.')->group(function () {
    Route::post('identify', [\App\Http\Controllers\Api\Auth\UnifiedAuthController::class, 'identify'])->name('identify');
    Route::post('send-otp', [\App\Http\Controllers\Api\Auth\UnifiedAuthController::class, 'sendOtp'])->name('send-otp');
    Route::post('verify-otp', [\App\Http\Controllers\Api\Auth\UnifiedAuthController::class, 'verifyOtp'])->name('verify-otp');
    Route::post('password-login', [\App\Http\Controllers\Api\Auth\UnifiedAuthController::class, 'passwordLogin'])->name('password-login');

    // Legacy
    Route::post('login', [\App\Http\Controllers\Api\Auth\LoginController::class, 'login'])->name('login');
    Route::post('register', [\App\Http\Controllers\Api\Auth\UnifiedAuthController::class, 'register'])->name('register');
    Route::post('verify-mobile-after-registration', [\App\Http\Controllers\Api\Auth\UnifiedAuthController::class, 'verifyMobileAfterRegistration'])->name('verify-mobile-after-registration');
    Route::post('password/reset/send-otp', [\App\Http\Controllers\Api\Auth\UnifiedAuthController::class, 'sendResetOtp'])->name('password.reset.send-otp');
    Route::post('password/reset/verify-otp', [\App\Http\Controllers\Api\Auth\UnifiedAuthController::class, 'verifyResetOtp'])->name('password.reset.verify-otp');
    Route::post('password/reset', [\App\Http\Controllers\Api\Auth\UnifiedAuthController::class, 'resetPassword'])->name('password.reset');
    Route::post('password/email', [\App\Http\Controllers\Api\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
});

Route::get('email/verify/{id}/{hash}', [\App\Http\Controllers\Api\Auth\EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

Route::middleware('auth:sanctum')->post('/email/resend', [\App\Http\Controllers\Api\Auth\EmailVerificationController::class, 'resend'])->name('api.auth.email.resend');

// OAuth
Route::prefix('oauth')->as('api.oauth.')->group(function () {
    Route::get('{driver}', [\App\Http\Controllers\Api\Auth\OAuthController::class, 'redirect'])->name('redirect');
    Route::get('{driver}/callback', [\App\Http\Controllers\Api\Auth\OAuthController::class, 'callback'])->name('callback');
});


