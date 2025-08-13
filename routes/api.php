<?php

use App\Http\Controllers\V1\Admin\UserController;
use App\Http\Controllers\V1\Auth\LoginController;
use App\Http\Controllers\V1\Auth\SocialAuthController;
use App\Http\Controllers\V1\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\V1\Auth\VerificationController;
use App\Http\Controllers\V1\NotificationController;
use App\Http\Controllers\V1\PaymentController;
use App\Http\Controllers\V1\PermissionsController;
use App\Http\Controllers\V1\ProfileController;
use App\Http\Controllers\V1\RolesController;
use App\Http\Controllers\V1\SupportMessageController;
use App\Http\Controllers\V1\SupportTicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('V1/user', function(Request $request ) {
    return $request->user();
})->middleware('auth:user');

Route::prefix('V1')->group(function () {
    Route::prefix('auth')->middleware('guest')->group(function () {
        Route::post('/register', [App\Http\Controllers\V1\Auth\RegisterController::class, 'register'])
            ->name('auth.register');
        Route::post('/login', [LoginController::class, 'login'])
            ->name('auth.login');
        Route::post('/forgot-password', [App\Http\Controllers\V1\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])
            ->name('auth.forgot-password.email');
        Route::post('/password-reset', [App\Http\Controllers\V1\Auth\ResetPasswordController::class, 'reset'])
            ->name('auth.password.reset');
        Route::post('/login/2fa-challenge', [LoginController::class, 'challenge'])
            ->name('auth.login.2fa-challenge');

        Route::middleware('web')->prefix('social')->group(function () {
            Route::get('{provider}/redirect', [SocialAuthController::class, 'redirectToProvider'])
                ->name('auth.social.redirect');
            Route::get('{provider}/callback', [SocialAuthController::class, 'handleProviderCallback'])
                ->name('auth.social.callback');
        });
    });

    Route::prefix('auth')->middleware('auth:user')->group(function () {
        Route::post('/logout', [App\Http\Controllers\V1\Auth\LogoutController::class, 'logout'])
            ->name('auth.logout');
        Route::post('/email/resend-verification', [VerificationController::class, 'resend'])
            ->middleware('throttle:6,1')->name('auth.verification.resend');
        Route::get('/email/verify', [VerificationController::class, 'verify'])
            ->middleware('signed','throttle:6,1')->name('auth.verification.verify');
        Route::post('/change-password', [App\Http\Controllers\V1\Auth\PasswordController::class, 'changePassword'])
            ->middleware('isActive','verified','isLocked')->name('auth.change-password');
        Route::middleware(['verified','isActive','isLocked','passwordResetNeeded'])
            ->prefix('2fa')->group(function () {
                Route::post('/setup', [TwoFactorAuthenticationController::class, 'setup'])
                    ->name('auth.2fa.setup');
                Route::post('/enable', [TwoFactorAuthenticationController::class, 'enable'])
                    ->name('auth.2fa.enable');
                Route::post('/disable', [TwoFactorAuthenticationController::class, 'disable'])
                    ->name('auth.2fa.disable');
                Route::post('/recovery-codes', [TwoFactorAuthenticationController::class, 'generateNewRecoveryCodes'])
                    ->name('auth.2fa.recovery-codes');
            }
        );
    });

    Route::middleware(['auth:user','verified', 'isActive','isLocked','passwordResetNeeded'])
        ->prefix('admin')->group( function() {
            Route::apiResource('/permissions', PermissionsController::class)->except('destroy');
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

            Route::get('/activities', [App\Http\Controllers\V1\ActivityLogController::class, 'listActivities'])
                ->name('activity.list');

            Route::apiResource('users',UserController::class)->except('update','destroy');
            Route::delete('users', [UserController::class, 'destroy'])
                ->name('user.destroy');
            Route::get('locked/users', [UserController::class, 'lockedUsers'])
                ->name('users.locked');
            Route::post('users/{user}/unlock', [UserController::class, 'unlockUser'])
                ->name('user.unlock');
            Route::post('users/{user}/toggleStatus', [UserController::class, 'toggleUserStatus'])
                ->name('user.toggle_status');
            Route::post('/users/import', [UserController::class, 'import'])->name('users.import');
            Route::post('/users/importAsync', [UserController::class, 'importAsync'])->name('users.import.async');
            Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
            Route::get('/users/exportAsync', [UserController::class, 'exportAsync'])->name('users.export.async');
            Route::get('/exports/download/{file}', [UserController::class, 'download'])
                ->name('exports.download')->middleware(['auth', 'signed:relative']);

            Route::patch('profile/{user}/update', [ProfileController::class, 'update'])
                ->name('profile.update');
            Route::post('profile/{user}/upload', [ProfileController::class, 'uploadProfilePicture'])
                ->name('profile.upload');
        });

    Route::middleware('auth:user')->prefix('payment')->group(function () {
        Route::post('init', [PaymentController::class, 'initialize'])->name('payment.init');
        Route::post('confirm', [PaymentController::class, 'confirm'])->name('payment.verify');
        Route::post('paystack/webhook', [PaymentController::class, 'paystackWebhook'])
            ->withoutMiddleware(['auth:user','verified', 'isActive','isLocked','passwordResetNeeded'])
            ->middleware('guest')->name('payment.paystack.webhook');
    });

    Route::middleware('auth:user', 'isActive','isLocked', 'verified','passwordResetNeeded')
        ->prefix('/notifications')->group(function (): void {
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
    Route::middleware('auth:user', 'isActive','isLocked', 'verified','passwordResetNeeded')
        ->group(function (): void {
            Route::apiResource('tickets', SupportTicketController::class);

            Route::post('/tickets/{supportTicket}/messages', [SupportMessageController::class, 'store'])
                ->name('tickets.messages.store');


        }
    );

});

