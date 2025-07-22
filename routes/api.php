<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;

Route::post('/register', [RegisterController::class, 'register'])
    ->name('auth.register');
