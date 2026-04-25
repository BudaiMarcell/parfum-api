<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds indexes that are NOT auto-created by Laravel's foreignId()->constrained().
 *
 * Note: MySQL auto-indexes every foreign-key column, so
 *   - products.category_id      (already indexed by FK)
 *   - orders.user_id            (already indexed by FK)
 *   - orders.address_id         (already indexed by FK)
 *   - order_items.order_id      (already indexed by FK)
 *   - order_items.product_id    (already indexed by FK)
 *   - analytics_sessions.user_id (already indexed by FK)
 * do NOT need their own single-column indexes.
 *
 * What IS genuinely missing and added here:
 *   - orders (user_id, created_at)  composite → "my orders, newest first"
 *   - orders (payment_status)       admin filter
 *   - products (is_active)          public list filter, runs on every shop page load
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Composite index: powers `WHERE user_id = ? ORDER BY created_at DESC`
            // (customer "my orders" page) without a filesort.
            $table->index(['user_id', 'created_at'], 'orders_user_id_created_at_index');
            // Admin filters by payment_status on the orders dashboard.
            $table->index('payment_status', 'orders_payment_status_index');
        });

        Schema::table('products', function (Blueprint $table) {
            // Public product listing always adds `WHERE is_active = 1`.
            // Selectivity is low but it still helps on warm caches.
            $table->index('is_active', 'products_is_active_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_id_created_at_index');
            $table->dropIndex('orders_payment_status_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_is_active_index');
        });
    }
};
