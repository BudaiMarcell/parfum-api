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

        $ip      = $request->ip();
        $userId  = $request->user()?->id;

        // A visitor is "new" only if we have no prior session for this IP or user
        $isNew = !AnalyticsSession::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(!$userId, fn ($q) => $q->where('ip_address', $ip))
            ->exists();

        $session = AnalyticsSession::firstOrCreate(
            ['id' => $validated['session_id']],
            [
                'user_id'        => $userId,
                'ip_address'     => $ip,
                'user_agent'     => $request->userAgent(),
                'referrer'       => $request->header('referer'),
                'device_type'    => $this->detectDevice($request->userAgent()),
                'is_new_visitor' => $isNew,
                'started_at'     => now(),
                'last_seen_at'   => now(),
            ]
        );

        // `time_spent` fires on page-hide / visibility-hidden — i.e. the user is
        // LEAVING, not active. Don't bump last_seen_at for these, so "aktív most"
        // correctly drops the user off after the 2-minute window. Heartbeats
        // (ping endpoint) handle the "still here" case instead.
        if ($validated['event_type'] !== 'time_spent') {
            $session->update(['last_seen_at' => now()]);
        }

        Event::create([
            'session_id'       => $session->id,
            'product_id'       => $validated['product_id'] ?? null,
            'event_type'       => $validated['event_type'],
            'page_url'         => $validated['page_url'],
            'element_selector' => $validated['element_selector'] ?? null,
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'meta'             => $validated['meta'] ?? null,
            'ip_address'       => $ip,
        ]);

        return response()->json(['message' => 'Event recorded.'], 201);
    }

    /**
     * Lightweight heartbeat — bumps `last_seen_at` on an existing session
     * without creating a new event row. Used by the frontend to keep a session
     * in the "active now" bucket while the page is visible.
     */
    public function ping(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string|max:64',
        ]);

        // Only update if the session exists — avoids creating empty sessions
        // from stale/forged ids. Returns 204 whether or not an update happened.
        AnalyticsSession::where('id', $validated['session_id'])
            ->update(['last_seen_at' => now()]);

        return response()->noContent();
    }

    private function detectDevice(?string $userAgent): string
    {
        if (!$userAgent) return 'desktop';

        $ua = strtolower($userAgent);

        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'tablet';
        }

        if (preg_match('/mobile|android|iphone|ipod/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }
}
