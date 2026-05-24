<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HmacAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Izinkan Preflight Request (CORS) - CRITICAL
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        $apiKey = $request->header('X-API-KEY');
        $timestamp = $request->header('X-TIMESTAMP');
        $signature = $request->header('X-SIGNATURE');

        // Debugging Headers jika masih error 'Missing headers'
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

        // 2. Cek Expiry (5 Menit)
        if (abs(time() - (int)$timestamp) > 300) {
            return response()->json(['message' => 'Request expired'], 401);
        }

        // 3. Ambil Secret dari ENV
        $secret = env('NEXT_PUBLIC_API_SECRET_KEY');

        // 4. Konstruksi Payload - HARUS SAMA PERSIS DENGAN NEXT.JS
        $method = strtoupper($request->getMethod()); // Pastikan "GET", "POST", dll
        
        /**
         * PERBAIKAN URI:
         * getRequestUri() menyertakan query string (?page=1). 
         * Jika Next.js hanya menggunakan path-nya saja, gunakan $request->getPathInfo()
         */
        $uri = str_replace('/api', '', $request->getPathInfo()); 
        
        /**
         * PERBAIKAN BODY:
         * getContent() bisa menghasilkan string kosong atau JSON.
         * Pastikan di Next.js jika GET, body adalah string kosong "".
         */
        $body = $request->getContent() ?: ""; 

        $payload = $method . $uri . $body . $timestamp;

        // 5. Kalkulasi Signature
        $calculatedSignature = hash_hmac('sha256', $payload, $secret);

        // 6. Verifikasi
        if (!hash_equals($calculatedSignature, $signature)) {
            Log::error("HMAC Mismatch!", [
                'payload_string' => $payload,
                'expected' => $calculatedSignature,
                'received' => $signature,
                'client_ip' => $request->ip()
            ]);
            
            return response()->json([
                'message' => 'Invalid signature',
                // Aktifkan baris di bawah hanya saat TESTING untuk bandingkan string
                // 'debug_payload_server' => $payload 
            ], 401);
        }

        return $next($request);
    }
}