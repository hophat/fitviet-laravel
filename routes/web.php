<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Trả về view chính cho SPA - tất cả routes khác được xử lý bởi Vue Router
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');

// Auth routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return view('app');
    })->name('dashboard');
    
    // Members
    Route::get('/members', function () {
        return view('app');
    })->name('members.index');
    
    // Trainers
    Route::get('/trainers', function () {
        return view('app');
    })->name('trainers.index');
    
    // Schedule
    Route::get('/schedules', function () {
        return view('app');
    })->name('schedules.index');
    
    // POS
    Route::get('/pos', function () {
        return view('app');
    })->name('pos.index');
    
    // Products
    Route::get('/products', function () {
        return view('app');
    })->name('products.index');
    
    // Memberships
    Route::get('/memberships', function () {
        return view('app');
    })->name('memberships.index');
    
    // Reports
    Route::get('/reports', function () {
        return view('app');
    })->name('reports.index');
});
