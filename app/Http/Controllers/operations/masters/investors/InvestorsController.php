<?php

namespace App\Http\Controllers\operations\masters\investors;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;

class InvestorsController extends Controller
{
    public function show($id)
    {
        try {

            $result = DB::connection('production')->select(
                "CALL select_investors_by_company_id_xx26(?)",
                [$id]
            );

            if (!$result || count($result) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data investor tidak ditemukan'
                ], 404);
            }

            $investors = collect($result)->map(function ($row) {

                return [
                    'id'          => (int) $row->id,
                    'company_id'  => (int) $row->company_id,
                    'name'        => $row->name ?? '-',
                    'phone'       => $row->phone ?? '-',
                    'address'     => $row->address ?? '-',
                    'email'       => $row->email ?? '-',
                    'start_date'  => $row->start_date,
                    'is_active'   => (int) $row->is_active,
                    'created_at'  => $row->created_at,
                    'updated_at'  => $row->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $investors->values()
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}