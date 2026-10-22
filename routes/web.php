<?php

use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\EnsurePasswordIsChanged;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('ganti-password', [PasswordController::class, 'edit'])->name('user-password.edit');
    Route::put('ganti-password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::middleware(EnsurePasswordIsChanged::class)->group(function () {
        Route::get('/', HomeController::class)->name('home');
    });
});
