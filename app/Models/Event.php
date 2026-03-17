<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'session_id',
        'product_id',
        'event_type',
        'page_url',
        'element_selector',
        'duration_seconds',
        'meta',
        'ip_address',
    ];

    protected $casts = [
        'meta'             => 'array',
        'duration_seconds' => 'integer',
        'created_at'       => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(AnalyticsSession::class, 'session_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}