<?php

namespace App\Http\Controllers\hrd\android\attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{    
    public function store(Request $request)
    {        
        $validated = $request->validate([
            'user_id'               => 'required|integer',
            'type'                  => ['required', Rule::in(['in', 'out'])],
            'latitude'              => 'required|numeric',
            'longitude'             => 'required|numeric',
            'device_info'           => 'nullable|string|max:255',
            'verified_by_biometric' => 'required|integer|in:0,1',
            'biometric_log_id'      => 'nullable|integer',
        ]);

        try {
            DB::connection('hrd_android')->select(
                'CALL sp_insert_attendance_xx26(?, ?, ?, ?, ?, ?, ?)',
                [
                    $validated['user_id'],
                    $validated['type'],
                    $validated['latitude'],
                    $validated['longitude'],
                    $validated['device_info'] ?? 'Unknown Android Device',
                    $validated['verified_by_biometric'],
                    $validated['biometric_log_id'] ?? null
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Presensi ' . ($validated['type'] == 'in' ? 'Masuk' : 'Pulang') . ' berhasil dicatat.'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Attendance Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses absensi. Silakan coba lagi nanti.',
                'error'   => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}