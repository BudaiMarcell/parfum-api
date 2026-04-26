<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wishlist — one row per (user, product) pairing.
 *
 * The unique composite index doubles as our idempotency guarantee: clicking
 * "add to wishlist" twice from a stale tab can't create a duplicate row.
 * Cascade deletes mean removing a user (or a product) automatically purges
 * the matching wishlist entries — no orphans, no manual cleanup needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // One product per user, max. The unique index also provides a
            // covering index for the "is this product wished?" lookup.
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
