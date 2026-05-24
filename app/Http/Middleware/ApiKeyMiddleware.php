<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY');

        if (!$apiKey || $apiKey !== env('PUBLIC_API_KEY')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized API Key'
            ], 401);
        }

        return $next($request);
    }
}