<?php

namespace App\Http\Resources;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'phone'             => $this->phone,
            // Surface verification state so the frontend can render a banner
            // ("please verify your email") without an extra round-trip.
            'email_verified_at' => $this->email_verified_at,
            'email_verified'    => !is_null($this->email_verified_at),
            'is_admin'          => Admin::where('email', $this->email)->exists(),
        ];
    }
}