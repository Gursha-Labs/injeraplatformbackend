<?php

namespace App\Http\Controllers;

use App\Models\ApiLog;
use App\Models\AdVideo;
use App\Models\AdView;
use App\Models\AdComment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ApiAnalyticsController extends Controller
{
    public function overview()
    {
        return response()->json([
            'total_requests' => ApiLog::count(),
            'today_requests' => ApiLog::whereDate('created_at', now()->toDateString())->count(),
            'error_requests' => ApiLog::where('status_code', '>=', 400)->count(),
            'avg_response_time' => round(ApiLog::avg('response_time'), 4),
        ]);
    }

    public function topEndpoints()
    {
        $data = ApiLog::select('endpoint')
            ->selectRaw('COUNT(*) as total_requests')
            ->groupBy('endpoint')
            ->orderByDesc('total_requests')
            ->limit(10)
            ->get();

        return response()->json($data);
    }

    public function topEndpointsWithMethod()
    {
        $data = ApiLog::select('endpoint', 'method')
            ->selectRaw('COUNT(*) as total_requests')
            ->groupBy('endpoint', 'method')
            ->orderByDesc('total_requests')
            ->get();

        return response()->json($data);
    }

    public function trafficPerDay()
    {
        $data = ApiLog::selectRaw('DATE(created_at) as date, COUNT(*) as total_requests')
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        return response()->json($data);
    }

    public function avgResponseTimePerEndpoint()
    {
        $data = ApiLog::select('endpoint')
            ->selectRaw('AVG(response_time) as avg_time')
            ->groupBy('endpoint')
            ->orderByDesc('avg_time')
            ->get();

        return response()->json($data);
    }

    public function adertiser_analysis(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($user->type !== 'advertiser') {
            return response()->json(['error' => 'Access denied. Advertiser only.'], 403);
        }

        $advertiserId = $user->id;

        $totalAds = AdVideo::where('advertiser_id', $advertiserId)->count();

        $totalViews = AdView::whereHas('ad', function ($q) use ($advertiserId) {
            $q->where('advertiser_id', $advertiserId);
        })->count();

        $totalComments = AdComment::whereHas('adVideo', function ($q) use ($advertiserId) {
            $q->where('advertiser_id', $advertiserId);
        })->count();

        $totalOrders = Order::whereHas('adVideo', function ($q) use ($advertiserId) {
            $q->where('advertiser_id', $advertiserId);
        })->count();

        $totalRevenue = (float) Order::whereHas('adVideo', function ($q) use ($advertiserId) {
            $q->where('advertiser_id', $advertiserId);
        })->sum('total_price');

        // 30-day series (configurable via ?days=)
        $days = (int) $request->query('days', 30);
        if ($days < 1) $days = 30;

        $start = Carbon::now()->subDays($days - 1)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $viewsQuery = AdView::whereHas('ad', function ($q) use ($advertiserId) {
            $q->where('advertiser_id', $advertiserId);
        })->whereBetween('viewed_at', [$start, $end])
            ->selectRaw("DATE(viewed_at) as day, COUNT(*) as total_views")
            ->groupBy('day')
            ->pluck('total_views', 'day')
            ->toArray();

        $commentsQuery = AdComment::whereHas('adVideo', function ($q) use ($advertiserId) {
            $q->where('advertiser_id', $advertiserId);
        })->whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE(created_at) as day, COUNT(*) as total_comments")
            ->groupBy('day')
            ->pluck('total_comments', 'day')
            ->toArray();

        $ordersQuery = Order::whereHas('adVideo', function ($q) use ($advertiserId) {
            $q->where('advertiser_id', $advertiserId);
        })->whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE(created_at) as day, COUNT(*) as total_orders, COALESCE(SUM(total_price),0) as total_revenue")
            ->groupBy('day')
            ->get();

        $ordersCountByDay = $ordersQuery->pluck('total_orders', 'day')->toArray();
        $revenueByDay = $ordersQuery->pluck('total_revenue', 'day')->toArray();

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::now()->subDays($days - 1 - $i)->format('Y-m-d');

            $series[] = [
                'date' => $date,
                'views' => (int) ($viewsQuery[$date] ?? 0),
                'comments' => (int) ($commentsQuery[$date] ?? 0),
                'orders' => (int) ($ordersCountByDay[$date] ?? 0),
                'revenue' => round((float) ($revenueByDay[$date] ?? 0), 2),
            ];
        }

        return response()->json([
            'scope' => 'advertiser',
            'advertiser_id' => $advertiserId,
            'total_ads' => $totalAds,
            'total_views' => $totalViews,
            'total_comments' => $totalComments,
            'total_orders' => $totalOrders,
            'total_revenue' => round($totalRevenue, 2),
            'series' => $series,
        ]);
    }

    public function errorRate()
    {
        $total = ApiLog::count();
        $errors = ApiLog::where('status_code', '>=', 400)->count();

        return response()->json([
            'total_requests' => $total,
            'error_requests' => $errors,
            'error_rate_percent' => $total > 0 ? round(($errors / $total) * 100, 2) : 0,
        ]);
    }

    public function slowEndpoints()
    {
        $data = ApiLog::select('endpoint')
            ->selectRaw('AVG(response_time) as avg_time')
            ->groupBy('endpoint')
            ->orderByDesc('avg_time')
            ->limit(10)
            ->get();

        return response()->json($data);
    }
}
