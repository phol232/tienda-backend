<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Seguridad\AuthController;

Route::middleware('signed')->group(function () {
    Route::get('/auth/approve-register/{id}', [AuthController::class, 'approveRegister'])
        ->name('auth.approveRegister');

    Route::get('/auth/approve/{id}', [AuthController::class, 'approve'])
        ->name('auth.approve');
});

Route::get('auth/google/redirect',  
    [AuthController::class, 'redirectToGoogle']
)->name('auth.google.redirect');

Route::get('auth/google/callback',  
    [AuthController::class, 'handleGoogleCallback']
)->name('auth.google.callback');

// Microsoft OAuth
Route::get('auth/microsoft/redirect',  
    [AuthController::class, 'redirectToMicrosoft']
)->name('auth.microsoft.redirect');

Route::get('auth/microsoft/callback',  
    [AuthController::class, 'handleMicrosoftCallback']
)->name('auth.microsoft.callback');
