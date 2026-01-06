<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserBlocked
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($user->is_blocking === true) {
            return response()->json([
                'error' => 'Account blocked',
                'message' => 'Contact support'
            ], 403);
        }

        return $next($request);
    }
}
