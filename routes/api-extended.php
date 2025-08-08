<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\ScheduleController;

// Additional member routes
Route::prefix('members')->middleware('auth:sanctum')->group(function () {
    Route::get('/{id}/check-ins', [MemberController::class, 'checkInHistory']);
    Route::get('/{id}/statistics', [MemberController::class, 'statistics']);
    Route::get('/{id}/sessions', [MemberController::class, 'sessions']);
});

// API cho huấn luyện viên - Quản trị viên và Nhân viên
Route::prefix('trainers')->middleware(['auth:sanctum', 'role:admin,staff,owner'])->group(function () {
    Route::get('/', [TrainerController::class, 'index']);
    Route::post('/', [TrainerController::class, 'store']);
    Route::put('/{id}', [TrainerController::class, 'update']);
    Route::delete('/{id}', [TrainerController::class, 'destroy']);
});

// API cho huấn luyện viên - Tất cả người dùng
Route::prefix('trainers')->middleware('auth:sanctum')->group(function () {
    Route::get('/{id}', [TrainerController::class, 'show']);
    Route::get('/{id}/schedule', [TrainerController::class, 'schedule']);
    Route::get('/{id}/reviews', [TrainerController::class, 'reviews']);
    Route::post('/{id}/reviews', [TrainerController::class, 'addReview']);
    Route::get('/{id}/statistics', [TrainerController::class, 'statistics']);
});

// API cho lịch trình - Quản trị viên và Nhân viên
Route::prefix('schedules')->middleware(['auth:sanctum', 'role:admin,staff,owner'])->group(function () {
    Route::post('/', [ScheduleController::class, 'store']);
    Route::put('/{id}', [ScheduleController::class, 'update']);
    Route::delete('/{id}', [ScheduleController::class, 'destroy']);
    Route::patch('/{id}/status', [ScheduleController::class, 'updateStatus']);
});

// API cho lịch trình - Tất cả người dùng
Route::prefix('schedules')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ScheduleController::class, 'index']);
    Route::get('/{id}', [ScheduleController::class, 'show']);
    Route::get('/available-slots', [ScheduleController::class, 'availableSlots']);
    Route::get('/statistics', [ScheduleController::class, 'statistics']);
});

// API cho sessions (alias for schedules)
Route::prefix('sessions')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ScheduleController::class, 'index']);
    Route::get('/{id}', [ScheduleController::class, 'show']);
    
    Route::middleware('role:admin,staff,owner')->group(function () {
        Route::post('/', [ScheduleController::class, 'store']);
        Route::put('/{id}', [ScheduleController::class, 'update']);
        Route::delete('/{id}', [ScheduleController::class, 'destroy']);
    });
});