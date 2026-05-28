<?php

namespace App\Http\Controllers\operations\masters\garages;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;

class GaragesController extends Controller
{
    public function show($id)
{
    try {

        $result = DB::connection('production')->select(
            "CALL select_garages_by_company_id_xx26(?)",
            [1]
        );

        if (!$result || count($result) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Data garage tidak ditemukan'
            ], 404);
        }

        $garages = collect($result)->map(function ($row) {

            return [
                'id'          => $row->id,
                'company_id'  => $row->company_id,
                'name'        => $row->name ?? '',
                'phone'       => $row->phone ?? '',
                'email'       => $row->email ?? '',
                'address'     => $row->address ?? '',
                'city'        => $row->city ?? '',
                'description' => $row->description ?? '',
                'pic_name'    => $row->pic_name ?? '',
                'is_active'   => $row->is_active,
                'created_at'  => $row->created_at,
                'updated_at'  => $row->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $garages->values()
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