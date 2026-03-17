<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyAggregate extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'date',
        'hour',
        'page_url',
        'event_type',
        'event_count',
        'unique_sessions',
        'avg_duration_sec',
        'product_id',
        'bounce_count',
        'new_visitors',
        'updated_at',
    ];

    protected $casts = [
        'date'             => 'date',
        'hour'             => 'integer',
        'event_count'      => 'integer',
        'unique_sessions'  => 'integer',
        'avg_duration_sec' => 'float',
        'bounce_count'     => 'integer',
        'new_visitors'     => 'integer',
        'updated_at'       => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}