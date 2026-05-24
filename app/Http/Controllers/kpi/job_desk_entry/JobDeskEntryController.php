<?php

namespace App\Http\Controllers\kpi\job_desk_entry;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;

class JobDeskEntryController extends Controller
{
    public function show($id)
    {
        try {
            $companyId = 1; 
            $periodMonth = 5; 
            $periodYear = 2026;

            $kpiResult = DB::connection('kpi')->select(
                "CALL sp_get_user_jobdesk_kpi_xx26(?, ?, ?)",
                [$companyId, $periodMonth, $periodYear]
            );

            if (empty($kpiResult)) {
                return response()->json([
                    'success' => false,
                    'message' => 'KPI data not found in the database (empty array)'
                ], 404);
            }

            $jobDesks = [];
            foreach ($kpiResult as $row) {                
                $rawData = array_change_key_case((array) $row, CASE_LOWER);
                
                $userId = isset($rawData['user_id']) ? (int)$rawData['user_id'] : 0;
                $userName = 'Not Found / Database Mismatch';

                if ($userId > 0) {
                    $userResult = DB::connection('mysql')->select(
                        "CALL sp_get_user_by_id_xx25(?)",
                        [$userId]
                    );

                    if (!empty($userResult)) {
                        // Paksa juga key hasil database user menjadi lowercase
                        $userData = array_change_key_case((array) $userResult[0], CASE_LOWER);
                        $userName = $userData['name'] ?? '-';
                    }
                }
                
                $jobDesks[] = [
                    'user_jobdesk_id'   => isset($rawData['user_jobdesk_id']) ? (int)$rawData['user_jobdesk_id'] : 0,
                    'company_id'        => isset($rawData['company_id']) ? (int)$rawData['company_id'] : 0,
                    'user_id'           => $userId,
                    'user_name'         => $userName, 
                    'jobdesk_master_id' => isset($rawData['jobdesk_master_id']) ? (int)$rawData['jobdesk_master_id'] : 0,
                    'job_title'         => $rawData['job_title'] ?? '-',
                    'kpi_name'          => $rawData['kpi_name'] ?? '-',
                    'weight'            => isset($rawData['weight']) ? (int)$rawData['weight'] : 0,
                    'target_value'      => $rawData['target_value'] ?? '-',
                    'actual_value'      => $rawData['actual_value'] ?? '-',
                    'score'             => isset($rawData['score']) ? (float)$rawData['score'] : 0.00,
                    'period_month'      => isset($rawData['period_month']) ? (int)$rawData['period_month'] : 0,
                    'period_year'       => isset($rawData['period_year']) ? (int)$rawData['period_year'] : 0,
                    'status'            => $rawData['status'] ?? 'PENDING',
                    'notes'             => $rawData['notes'] ?? '-',
                ];
            }

            return response()->json([
                'success' => true,
                'data'    => $jobDesks
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error encountered during cross-database data mapping',
                'error'   => $e->getMessage() 
            ], 500);
        }
    }
}