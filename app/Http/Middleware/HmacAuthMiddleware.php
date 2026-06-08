<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HmacAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        $apiKey = $request->header('X-API-KEY');
        $timestamp = $request->header('X-TIMESTAMP');
        $signature = $request->header('X-SIGNATURE');
        
        if (!$apiKey || !$timestamp || !$signature) {
            return response()->json([
                'message' => 'Missing headers',
                'debug' => [
                    'api_key' => $apiKey ? 'present' : 'missing',
                    'timestamp' => $timestamp ? 'present' : 'missing',
                    'signature' => $signature ? 'present' : 'missing'
                ]
            ], 401);
        }
        
        if (abs(time() - (int)$timestamp) > 300) {
            return response()->json(['message' => 'Request expired'], 401);
        }

        $secret = env('NEXT_PUBLIC_API_SECRET_KEY');
        $method = strtoupper($request->getMethod()); 
        $uri = str_replace('/api', '', $request->getPathInfo());         
        $contentType = $request->header('Content-Type');
        $isMultipart = $contentType && str_contains(strtolower($contentType), 'multipart/form-data');

        if ($isMultipart) {
            $body = "";
        } else {
            $body = $request->getContent() ?: ""; 
        }

        $payload = $method . $uri . $body . $timestamp;

        $calculatedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($calculatedSignature, $signature)) {
            Log::error("HMAC Mismatch!", [
                'payload_string' => $payload,
                'expected' => $calculatedSignature,
                'received' => $signature,
                'client_ip' => $request->ip()
            ]);
            
            return response()->json([
                'message' => 'Invalid signature',
                // untuk debugging string payload di Postman/Log Studio
                // 'debug_payload_server' => $payload 
            ], 401);
        }

        return $next($request);
    }
}