<?php

namespace App\Http\Controllers\operations\masters\drivers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;

class DriversController extends Controller
{
    public function show($id)
    {
        try {

            $result = DB::select(
                "CALL GetDriverByCompanyId_xx25(?)",
                [1]
            );

            if (!$result || count($result) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data driver tidak ditemukan'
                ], 404);
            }

            $drivers = collect($result)->map(function ($row) {

                return [
                    'driver_id'      => $row->driver_id,
                    'driver_address' => $row->driver_address ?? '',
                    'phone_number'   => $row->phone_number ?? '',
                    'join_date'      => $row->join_date,
                    'description'    => $row->description ?? '',
                    'photo'          => $row->photo ?? '',
                    'driver_active'  => $row->driver_active,

                    'user' => [
                        'user_id'    => $row->user_id,
                        'user_name'  => $row->user_name ?? '',
                        'user_email' => $row->user_email ?? '',
                    ],

                    'company' => [
                        'company_id'   => $row->company_id,
                        'company_name' => $row->company_name ?? '',
                        'company_city' => $row->company_city ?? '',
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $drivers->values()
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