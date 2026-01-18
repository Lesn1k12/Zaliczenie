<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::apiResource('boards', BoardController::class);
    Route::apiResource('boards.tasks', TaskController::class);

    Route::post('boards/{board}/export', [ExportController::class, 'export']);
    Route::get('jobs/{job}', [ExportController::class, 'status']);
    Route::get('jobs/{job}/download', [ExportController::class, 'download']);
});