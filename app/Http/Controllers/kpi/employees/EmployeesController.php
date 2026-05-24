<?php

namespace App\Http\Controllers\kpi\employees;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;

class EmployeesController extends Controller
{
    public function show($id)
    {
        try {

            $result = DB::select(
                "CALL GetEmployees_xx25(?)",
                [$id]
            );

            if (!$result || count($result) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan'
                ], 404);
            }

            $employees = collect($result)->map(function ($row) {

                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'phone' => $row->phone ?? '',
                    'email' => $row->email ?? '',
                    'address' => $row->address ?? '',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $employees->values()
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