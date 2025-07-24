<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\Auth\VerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('auth')
    ->group(function () {
        Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register'])
            ->name('auth.register')->middleware('guest');
        Route::post('/login', [LoginController::class, 'login'])
            ->name('auth.login')->middleware('guest');
        Route::post('/logout', [App\Http\Controllers\Auth\LogoutController::class, 'logout'])
            ->name('auth.logout')->middleware('auth:api');
        Route::post('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])
            ->name('auth.forgot-password.email')->middleware('guest');
        Route::post('/password-reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])
            ->name('auth.password.reset')->middleware('guest');
        Route::get('/email/verify', [VerificationController::class, 'verify'])
            ->name('auth.verification.verify')->middleware('auth:api', 'signed','throttle:6,1');
        Route::post('/email/resend-verification', [VerificationController::class, 'resend'])
            ->middleware('auth:api', 'throttle:6,1')->name('auth.verification.resend');
        Route::post('/login/2fa-challenge', [LoginController::class, 'challenge'])
            ->middleware('guest')->name('auth.login.2fa-challenge');
        Route::middleware('auth:api')->prefix('2fa')->group(function () {
            Route::post('/setup', [TwoFactorAuthenticationController::class, 'setup'])
                ->name('auth.2fa.setup');
            Route::post('/enable', [TwoFactorAuthenticationController::class, 'enable'])
                ->name('auth.2fa.enable');
            Route::post('/disable', [TwoFactorAuthenticationController::class, 'disable'])
                ->name('auth.2fa.disable');
            Route::post('/recovery-codes', [TwoFactorAuthenticationController::class, 'generateNewRecoveryCodes'])
                ->name('auth.2fa.recovery-codes');
        });
        Route::post('/change-password', [App\Http\Controllers\Auth\PasswordController::class, 'changePassword'])
            ->name('auth.change-password')->middleware('auth:api');
    });


