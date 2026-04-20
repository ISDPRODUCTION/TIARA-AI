<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/', [ChatController::class, 'index']);
Route::post('/api/chat', [ChatController::class, 'sendMessage'])
    ->name('chat.send')
    ->middleware('throttle:chat');
