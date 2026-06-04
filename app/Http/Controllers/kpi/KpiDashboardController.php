<?php

namespace App\Http\Controllers\kpi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KpiDashboardController extends Controller
{
    public function show(Request $request)
    {
        try {
            $companyId = $request->input('company_id');
            $userId    = $request->input('user_id');
            $month     = $request->input('month');
            $year      = $request->input('year');

            // CALL PROCEDURE
            $rawData = DB::connection('crm')->select(
                "CALL sp_kpi_summary_xx26(?, ?, ?, ?)",
                [$companyId, $userId, $month, $year]
            );

            // =========================
            // SUMMARY CALCULATION
            // =========================
            $totalKpi = count($rawData);

            $totalScore = 0;
            $totalFinal = 0;
            $totalEntries = 0;

            $activityDataMap = [];

            $recentActivities = [];

            foreach ($rawData as $row) {

                $totalScore += (float) $row->base_score;
                $totalFinal += (float) $row->final_score;
                $totalEntries += (int) $row->total_entries;

                if (!empty($row->last_activity_date)) {
                    $monthNum = (int) date('n', strtotime($row->last_activity_date));

                    if (!isset($activityDataMap[$monthNum])) {
                        $activityDataMap[$monthNum] = 0;
                    }

                    $activityDataMap[$monthNum] += (float) $row->final_score;
                }

                if (!empty($row->kpi_name)) {
                    $recentActivities[] = [
                        'title' => $row->job_title . ' - ' . $row->kpi_name,
                        'time'  => $row->last_activity_date
                    ];
                }
            }

            // =========================
            // MONTH FORMAT (12 BULAN)
            // =========================
            $monthNames = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
                7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
            ];

            $activityData = [];
            for ($i = 1; $i <= 12; $i++) {
                $activityData[] = [
                    'month' => $monthNames[$i],
                    'value' => $activityDataMap[$i] ?? 0
                ];
            }

            // =========================
            // RESPONSE
            // =========================
            return response()->json([
                'success' => true,
                'message' => 'Data KPI summary berhasil diambil',
                'data' => [
                    'summary' => [
                        'total_kpi'      => $totalKpi,
                        'total_score'    => $totalScore,
                        'final_score'    => $totalFinal,
                        'total_entries'  => $totalEntries,
                        'avg_score'      => $totalKpi > 0 ? round($totalFinal / $totalKpi, 2) : 0,
                    ],

                    'detail' => $rawData,

                    'activityData' => $activityData,

                    'recentActivities' => array_slice(array_map(function ($item) {
                        return [
                            'title' => $item['title'],
                            'time'  => !empty($item['time'])
                                ? Carbon::parse($item['time'])->diffForHumans()
                                : '-'
                        ];
                    }, $recentActivities), 0, 5)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil KPI summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}