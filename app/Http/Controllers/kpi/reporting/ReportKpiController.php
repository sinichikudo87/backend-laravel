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
            $targetUserId = ($user_id === 'null' || empty($user_id)) ? null : $user_id;
            $targetDeptId = ($department_id === 'null' || empty($department_id)) ? null : $department_id;

            $data = DB::connection('kpi')->select(
                'CALL sp_report_kpi_xx26(?, ?)',
                [
                    $targetUserId,
                    $targetDeptId
                ]
            );

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