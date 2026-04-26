<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One entry per user/product pairing. The DB-level unique index on
 * (user_id, product_id) — see the migration — is the source of truth for
 * "is this product wished?"; the model is intentionally thin.
 */
class Wishlist extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
