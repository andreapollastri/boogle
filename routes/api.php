<?php

use App\Http\Controllers\Api\Admin\ExceptionApiController;
use App\Http\Controllers\Api\Admin\GroupApiController;
use App\Http\Controllers\Api\Admin\ProjectApiController;
use App\Http\Controllers\Api\ExceptionReportController;
use Illuminate\Support\Facades\Route;

Route::post('/log', [ExceptionReportController::class, 'store']);

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('projects', [ProjectApiController::class, 'index']);
    Route::post('projects', [ProjectApiController::class, 'store']);
    Route::get('projects/{project}', [ProjectApiController::class, 'show']);
    Route::patch('projects/{project}', [ProjectApiController::class, 'update']);
    Route::delete('projects/{project}', [ProjectApiController::class, 'destroy']);

    Route::get('groups', [GroupApiController::class, 'index']);
    Route::post('groups', [GroupApiController::class, 'store']);
    Route::get('groups/{group}', [GroupApiController::class, 'show']);
    Route::patch('groups/{group}', [GroupApiController::class, 'update']);
    Route::delete('groups/{group}', [GroupApiController::class, 'destroy']);

    Route::get('exceptions', [ExceptionApiController::class, 'index']);
    Route::get('projects/{project}/exceptions', [ExceptionApiController::class, 'byProject']);
    Route::get('projects/{project}/exceptions/{exception}', [ExceptionApiController::class, 'show']);
    Route::patch('projects/{project}/exceptions/{exception}/status', [ExceptionApiController::class, 'updateStatus']);
});
