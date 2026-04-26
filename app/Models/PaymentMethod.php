<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Saved payment method.
 *
 * See the migration's docblock for the full PCI-scope discussion. The model
 * intentionally exposes ONLY display metadata fields — there is no $fillable
 * for `card_number`, `cvv`, or any other PCI-restricted field, so even an
 * upstream typo can't accidentally persist them.
 */
class PaymentMethod extends Model
{
    protected $fillable = [
        'user_id',
        'brand',
        'last_four',
        'exp_month',
        'exp_year',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'exp_month'  => 'integer',
        'exp_year'   => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
