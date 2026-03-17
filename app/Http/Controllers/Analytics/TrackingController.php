<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsSession;
use App\Models\Event;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'session_id'       => 'required|string|max:64',
            'event_type'       => 'required|in:pageview,click,time_spent,add_to_cart,remove_from_cart,checkout',
            'page_url'         => 'required|string|max:500',
            'element_selector' => 'nullable|string|max:255',
            'duration_seconds' => 'nullable|integer|min:0',
            'product_id'       => 'nullable|exists:products,id',
            'meta'             => 'nullable|array',
        ]);

        $session = AnalyticsSession::firstOrCreate(
            ['id' => $validated['session_id']],
            [
                'user_id'        => $request->user()?->id,
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
                'referrer'       => $request->header('referer'),
                'device_type'    => $this->detectDevice($request->userAgent()),
                'is_new_visitor' => true,
                'started_at'     => now(),
            ]
        );

        $session->update(['last_seen_at' => now()]);

        Event::create([
            'session_id'       => $session->id,
            'product_id'       => $validated['product_id'] ?? null,
            'event_type'       => $validated['event_type'],
            'page_url'         => $validated['page_url'],
            'element_selector' => $validated['element_selector'] ?? null,
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'meta'             => $validated['meta'] ?? null,
            'ip_address'       => $request->ip(),
        ]);

        return response()->json(['message' => 'Event recorded.'], 201);
    }

    private function detectDevice(?string $userAgent): string
    {
        if (!$userAgent) return 'desktop';

        if (preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent)) {
            return str_contains(strtolower($userAgent), 'ipad') ? 'tablet' : 'mobile';
        }

        return 'desktop';
    }
}