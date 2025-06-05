<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\AuthController;

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
    });
}); 