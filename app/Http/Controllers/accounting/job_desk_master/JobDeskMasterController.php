<?php

namespace App\Http\Controllers\kpi\job_desk_master;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;

class JobDeskMasterController extends Controller
{
    public function show($id)
    {
        try {     
            $companyId = 1; 
            $departmentId = 1;

            $result = DB::connection('kpi')->select(
                "CALL sp_get_jobdesk_kpi_by_company_id_and_department_id_xx26(?, ?)",
                [$companyId, $departmentId]
            );

            if (empty($result)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data ora ketemu ndek database (array kosong)'
                ], 404);
            }

            $jobDesks = [];
            foreach ($result as $row) {
                $rawData = (array) $row;
                
                $jobDesks[] = [
                    'id'               => isset($rawData['id']) ? (int)$rawData['id'] : 0,
                    'job_title'        => $rawData['job_title'] ?? $rawData['JOB_TITLE'] ?? '-',
                    'department'       => $rawData['department'] ?? $rawData['DEPARTMENT'] ?? '-',
                    'kpi_name'         => $rawData['kpi_name'] ?? $rawData['KPI_NAME'] ?? '-',
                    'target_indicator' => $rawData['target_indicator'] ?? $rawData['TARGET_INDICATOR'] ?? '-',
                    'weight'           => isset($rawData['weight']) ? (int)$rawData['weight'] : 0,
                    'is_active'        => isset($rawData['is_active']) ? (int)$rawData['is_active'] : 1,
                ];
            }

            return response()->json([
                'success' => true,
                'data'    => $jobDesks
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server Error pas mapping data',
                'error'   => $e->getMessage() 
            ], 500);
        }
    }
}