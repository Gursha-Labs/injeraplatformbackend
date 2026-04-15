<?php

namespace App\Http\Controllers;

use App\Models\ApiLog;
use Illuminate\Http\Request;

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
