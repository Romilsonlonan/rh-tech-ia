<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\NotionController;
use App\Http\Controllers\Api\V1\LogStreamController;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    // Log Stream (SSE) - Protected for dev
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/logs/stream', [LogStreamController::class, 'stream']);
        Route::post('/logs', [LogStreamController::class, 'log']);
        Route::post('/logs/pipeline', [LogStreamController::class, 'pipelineStatus']);
        Route::delete('/logs', [LogStreamController::class, 'clearLogs']);
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [UserController::class, 'show']);
        Route::put('/user', [UserController::class, 'update']);

        // Notion Integration
        Route::get('/notion/studies', [NotionController::class, 'studies']);
        Route::get('/notion/studies/{pageId}', [NotionController::class, 'studyContent']);
        Route::post('/notion/studies', [NotionController::class, 'exportStudy']);
        Route::put('/notion/studies/{pageId}', [NotionController::class, 'updateStudy']);
        Route::delete('/notion/studies/{pageId}', [NotionController::class, 'deleteStudy']);
    });
});
