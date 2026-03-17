<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsSession;
use App\Models\DailyAggregate;
use App\Models\Event;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsDashboardController extends Controller
{
    public function overview()
    {
        $today     = now()->toDateString();
        $thisWeek  = now()->subDays(7)->toDateString();
        $thisMonth = now()->subDays(30)->toDateString();

        return response()->json([
            'today' => [
                'pageviews'       => Event::where('event_type', 'pageview')->whereDate('created_at', $today)->count(),
                'unique_sessions' => AnalyticsSession::whereDate('started_at', $today)->count(),
                'add_to_carts'    => Event::where('event_type', 'add_to_cart')->whereDate('created_at', $today)->count(),
            ],
            'this_week' => [
                'pageviews'       => Event::where('event_type', 'pageview')->where('created_at', '>=', $thisWeek)->count(),
                'unique_sessions' => AnalyticsSession::where('started_at', '>=', $thisWeek)->count(),
                'new_visitors'    => AnalyticsSession::where('started_at', '>=', $thisWeek)->where('is_new_visitor', true)->count(),
            ],
            'this_month' => [
                'pageviews'       => Event::where('event_type', 'pageview')->where('created_at', '>=', $thisMonth)->count(),
                'unique_sessions' => AnalyticsSession::where('started_at', '>=', $thisMonth)->count(),
            ],
        ]);
    }

    public function hourly()
    {
        $data = Event::select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as event_count')
            )
            ->where('event_type', 'pageview')
            ->whereDate('created_at', now()->toDateString())
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $hours = array_fill(0, 24, 0);
        foreach ($data as $row) {
            $hours[$row->hour] = $row->event_count;
        }

        return response()->json([
            'labels' => array_keys($hours),
            'data'   => array_values($hours),
        ]);
    }

    public function topProducts()
    {
        $products = Event::select(
                'product_id',
                DB::raw('COUNT(*) as view_count')
            )
            ->where('event_type', 'pageview')
            ->whereNotNull('product_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('product_id')
            ->orderByDesc('view_count')
            ->limit(10)
            ->with('product:id,name,brand,price')
            ->get();

        return response()->json($products);
    }

    public function realtime()
    {
        $activeMinutes = 5;
        $since         = now()->subMinutes($activeMinutes);

        $activeSessions = AnalyticsSession::where('last_seen_at', '>=', $since)->count();

        $recentEvents = Event::where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'active_sessions' => $activeSessions,
            'recent_events'   => $recentEvents,
        ]);
    }
}