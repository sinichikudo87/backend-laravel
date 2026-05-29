<?php

namespace App\Http\Controllers\kpi\reporting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportKpiController extends Controller
{
    public function show(Request $request)
    {
        try {

            // $userId = $request->input('user_id');
            // $departmentId = $request->input('department_id');

            $userId = 10;
            $departmentId = 9;

            /*
            |--------------------------------------------------------------------------
            | VALIDATION
            |--------------------------------------------------------------------------
            */

            // if (is_null($userId) && is_null($departmentId)) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'user_id atau department_id wajib diisi'
            //     ], 422);
            // }

            /*
            |--------------------------------------------------------------------------
            | CALL PROCEDURE
            |--------------------------------------------------------------------------
            */

            $data = DB::connection('kpi')->select(
                'CALL sp_report_kpi_xx26(?, ?)',
                [
                    $userId,
                    $departmentId
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | RESPONSE
            |--------------------------------------------------------------------------
            */
dd($data);
            return response()->json([
                'success' => true,
                'message' => 'Data report KPI berhasil diambil',
                'total' => count($data),
                'data' => $data
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage()
            ], 500);

        }
    }
}