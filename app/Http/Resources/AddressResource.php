<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'label'      => $this->label,
            'country'    => $this->country,
            'city'       => $this->city,
            'zip_code'   => $this->zip_code,
            'street'     => $this->street,
            'is_default' => $this->is_default,
            'full_address' => "{$this->zip_code} {$this->city}, {$this->street}",
        ];
    }
}