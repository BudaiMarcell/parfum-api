<?php

namespace App\Http\Controllers;

use App\Http\Resources\WishlistResource;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;

/**
 * Customer-facing wishlist management. All endpoints are scoped to the
 * authenticated user — the routes file wraps the group in auth:sanctum
 * and we additionally filter by user_id on every query so a leaked or
 * mis-issued token can't read another user's wishlist.
 */
class WishlistController extends Controller
{
    /**
     * Full wishlist for the current user, newest first. The product (with
     * its primary image and category) is eager-loaded so a single round
     * trip is enough to render the wishlist grid.
     */
    public function index(Request $request)
    {
        $items = Wishlist::where('user_id', $request->user()->id)
            ->with(['product.primaryImage', 'product.category'])
            ->orderByDesc('created_at')
            ->get();

        return WishlistResource::collection($items);
    }

    /**
     * Add a product to the wishlist. Idempotent — the unique index on
     * (user_id, product_id) means a duplicate POST returns the same row
     * via firstOrCreate instead of crashing with a 500.
     *
     * 404s when the product doesn't exist (or is soft-deleted in future).
     */
    public function store(Request $request, int $product)
    {
        // Only active products can be wished — keeps the list from filling
        // up with rows pointing at deactivated SKUs.
        $productModel = Product::where('id', $product)
            ->where('is_active', true)
            ->firstOrFail();

        $entry = Wishlist::firstOrCreate([
            'user_id'    => $request->user()->id,
            'product_id' => $productModel->id,
        ]);

        $entry->load(['product.primaryImage', 'product.category']);

        // 201 on first add, 200 on idempotent re-add — both fine here.
        $status = $entry->wasRecentlyCreated ? 201 : 200;

        return (new WishlistResource($entry))
            ->response()
            ->setStatusCode($status);
    }

    /**
     * Remove a product from the wishlist. Returns 200 even when there's
     * nothing to remove — the desired post-state ("not wished") is what
     * the caller cares about, so we don't 404 a no-op.
     */
    public function destroy(Request $request, int $product)
    {
        Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $product)
            ->delete();

        return response()->json(['message' => 'Removed from wishlist.']);
    }
}
