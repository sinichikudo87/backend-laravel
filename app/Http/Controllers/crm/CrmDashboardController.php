<?php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CrmDashboardController extends Controller
{
    public function show(Request $request, $id = null)
    {
        try {
            $rawData = DB::connection('crm')->select("CALL sp_summary_tender_xx26()");
            
            $customersList = [];
            $leadsCount = 0;
            $dealsCount = 0;
            
            $monthlyValues = array_fill(1, 12, 0);
            $monthNames = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 
                7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
            ];

            foreach ($rawData as $row) {
                if (!empty($row->id_customer)) {
                    $customersList[$row->id_customer] = true;
                }

                if ($row->approval_status === 'pending') {
                    $leadsCount++;
                }

                if ($row->approval_status === 'approved') {
                    $dealsCount++;
                }

                if (!empty($row->tanggal)) {
                    $date = Carbon::parse($row->tanggal);
                    $monthNum = (int)$date->format('n');
                    $monthlyValues[$monthNum] += (float)($row->total_harga ?? 0); 
                }
            }

            $totalCustomers = count($customersList);
            $totalTenders = count($rawData);
            $conversionRate = $totalTenders > 0 ? round(($dealsCount / $totalTenders) * 100) : 0;            
            $activityData = [];
            foreach ($monthNames as $num => $name) {
                $activityData[] = [
                    'month' => $name,
                    'value' => $monthlyValues[$num]
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Data summary dashboard berhasil diambil',
                'data' => [
                    'summary' => [
                        'customers' => $totalCustomers,
                        'leads' => $leadsCount,
                        'deals' => $dealsCount,
                        'conversion' => $conversionRate,
                    ],
                    'activityData' => $activityData,
                    'customerData' => [
                        ['name' => 'New Leads', 'value' => $leadsCount],
                        ['name' => 'Won Deals', 'value' => $dealsCount],
                    ],
                    'leadSourceData' => [
                        ['source' => 'Sistem Tender', 'total' => $totalTenders]
                    ],
                    'recentActivities' => array_map(function($item) {
                        return [
                            'title' => "Tender " . $item->kode . " - " . ($item->last_followup_result ?? 'No Followup'),
                            'time' => !empty($item->tanggal) ? Carbon::parse($item->tanggal)->diffForHumans() : '-'
                        ];
                    }, array_slice($rawData, 0, 4))
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data summary tender',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}