<?php

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
        
    });


