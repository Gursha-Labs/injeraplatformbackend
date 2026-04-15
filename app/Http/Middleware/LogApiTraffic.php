<?php

namespace App\Http\Middleware;

use App\Models\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiTraffic
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $startTime = microtime(true);
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;
        ApiLog::create([
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'status_code' => $response->getStatusCode(),
            'response_time' => $responseTime,
        ]);
        return $response;
    }
}
