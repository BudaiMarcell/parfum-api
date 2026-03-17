<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'price'          => (float) $this->price,
            'stock_quantity' => $this->stock_quantity,
            'volume_ml'      => $this->volume_ml ? $this->volume_ml . 'ml' : null,
            'gender'         => $this->gender,
            'is_active'      => $this->is_active,
            'in_stock'       => $this->stock_quantity > 0,
            'category'       => new CategoryResource($this->whenLoaded('category')),
            'images'         => ProductImageResource::collection($this->whenLoaded('images')),
            'primary_image'  => new ProductImageResource($this->whenLoaded('primaryImage')),
            'created_at'     => $this->created_at->format('Y-m-d'),
        ];
    }
}