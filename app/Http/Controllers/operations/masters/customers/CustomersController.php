<?php

namespace App\Http\Controllers\operations\masters\customers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;

class CustomersController extends Controller
{
    public function show($id)
    {
        try {

            $result = DB::select(
                "CALL SelectCustomersByUserId_xx25(?)",
                [1]
            );

            if (!$result || count($result) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data customer tidak ditemukan'
                ], 404);
            }

            $customers = collect($result)->map(function ($row) {

                return [
                    'id'              => $row->id,
                    'name'            => $row->name ?? '',
                    'phone'           => $row->phone ?? '',
                    'email'           => $row->email ?? '',
                    'address'         => $row->address ?? '',
                    'identity_number' => $row->identity_number ?? '',
                    'user_id'         => $row->user_id,
                    'is_active'       => $row->is_active,
                    'created_at'      => $row->created_at,
                    'updated_at'      => $row->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $customers->values()
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