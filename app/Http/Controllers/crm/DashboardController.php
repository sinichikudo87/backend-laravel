<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {

            $data = DB::connection('crm')->select("
                CALL crm_carlinx_xx26.sp_summary_tender_xx26()
            ");

            return response()->json([
                'success' => true,
                'message' => 'Data summary tender berhasil diambil',
                'data' => $data
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