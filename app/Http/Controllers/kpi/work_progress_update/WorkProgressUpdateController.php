<?php

namespace App\Http\Controllers\kpi\work_progress_update;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator; // 🌟 PENTING: Wajib di-import agar fungsi Validator::make() aktif

class WorkProgressUpdateController extends Controller
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

    public function store(Request $request)
    {        
        $validator = Validator::make($request->all(), [
            'user_jobdesk_kpi_id'    => 'required|integer',
            'date'                   => 'required|date_format:Y-m-d',
            'actual_value_submitted' => 'required|string|max:100',
            'score_impact'           => 'required|numeric',
            'notes'                  => 'nullable|string',
            'attachment_url'         => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal, mohon cek kembali inputan Anda.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $userJobdeskKpiId     = (int) $request->input('user_jobdesk_kpi_id');
            $date                 = $request->input('date');
            $actualValueSubmitted = (string) $request->input('actual_value_submitted');
            $scoreImpact          = (float) $request->input('score_impact', 0.00);
            $notes                = $request->input('notes') ? trim($request->input('notes')) : null;
            $attachmentUrl        = $request->input('attachment_url') ? trim($request->input('attachment_url')) : null;

            DB::connection('kpi')->statement(
                "CALL sp_upsert_daily_progress_log_xx26(:id, :log_date, :actual, :score, :notes, :url)",
                [
                    'id'       => $userJobdeskKpiId,
                    'log_date' => $date,
                    'actual'   => $actualValueSubmitted,
                    'score'    => $scoreImpact,
                    'notes'    => $notes,
                    'url'      => $attachmentUrl,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Catatan progress log harian berhasil disimpan ke database.',
                'data'    => [
                    'user_jobdesk_kpi_id' => $userJobdeskKpiId,
                    'date'                => $date
                ]
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengeksekusi Stored Procedure (Server Error)',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function getLogs($user_jobdesk_kpi_id)
    {
        try {
            /*
            |--------------------------------------------------------------------------
            | 🌟 CALL STORED PROCEDURE FETCH DATA VIA DB::SELECT 🌟
            |--------------------------------------------------------------------------
            */
            $logs = DB::connection('kpi')->select(
                "CALL sp_get_daily_progress_logs_xx26(:id)",
                [
                    'id' => (int) $user_jobdesk_kpi_id
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'History progress log berhasil diambil via Stored Procedure.',
                'total'   => count($logs),
                'data'    => $logs
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data history log melalui server',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}