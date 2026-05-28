<?php

namespace App\Http\Controllers\kpi\job_desk_entry;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class JobDeskEntryController extends Controller
{
    public function show($id)
    {
        try {
            $companyId = 1;
            $departmentId = 9;

            $kpiResult = DB::connection('kpi')->select(
                "CALL sp_get_jobdesk_kpi_by_company_id_and_department_id_xx26(?, ?)",
                [$companyId, $departmentId]
            );

            $jobDesks = [];
            foreach ($kpiResult as $row) {
                $raw = (array) $row;

                $jobDesks[] = [
                    'id'               => (int) ($raw['id'] ?? 0),
                    'company_id'       => $companyId,
                    'job_title'        => $raw['job_title'] ?? '-',
                    'department'       => $raw['department'] ?? '-',
                    'kpi_name'         => $raw['kpi_name'] ?? '-',
                    'target_indicator' => $raw['target_indicator'] ?? '-',
                    'weight'           => (int) ($raw['weight'] ?? 0),
                    'is_active'        => (int) ($raw['is_active'] ?? 1),
                ];
            }

            $userResult = DB::connection('mysql')->select(
                "CALL sp_get_user_by_company_id_and_division_id_xx25(?, ?)",
                [$companyId, $departmentId]
            );

            $users = [];
            foreach ($userResult as $row) {
                $raw = (array) $row;

                $users[] = [
                    'id'          => (int) $raw['id'],
                    'company_id'  => (int) $raw['id_companies_dash'],
                    'division_id' => (int) ($raw['id_division'] ?? 0),
                    'name'        => $raw['name'] ?? '-',
                    'email'       => $raw['email'] ?? '-',
                    'telepon'     => $raw['telepon'] ?? '-',
                    'is_active'   => (int) ($raw['is_active'] ?? 1),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'job_desks' => $jobDesks,
                    'users' => $users
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $logs = $request->input('logs', []);

        if (empty($logs)) {
            return response()->json([
                'success' => false,
                'message' => 'No data provided'
            ], 400);
        }

        try {
            DB::connection('kpi')->beginTransaction();

            foreach ($logs as $log) {
                DB::connection('kpi')->select(
                    "CALL sp_upsert_user_jobdesk_kpi_xx26(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $log['company_id'],
                        $log['user_id'],
                        $log['jobdesk_master_id'],
                        (int) $log['target_value'],
                        (int) $log['actual_value_submitted'],
                        $log['score_impact'],
                        $log['period_month'],
                        $log['period_year'],
                        'IN_PROGRESS',
                        $log['notes'] ?? null
                    ]
                );
            }

            DB::connection('kpi')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Saved successfully'
            ]);

        } catch (\Exception $e) {
            DB::connection('kpi')->rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}