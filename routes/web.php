<?php

use App\Http\Controllers\ChatWithEmbeddingsController;
use App\Http\Controllers\ChatWithMySqlDatabaseController;
use App\Http\Controllers\StreamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/chat_ai', [ChatWithEmbeddingsController::class,'index'])->name('chat.index');
Route::get('/chat_stream', [ChatWithEmbeddingsController::class, 'chat'])->name('chat.store');

//Route::get('/chat_stream', [ChatWithMySqlDatabaseController::class, 'chat'])->name('chat.store');


// Route::get('/stream', [StreamController::class, 'index'])->name('chat.index');
// Route::get('/stream_message', [StreamController::class, 'streamForm'])->name('chat.streamForm');
