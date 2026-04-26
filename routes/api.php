<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminCouponController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Analytics\TrackingController;
use App\Http\Controllers\Analytics\AnalyticsDashboardController;

// Auth endpoints are rate limited to slow down credential stuffing /
// brute-force attempts. 5 requests per minute per IP is enough for a
// real user who mistypes their password a few times; attackers see 429.
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login',    [AuthController::class, 'login'])->middleware('throttle:5,1');

// Email verification — landing route hit by the link in the verification
// email. Must be `signed` so a tampered or expired URL is rejected at
// the middleware level. Throttle keeps the endpoint cheap to expose.
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::get('/categories',        [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);
Route::get('/products',          [ProductController::class, 'index']);
Route::get('/products/{slug}',   [ProductController::class, 'show']);

Route::post('/analytics/track', [TrackingController::class, 'store'])
    ->middleware(['tracking.key', 'throttle:30,1']);

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

    // Self-service profile management. Splitting password into its own
    // endpoint keeps the cheap profile fields off the current_password
    // check — see AuthController::updateProfile / changePassword.
    Route::put('/me',           [AuthController::class, 'updateProfile']);
    Route::post('/me/password', [AuthController::class, 'changePassword']);

    // Resend the verification mail. Throttled tighter than register
    // because it's auth'd — 6/min is plenty for "I didn't get it".
    Route::post('/email/resend', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1');

    // Wishlist — keyed by product id in the URL so add/remove are RESTful
    // and the route doesn't need a request body. Idempotent on both sides.
    Route::get('/wishlist',                 [WishlistController::class, 'index']);
    Route::post('/wishlist/{product}',      [WishlistController::class, 'store']);
    Route::delete('/wishlist/{product}',    [WishlistController::class, 'destroy']);

    // Saved payment methods — see the migration's docblock for what is
    // (and is NOT) stored. POST accepts only display metadata.
    Route::get('/payment-methods',          [PaymentMethodController::class, 'index']);
    Route::post('/payment-methods',         [PaymentMethodController::class, 'store']);
    Route::put('/payment-methods/{id}',     [PaymentMethodController::class, 'update']);
    Route::delete('/payment-methods/{id}',  [PaymentMethodController::class, 'destroy']);

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