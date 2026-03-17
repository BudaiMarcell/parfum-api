<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsSession extends Model
{
    public $incrementing = false;
    public $timestamps   = false;
    protected $keyType   = 'string';
    protected $table     = 'analytics_sessions';

    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'referrer',
        'device_type',
        'country',
        'city',
        'is_new_visitor',
        'started_at',
        'last_seen_at',
    ];

    protected $casts = [
        'is_new_visitor' => 'boolean',
        'started_at'     => 'datetime',
        'last_seen_at'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'session_id');
    }
}