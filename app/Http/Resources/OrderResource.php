<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'status'         => $this->status,
            'total_amount'   => (float) $this->total_amount,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'notes'          => $this->notes,
            'address'        => new AddressResource($this->whenLoaded('address')),
            'items'          => OrderItemResource::collection($this->whenLoaded('items')),
            'items_count'    => $this->items->count(),
            'created_at'     => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}