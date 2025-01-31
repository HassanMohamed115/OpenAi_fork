<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatWithEmbeddingsController;
use App\Http\Controllers\DeepSeekController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OpenAIController;
use App\Http\Controllers\PineconeController;
use App\Http\Controllers\VectorDatabaesController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::get('/chat', [ChatController::class, 'chat']);
Route::get('/test', function (){
    return "test";
});

Route::get('/chat_gpt', [OpenAIController::class, 'chat']);
Route::get('/ask_deepseek', [DeepSeekController::class, 'chat']);

Route::get('/vectors/store', [VectorDatabaesController::class, 'insert_vectors']);

Route::get('/vectors/search', [VectorDatabaesController::class, 'test_embeddings']);


Route::get('/pinecone/store', [PineconeController::class, 'store']);
Route::get('/pinecone/search', [PineconeController::class, 'search']);

