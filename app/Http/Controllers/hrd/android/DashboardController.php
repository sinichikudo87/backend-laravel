<?php

namespace App\Http\Controllers\hrd\android;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class DashboardController extends Controller
{
    public function view(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'limit'   => 'nullable|integer|min:1|max:100',
            'offset'  => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $userId = $request->input('user_id');
            $limit  = $request->input('limit', 10);
            $offset = $request->input('offset', 0);

            $attendances = DB::connection('hrd_android')->select(
                'CALL sp_get_attendance_by_user_xx26(?, ?, ?)', 
                [$userId, $limit, $offset]
            );

            $summaryResult = DB::connection('hrd_android')->select(
                'CALL sp_get_summary_user_xx26(?)', 
                [$userId]
            );

            return response()->json([
                'success' => true,
                'message' => 'Riwayat absensi berhasil diambil',
                'data'    => $attendances,
                'summary' => $summaryResult
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server hrd_android',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}