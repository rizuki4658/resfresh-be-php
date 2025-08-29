<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FallbackController;
use App\Http\Controllers\TaskController;

Route::middleware(['throttle:login'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['throttle:register'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware(['throttle:password'])->group(function () {
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
});

// Protected routes - gunakan jwt.verify custom middleware
Route::middleware(['jwt.verify'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/users', [AuthController::class, 'users']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::get('/sessions', [AuthController::class, 'sessions']);
    Route::delete('/sessions/{sessionId}', [AuthController::class, 'revokeSession']);
    Route::get('/login-history', [AuthController::class, 'loginHistory']);
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks-statistics', [TaskController::class, 'statistics']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
});

Route::get('/email/verify/{token}', [AuthController::class, 'verifyEmail'])
    ->name('verification.verify');
Route::post('/email/resend', [AuthController::class, 'resendVerification'])
    ->middleware(['throttle:6,1']);
Route::fallback([FallbackController::class, 'handler']);
