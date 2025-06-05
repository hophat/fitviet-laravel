<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\MembershipController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Các routes cần xác thực
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // API cho sản phẩm - Quản trị viên và Nhân viên
    Route::prefix('products')->middleware('role:admin,staff,owner')->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        Route::post('/{id}/stock', [ProductController::class, 'updateStock']);
    });

    // API cho sản phẩm - Tất cả người dùng
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/categories', [ProductController::class, 'categories']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::get('/{id}/stock', [ProductController::class, 'checkStock']);
    });

    // API cho đơn hàng - Quản trị viên và Nhân viên
    Route::prefix('orders')->middleware('role:admin,staff,owner')->group(function () {
        Route::patch('/{id}/status', [OrderController::class, 'updateStatus']);
        Route::post('/{id}/refund', [OrderController::class, 'refund']);
        Route::get('/report', [OrderController::class, 'report']);
        Route::get('/top-products', [OrderController::class, 'topProducts']);
    });

    // API cho đơn hàng - Tất cả người dùng
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{id}', [OrderController::class, 'show']);
    });

    // API cho hội viên - Quản trị viên và Nhân viên
    Route::prefix('members')->middleware('role:admin,staff,owner')->group(function () {
        Route::get('/', [MemberController::class, 'index']);
        Route::post('/', [MemberController::class, 'store']);
        Route::put('/{id}', [MemberController::class, 'update']);
        Route::delete('/{id}', [MemberController::class, 'destroy']);
    });

    // API cho hội viên - Tất cả người dùng
    Route::prefix('members')->group(function () {
        Route::get('/{id}', [MemberController::class, 'show']);
        Route::get('/{id}/orders', [MemberController::class, 'orders']);
        Route::get('/{id}/payments', [MemberController::class, 'payments']);
        Route::get('/{id}/membership', [MemberController::class, 'membership']);
        Route::post('/{id}/checkin', [MemberController::class, 'checkin']);
        Route::get('/{id}/check-ins', [MemberController::class, 'checkInHistory']);
        Route::get('/{id}/statistics', [MemberController::class, 'statistics']);
        Route::get('/{id}/sessions', [MemberController::class, 'sessions']);
    });

    // API cho huấn luyện viên - Quản trị viên và Nhân viên
    Route::prefix('trainers')->middleware('role:admin,staff,owner')->group(function () {
        Route::get('/', [TrainerController::class, 'index']);
        Route::post('/', [TrainerController::class, 'store']);
        Route::put('/{id}', [TrainerController::class, 'update']);
        Route::delete('/{id}', [TrainerController::class, 'destroy']);
    });

    // API cho huấn luyện viên - Tất cả người dùng
    Route::prefix('trainers')->group(function () {
        Route::get('/{id}', [TrainerController::class, 'show']);
        Route::get('/{id}/schedule', [TrainerController::class, 'schedule']);
        Route::get('/{id}/reviews', [TrainerController::class, 'reviews']);
        Route::post('/{id}/reviews', [TrainerController::class, 'addReview']);
        Route::get('/{id}/statistics', [TrainerController::class, 'statistics']);
    });

    // API cho lịch trình - Quản trị viên và Nhân viên
    Route::prefix('schedules')->middleware('role:admin,staff,owner')->group(function () {
        Route::post('/', [ScheduleController::class, 'store']);
        Route::put('/{id}', [ScheduleController::class, 'update']);
        Route::delete('/{id}', [ScheduleController::class, 'destroy']);
        Route::patch('/{id}/status', [ScheduleController::class, 'updateStatus']);
    });

    // API cho lịch trình - Tất cả người dùng
    Route::prefix('schedules')->group(function () {
        Route::get('/', [ScheduleController::class, 'index']);
        Route::get('/{id}', [ScheduleController::class, 'show']);
        Route::get('/available-slots', [ScheduleController::class, 'availableSlots']);
        Route::get('/statistics', [ScheduleController::class, 'statistics']);
    });

    // API cho sessions (alias for schedules)
    Route::prefix('sessions')->group(function () {
        Route::get('/', [ScheduleController::class, 'index']);
        Route::get('/{id}', [ScheduleController::class, 'show']);
        
        Route::middleware('role:admin,staff,owner')->group(function () {
            Route::post('/', [ScheduleController::class, 'store']);
            Route::put('/{id}', [ScheduleController::class, 'update']);
            Route::delete('/{id}', [ScheduleController::class, 'destroy']);
        });
    });

    // API cho gói thành viên - Quản trị viên và Nhân viên
    Route::prefix('memberships')->middleware('role:admin,staff,owner')->group(function () {
        Route::get('/', [MembershipController::class, 'index']);
        Route::post('/', [MembershipController::class, 'store']);
        Route::put('/{id}', [MembershipController::class, 'update']);
        Route::delete('/{id}', [MembershipController::class, 'destroy']);
        Route::post('/assign', [MembershipController::class, 'assignToMember']);
        Route::post('/renew/{memberId}', [MembershipController::class, 'renewMembership']);
        Route::post('/{id}/freeze', [MembershipController::class, 'freezeMembership']);
        Route::post('/{id}/cancel', [MembershipController::class, 'cancelMembership']);
        Route::get('/statistics', [MembershipController::class, 'statistics']);
    });

    // API cho gói thành viên - Tất cả người dùng
    Route::prefix('memberships')->group(function () {
        Route::get('/{id}', [MembershipController::class, 'show']);
    });
}); 