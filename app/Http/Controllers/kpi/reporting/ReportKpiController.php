<?php

namespace App\Http\Controllers\kpi\reporting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportKpiController extends Controller
{
    public function show($user_id, $department_id)
{
    try {

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if (
            empty($user_id) ||
            empty($department_id)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'user_id dan department_id wajib diisi'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | CALL PROCEDURE
        |--------------------------------------------------------------------------
        */

        $data = DB::connection('kpi')->select(
            'CALL sp_report_kpi_xx26(?, ?)',
            [
                $user_id,
                $department_id
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

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