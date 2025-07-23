<?php

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
        Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login'])
            ->name('auth.login')->middleware('guest');
        Route::post('/logout', [App\Http\Controllers\Auth\LogoutController::class, 'logout'])
            ->name('auth.logout')->middleware('auth:api');
        Route::post('/forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])
            ->name('auth.forgot-password.email')->middleware('guest');
        Route::post('/password-reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('auth.password.reset')
            ->middleware('guest');
        Route::get('/email/verify', [VerificationController::class, 'verify'])->name('auth.verification.verify')
            ->middleware('auth:api', 'signed','throttle:6,1');
        Route::post('/email/resend-verification', [VerificationController::class, 'resend'])->middleware('auth:api', 'throttle:6,1')
            ->name('auth.verification.resend');
    });


