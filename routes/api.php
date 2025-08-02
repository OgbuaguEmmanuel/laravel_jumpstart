<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentTestController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RolesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('V1')->group(function () {

    Route::middleware('auth:api','verified','isLocked','isActive','passwordResetNeeded')
        ->get('/user', function (Request $request) {
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

            Route::middleware('web')->group(function () {
                Route::get('{provider}/redirect', [SocialAuthController::class, 'redirectToProvider'])
                    ->name('auth.social.redirect');
                Route::get('{provider}/callback', [SocialAuthController::class, 'handleProviderCallback'])
                    ->name('auth.social.callback');
            });
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
                ->middleware('isActive','verified','isLocked')->name('auth.change-password');
            Route::prefix('2fa')->middleware(['verified','isActive','isLocked','passwordResetNeeded'])->group(function () {
                Route::post('/setup', [TwoFactorAuthenticationController::class, 'setup'])
                    ->name('auth.2fa.setup');
                Route::post('/enable', [TwoFactorAuthenticationController::class, 'enable'])
                    ->name('auth.2fa.enable');
                Route::post('/disable', [TwoFactorAuthenticationController::class, 'disable'])
                    ->name('auth.2fa.disable');
                Route::post('/recovery-codes', [TwoFactorAuthenticationController::class, 'generateNewRecoveryCodes'])
                    ->name('auth.2fa.recovery-codes');
            });
        });

    Route::prefix('/notifications')->middleware('auth:api', 'isActive','isLocked', 'verified','passwordResetNeeded')
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

    Route::middleware(['auth:api','verified', 'isActive','isLocked','passwordResetNeeded'])->prefix('admin')
        ->group( function() {
            Route::apiResource('/permissions', PermissionsController::class)->only('index', 'store');
            Route::post('users/{user}/assign-permissions', [PermissionsController::class, 'assignPermissionsToUser'])
                ->name('permission.user.assign');
            Route::post('users/{user}/revoke-permissions', [PermissionsController::class, 'revokePermissionsToUser'])
                ->name('permission.user.revoke');

            Route::apiResource('/roles', RolesController::class)->except('destroy');
            Route::post('/roles/{role}/give-permission', [RolesController::class, 'givePermissions'])
                ->name('permission.role.assign');
            Route::post('/roles/{role}/revoke-permission', [RolesController::class, 'revokePermissions'])
                ->name('permission.role.revoke');
            Route::post('/users/{user}/assignRole/roles/{role}', [RolesController::class,'assignRole'])
                ->name('role.assign');
            Route::post('/users/{user}/removeRole/roles/{role}', [RolesController::class,'removeRole'])
                ->name('role.revoke');

            Route::get('/activities', [App\Http\Controllers\ActivityLogController::class, 'listActivities'])
                ->name('activity.list');

            Route::apiResource('users',UserController::class)->except('update');
            Route::post('users/{user}/unlock', [UserController::class, 'unlockUser'])
                ->name('user.unlock');
            Route::post('users/{user}/toggleStatus', [UserController::class, 'toggleUserStatus'])
                ->name('user.toggle_status');

            Route::patch('profile/update', [ProfileController::class, 'update'])
                ->name('profile.update');
            Route::post('profile/upload', [ProfileController::class, 'uploadProfilePicture'])
                ->name('profile.upload');
        });

    Route::middleware('auth:api')->prefix('payment')->group(function () {
        Route::post('init', [PaymentTestController::class, 'initialize'])->name('payment.init');
        Route::post('confirm', [PaymentTestController::class, 'confirm'])->name('payment.verify');
    });

    Route::post('/payment/paystack/webhook', [PaymentTestController::class, 'paystackWebhook'])
        ->middleware('guest')->name('payment.paystack.webhook');

});
