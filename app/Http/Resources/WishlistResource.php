<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wishlist row projection. The product is always eager-loaded with its
 * primary image so the frontend can render the card without a follow-up
 * fetch — see WishlistController::index for the .with() declaration.
 */
class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'product_id' => $this->product_id,
            'product'    => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
