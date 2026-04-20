<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Auth\GoogleController;

// Auth Routes
Route::get('/login', function () {
    if (auth()->check()) return redirect('/');
    return view('auth.login');
})->name('login');

Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);
Route::post('/logout', [GoogleController::class, 'logout'])->name('logout');

// Protected Chat Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('chat');
    
    // Session API
    Route::get('/api/sessions', [ChatController::class, 'getSessions'])->name('sessions.index');
    Route::get('/api/sessions/{id}/messages', [ChatController::class, 'getMessages'])->name('sessions.messages');
    Route::delete('/api/sessions/{id}', [ChatController::class, 'deleteSession'])->name('sessions.delete');

    Route::post('/api/chat', [ChatController::class, 'sendMessage'])
        ->name('chat.send')
        ->middleware('throttle:chat');
});
