<?php

namespace App\Http\Controllers\kpi\job_desk_master;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Exception;

class JobDeskMasterController extends Controller
{
    public function show($id)
    {
        try {     
            $companyId = 1; 
            $departmentId = 9;

            $departmentResult = DB::connection('mysql')->select(
                "CALL GetAllDivisions_xx25()"                
            );

            $result = DB::connection('kpi')->select(
                "CALL sp_get_jobdesk_kpi_by_company_id_and_department_id_xx26(?, ?)",
                [$companyId, $departmentId]
            );

            if (empty($result)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ada !!'
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
                'departments' => $departmentResult,
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

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'               => 'nullable|integer',
            'company_id'       => 'required|integer',
            'job_title'        => 'required|string|max:150',
            'department_id'    => 'required|integer',
            'kpi_name'         => 'required|string|max:255',
            'target_indicator' => 'required|string',
            'weight'           => 'required|integer|min:0|max:100',
            'is_active'        => 'nullable|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal, mohon cek kembali inputan Anda.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $id              = $request->input('id', 0);
            $companyId       = $request->input('company_id');
            $jobTitle        = $request->input('job_title');
            $departmentId    = $request->input('department_id');
            $kpiName         = $request->input('kpi_name');
            $targetIndicator = $request->input('target_indicator');
            $weight          = $request->input('weight');
            $isActive        = $request->input('is_active', 1);

            $result = DB::connection('kpi')->select(
                "CALL sp_upsert_master_jobdesk_kpi_xx26(?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $id,
                    $companyId,
                    $jobTitle,
                    $departmentId,
                    $kpiName,
                    $targetIndicator,
                    $weight,
                    $isActive
                ]
            );

            $dbResponse = !empty($result) ? (array) $result[0] : null;

            $operation = $dbResponse['operation_type'] ?? 'UNKNOWN';
            $affectedId = $dbResponse['affected_id'] ?? $id;

            return response()->json([
                'success' => true,
                'message' => $operation === 'INSERT' 
                    ? 'Data master job desk baru berhasil ditambahkan.' 
                    : 'Data master job desk berhasil diperbarui.',
                'data' => [
                    'id'             => (int) $affectedId,
                    'operation_type' => $operation
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses data ke database (Server Error)',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}