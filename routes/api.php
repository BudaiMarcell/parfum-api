<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminCouponController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Analytics\TrackingController;
use App\Http\Controllers\Analytics\AnalyticsDashboardController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::get('/categories',        [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);
Route::get('/products',          [ProductController::class, 'index']);
Route::get('/products/{slug}',   [ProductController::class, 'show']);

Route::post('/analytics/track', [TrackingController::class, 'store'])
    ->middleware(['tracking.key', 'throttle:60,1']);

// Heartbeat — called ~every 30s while the page is visible. Higher throttle
// limit since multiple tabs × long sessions can add up quickly.
Route::post('/analytics/ping', [TrackingController::class, 'ping'])
    ->middleware(['tracking.key', 'throttle:240,1']);

Route::get('/cart',          [CartController::class, 'index']);
Route::post('/cart',         [CartController::class, 'store']);
Route::put('/cart/{id}',     [CartController::class, 'update']);
Route::delete('/cart/{id}',  [CartController::class, 'destroy']);
Route::delete('/cart',       [CartController::class, 'clear']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    Route::get('/addresses',         [AddressController::class, 'index']);
    Route::post('/addresses',        [AddressController::class, 'store']);
    Route::put('/addresses/{id}',    [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);

    Route::get('/orders',      [OrderController::class, 'index']);
    Route::post('/orders',     [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    Route::middleware('admin')->prefix('admin')->group(function () {

        Route::get('/products',              [AdminProductController::class, 'index']);
        Route::post('/products',             [AdminProductController::class, 'store']);
        Route::post('/products/bulk-delete', [AdminProductController::class, 'bulkDelete']);
        Route::post('/products/bulk-update', [AdminProductController::class, 'bulkUpdate']);
        Route::get('/products/{id}',         [AdminProductController::class, 'show']);
        Route::put('/products/{id}',         [AdminProductController::class, 'update']);
        Route::delete('/products/{id}',      [AdminProductController::class, 'destroy']);

        Route::get('/categories',         [AdminCategoryController::class, 'index']);
        Route::post('/categories',        [AdminCategoryController::class, 'store']);
        Route::get('/categories/{id}',    [AdminCategoryController::class, 'show']);
        Route::put('/categories/{id}',    [AdminCategoryController::class, 'update']);
        Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy']);

        Route::get('/orders',                [AdminOrderController::class, 'index']);
        Route::get('/orders/{id}',           [AdminOrderController::class, 'show']);
        Route::put('/orders/{id}/status',    [AdminOrderController::class, 'updateStatus']);
        Route::put('/orders/{id}/payment',   [AdminOrderController::class, 'updatePayment']);
        Route::delete('/orders/{id}',        [AdminOrderController::class, 'destroy']);

        Route::get('/coupons',         [AdminCouponController::class, 'index']);
        Route::post('/coupons',        [AdminCouponController::class, 'store']);
        Route::get('/coupons/{id}',    [AdminCouponController::class, 'show']);
        Route::put('/coupons/{id}',    [AdminCouponController::class, 'update']);
        Route::delete('/coupons/{id}', [AdminCouponController::class, 'destroy']);

        Route::get('/audit-logs', [AdminAuditLogController::class, 'index']);

        Route::get('/analytics/overview',     [AnalyticsDashboardController::class, 'overview']);
        Route::get('/analytics/hourly',       [AnalyticsDashboardController::class, 'hourly']);
        Route::get('/analytics/daily',        [AnalyticsDashboardController::class, 'daily']);
        Route::get('/analytics/top-products', [AnalyticsDashboardController::class, 'topProducts']);
        Route::get('/analytics/devices',      [AnalyticsDashboardController::class, 'devices']);
        Route::get('/analytics/funnel',       [AnalyticsDashboardController::class, 'funnel']);
        Route::get('/analytics/realtime',     [AnalyticsDashboardController::class, 'realtime']);
    });
});