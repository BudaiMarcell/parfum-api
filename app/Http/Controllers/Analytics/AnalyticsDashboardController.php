<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsSession;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsDashboardController extends Controller
{
    /**
     * High-level KPIs — today / last 7 days / last 30 days.
     * Cached for 60s since it's called often and runs many COUNTs.
     */
    public function overview()
    {
        return Cache::remember('analytics.overview', 60, function () {
            $today     = now()->toDateString();
            $weekAgo   = now()->subDays(7);
            $monthAgo  = now()->subDays(30);

            // "Unique visitors" = distinct people per period. A person is
            // fingerprinted by logged-in user_id when available, otherwise by
            // IP + device_type — so the same laptop visiting twice in one day
            // counts once, but the same IP from a phone counts as a second
            // visitor. Matches the product requirement: "one person opens the
            // page once, that counts as one visitor, even when navigating
            // around". Sessions (tab-lifecycles) can be higher than visitors.
            $visitorFingerprint = DB::raw(
                "COUNT(DISTINCT COALESCE(CAST(user_id AS CHAR), CONCAT(ip_address, '|', device_type)))"
            );

            return response()->json([
                'today' => [
                    'pageviews'        => Event::where('event_type', 'pageview')->whereDate('created_at', $today)->count(),
                    'unique_sessions'  => AnalyticsSession::whereDate('started_at', $today)->count(),
                    'unique_visitors'  => (int) AnalyticsSession::whereDate('started_at', $today)->value($visitorFingerprint),
                    'add_to_carts'     => Event::where('event_type', 'add_to_cart')->whereDate('created_at', $today)->count(),
                    'checkouts'        => Event::where('event_type', 'checkout')->whereDate('created_at', $today)->count(),
                    'orders'           => Order::whereDate('created_at', $today)->count(),
                    'revenue'          => (float) Order::whereDate('created_at', $today)
                        ->whereIn('status', ['paid', 'shipped', 'delivered', 'completed'])
                        ->sum('total_amount'),
                ],
                'this_week' => [
                    'pageviews'        => Event::where('event_type', 'pageview')->where('created_at', '>=', $weekAgo)->count(),
                    'unique_sessions'  => AnalyticsSession::where('started_at', '>=', $weekAgo)->count(),
                    'unique_visitors'  => (int) AnalyticsSession::where('started_at', '>=', $weekAgo)->value($visitorFingerprint),
                    'new_visitors'     => AnalyticsSession::where('started_at', '>=', $weekAgo)->where('is_new_visitor', true)->count(),
                    'orders'           => Order::where('created_at', '>=', $weekAgo)->count(),
                    'revenue'          => (float) Order::where('created_at', '>=', $weekAgo)
                        ->whereIn('status', ['paid', 'shipped', 'delivered', 'completed'])
                        ->sum('total_amount'),
                ],
                'this_month' => [
                    'pageviews'        => Event::where('event_type', 'pageview')->where('created_at', '>=', $monthAgo)->count(),
                    'unique_sessions'  => AnalyticsSession::where('started_at', '>=', $monthAgo)->count(),
                    'unique_visitors'  => (int) AnalyticsSession::where('started_at', '>=', $monthAgo)->value($visitorFingerprint),
                    'new_visitors'     => AnalyticsSession::where('started_at', '>=', $monthAgo)->where('is_new_visitor', true)->count(),
                    'orders'           => Order::where('created_at', '>=', $monthAgo)->count(),
                    'revenue'          => (float) Order::where('created_at', '>=', $monthAgo)
                        ->whereIn('status', ['paid', 'shipped', 'delivered', 'completed'])
                        ->sum('total_amount'),
                ],
            ]);
        });
    }

    /**
     * 24-hour pageview breakdown for today.
     */
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
            $hours[$row->hour] = (int) $row->event_count;
        }

        return response()->json([
            'labels' => array_keys($hours),
            'data'   => array_values($hours),
        ]);
    }

    /**
     * Daily timeseries for the last N days (default 30).
     * Returns pageviews + unique sessions per day — ideal for line charts.
     */
    public function daily(Request $request)
    {
        $days  = min((int) $request->query('days', 30), 365);
        $since = now()->subDays($days - 1)->startOfDay();

        $pageviews = Event::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('event_type', 'pageview')
            ->where('created_at', '>=', $since)
            ->groupBy('date')
            ->pluck('count', 'date');

        $sessions = AnalyticsSession::select(
                DB::raw('DATE(started_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('started_at', '>=', $since)
            ->groupBy('date')
            ->pluck('count', 'date');

        $orders = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->where('created_at', '>=', $since)
            ->whereIn('status', ['paid', 'shipped', 'delivered', 'completed'])
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $labels  = [];
        $series  = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($days - 1 - $i)->toDateString();
            $labels[] = $date;
            $series[] = [
                'date'      => $date,
                'pageviews' => (int) ($pageviews[$date] ?? 0),
                'sessions'  => (int) ($sessions[$date] ?? 0),
                'orders'    => (int) ($orders[$date]->count ?? 0),
                'revenue'   => (float) ($orders[$date]->revenue ?? 0),
            ];
        }

        return response()->json([
            'labels' => $labels,
            'series' => $series,
        ]);
    }

    /**
     * Top-selling products in the last 30 days.
     *
     * Aggregates from order_items — summing quantity per product across all
     * orders whose status isn't 'canceled' or 'refunded'. Top-viewed was
     * replaced because the frontend has no per-product detail pages, so
     * pageview-level product attribution isn't meaningful there.
     */
    public function topProducts()
    {
        $since = now()->subDays(30);

        $products = OrderItem::select(
                'product_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(subtotal) as total_revenue')
            )
            ->whereNotNull('product_id')
            ->whereHas('order', function ($q) use ($since) {
                $q->where('created_at', '>=', $since)
                  ->whereNotIn('status', ['canceled', 'refunded']);
            })
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(10)
            // withTrashed(): a Product soft-delete-elt (SoftDeletes trait), és az
            // admin UI-n is látni akarjuk az eladott, de azóta törölt termékeket
            // — a WPF oldal "(törölve)" jelzéssel különbözteti meg őket.
            ->with(['product' => function ($q) {
                $q->withTrashed()
                  ->select('id', 'name', 'price', 'category_id', 'deleted_at')
                  ->with('category:id,name');
            }])
            ->get();

        return response()->json($products);
    }

    /**
     * Device breakdown (desktop / mobile / tablet) for the last 30 days.
     */
    public function devices()
    {
        $rows = AnalyticsSession::select('device_type', DB::raw('COUNT(*) as count'))
            ->where('started_at', '>=', now()->subDays(30))
            ->groupBy('device_type')
            ->pluck('count', 'device_type');

        return response()->json([
            'desktop' => (int) ($rows['desktop'] ?? 0),
            'mobile'  => (int) ($rows['mobile']  ?? 0),
            'tablet'  => (int) ($rows['tablet']  ?? 0),
        ]);
    }

    /**
     * Conversion funnel for the last 30 days:
     * pageview → add_to_cart → checkout → paid order.
     */
    public function funnel()
    {
        $since = now()->subDays(30);

        $pageviews     = Event::where('event_type', 'pageview')->where('created_at', '>=', $since)->count();
        $addToCarts    = Event::where('event_type', 'add_to_cart')->where('created_at', '>=', $since)->count();
        $checkouts     = Event::where('event_type', 'checkout')->where('created_at', '>=', $since)->count();
        $paidOrders    = Order::where('created_at', '>=', $since)
            ->whereIn('status', ['paid', 'shipped', 'delivered', 'completed'])
            ->count();

        return response()->json([
            'pageviews'    => $pageviews,
            'add_to_carts' => $addToCarts,
            'checkouts'    => $checkouts,
            'orders'       => $paidOrders,
        ]);
    }

    /**
     * Live view — active sessions (last 2 min) + recent events with context.
     *
     * The window is deliberately short (2 min) so that users who close the tab
     * drop out of "aktív most" quickly. The frontend sends a heartbeat every
     * ~30s while the page is visible, so genuinely-active users stay in the
     * bucket as long as the tab is open and focused.
     */
    public function realtime()
    {
        $since = now()->subMinutes(2);

        $activeSessions = AnalyticsSession::where('last_seen_at', '>=', $since)->count();

        $recentEvents = Event::with([
                'product:id,name',
                'session:id,device_type,country,user_id',
            ])
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        return response()->json([
            'active_sessions' => $activeSessions,
            'recent_events'   => $recentEvents,
        ]);
    }
}
