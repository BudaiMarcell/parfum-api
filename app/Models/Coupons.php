<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupons extends Model
{
    protected $table = 'coupons';

    protected $fillable = [
        'coupon_code',
        'discount_type',
        'discount_value',
        'expiry_date',
        'usage_limit',
        'used_count',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'expiry_date'    => 'date',
        'usage_limit'    => 'integer',
        'used_count'     => 'integer',
        'is_active'      => 'boolean',
    ];
}
