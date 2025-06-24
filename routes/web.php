<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Seguridad\AuthController;

Route::middleware('signed')->group(function () {
    Route::get('/auth/approve-register/{id}', [AuthController::class, 'approveRegister'])
        ->name('auth.approveRegister');

    Route::get('/auth/approve/{id}', [AuthController::class, 'approve'])
        ->name('auth.approve');
});
