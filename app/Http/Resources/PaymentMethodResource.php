<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Display projection for a saved payment method. The expiry is formatted
 * as MM/YY (zero-padded month) so the frontend can render it without
 * having to know about the underlying integer columns.
 */
class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'brand'      => $this->brand,
            'last_four'  => $this->last_four,
            'exp_month'  => $this->exp_month,
            'exp_year'   => $this->exp_year,
            'expiry'     => sprintf('%02d/%02d', $this->exp_month, $this->exp_year % 100),
            'is_default' => (bool) $this->is_default,
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
