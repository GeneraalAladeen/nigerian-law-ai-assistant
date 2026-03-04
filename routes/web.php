<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('auth.login'))->name('login');
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::post('/logout', [GoogleAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => view('chat.index'));
    Route::get('/conversation/{conversationId}', fn (string $conversationId) => view('chat.index', ['conversationId' => $conversationId]))->name('chat.conversation');
    Route::post('/chat/stop', function () {
        Cache::put('chat_stop_'.auth()->id(), true, 30);

        return response()->noContent();
    })->name('chat.stop');
});
