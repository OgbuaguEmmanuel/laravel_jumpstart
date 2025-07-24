<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RolesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('auth')->middleware('guest')
    ->group(function () {
        Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register'])
            ->name('auth.register');
        Route::post('/login', [LoginController::class, 'login'])
            ->name('auth.login');
        Route::post('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])
            ->name('auth.forgot-password.email');
        Route::post('/password-reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])
            ->name('auth.password.reset');
        Route::post('/login/2fa-challenge', [LoginController::class, 'challenge'])
            ->name('auth.login.2fa-challenge');
    });

Route::prefix('auth')->middleware('auth:api')
    ->group(function () {
        Route::post('/logout', [App\Http\Controllers\Auth\LogoutController::class, 'logout'])
            ->name('auth.logout');
        Route::get('/email/verify', [VerificationController::class, 'verify'])
            ->middleware('signed','throttle:6,1')->name('auth.verification.verify');
        Route::post('/email/resend-verification', [VerificationController::class, 'resend'])
            ->middleware('throttle:6,1')->name('auth.verification.resend');
        Route::post('/change-password', [App\Http\Controllers\Auth\PasswordController::class, 'changePassword'])
            ->name('auth.change-password');
        Route::prefix('2fa')->group(function () {
            Route::post('/setup', [TwoFactorAuthenticationController::class, 'setup'])
                ->name('auth.2fa.setup');
            Route::post('/enable', [TwoFactorAuthenticationController::class, 'enable'])
                ->name('auth.2fa.enable');
            Route::post('/disable', [TwoFactorAuthenticationController::class, 'disable'])
                ->name('auth.2fa.disable');
            Route::post('/recovery-codes', [TwoFactorAuthenticationController::class, 'generateNewRecoveryCodes'])
                ->name('auth.2fa.recovery-codes');
        });

        Route::get('redirect/{provider}', [SocialAuthController::class, 'redirectToProvider'])
            ->name('auth.social.redirect');
        Route::get('callback/{provider}', [SocialAuthController::class, 'handleProviderCallback'])
            ->name('auth.social.callback');

    });

Route::prefix('/notifications')->middleware('auth:apir')
    ->group(function (): void {
        Route::get('/all', [NotificationController::class, 'index'])
            ->name('notification.all');
        Route::get('/read', [NotificationController::class, 'read'])
            ->name('notification.read');
        Route::get('/unread', [NotificationController::class, 'unread'])
            ->name('notification.unread');
        Route::get('/{notification}/view', [NotificationController::class, 'view'])
            ->name('notification.view');
        Route::post('/{notification}/unread', [NotificationController::class, 'markUnread'])
            ->name('notification.markunread');
        Route::post('/{notification}/read', [NotificationController::class, 'markRead'])
            ->name('notification.markread');
        Route::post('/{notification}/delete', [NotificationController::class, 'destroy'])
            ->name('notification.delete');
    });

Route::middleware(['auth:api','role:super-admin'])->group( function() {
    Route::apiResource('/permissions', App\Http\Controllers\PermissionsController::class)
        ->only('index', 'store');

    Route::apiResource('/roles', RolesController::class)
        ->only('index', 'store');
    Route::post('/roles/{role}/give-permission', [RolesController::class, 'givePermission']);
    Route::post('/roles/{role}/revoke-permission', [RolesController::class, 'revokePermission']);
    Route::post('/users/{user}/assignRole/roles/{role}', [RolesController::class,'assignRole']);
    Route::post('/users/{user}/removeRole/roles/{role}', [RolesController::class,'removeRole']);
});


