<?php

namespace App\Http\Controllers\operations\masters\units;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;

class UnitsController extends Controller
{
    public function show($id)
    {
        try {

            $result = DB::select(
                "CALL GetCarTypesWithCompany_xx25(?)",
                [$id]
            );

            if (!$result || count($result) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data unit tidak ditemukan'
                ], 404);
            }

            $units = collect($result)->map(function ($row) {

                return [
                    'car_id'              => $row->car_id,
                    'category_id'         => $row->category_id,
                    'category_name'       => $row->category_name ?? '',
                    'driver_category_id'  => $row->driver_category_id,

                    'car_name'            => $row->car_name ?? '',
                    'transmission'        => $row->transmission ?? '',
                    'bbm_id'              => $row->bbm_id,
                    'passenger_capacity'  => $row->passenger_capacity,
                    'year'                => $row->year,
                    'warna'               => $row->warna ?? '',
                    'plat_nomor'          => $row->plat_nomor ?? '',

                    'harga_bulanan'       => $row->harga_bulanan,
                    'harga_lepas_kunci'   => $row->harga_lepas_kunci,
                    'biaya_dalam_kota'    => $row->biaya_dalam_kota,
                    'biaya_luar_kota'     => $row->biaya_luar_kota,
                    'biaya_luar_batas'    => $row->biaya_luar_batas,
                    'biaya_transfer'      => $row->biaya_transfer,
                    'biaya_overtime'      => $row->biaya_overtime,
                    'biaya_3_jam'         => $row->biaya_3_jam,
                    'biaya_6_jam'         => $row->biaya_6_jam,
                    'biaya_9_jam'         => $row->biaya_9_jam,
                    'biaya_12_jam'        => $row->biaya_12_jam,

                    'bbm_dalam_kota'      => $row->bbm_dalam_kota,
                    'bbm_jarak_per_liter' => $row->bbm_jarak_per_liter,

                    'status'              => $row->status,
                    'is_active'           => $row->is_active,

                    'created_at'          => $row->created_at,
                    'updated_at'          => $row->updated_at,

                    'company' => [
                        'company_id'     => $row->company_id,
                        'company_name'   => $row->company_name ?? '',
                        'company_alias'  => $row->company_alias ?? '',
                        'company_email'  => $row->company_email ?? '',
                        'company_phone'  => $row->company_phone ?? '',
                        'company_city'   => $row->company_city ?? '',
                        'company_logo'   => $row->company_logo ?? '',
                        'company_ppn'    => $row->company_ppn,
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $units->values()
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