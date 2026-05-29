<?php

namespace App\Http\Controllers\accounting\account_category;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;

class AccountCategoryController extends Controller
{
    public function show($companyId)
    {
        try {
            $companyId = 1;
            $result = DB::select(
                "CALL sp_get_account_categories_xx26(?)",
                [$companyId]
            );

            if (!$result || count($result) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account categories not found'
                ], 404);
            }

            $categories = collect($result)->map(function ($row) {

                return [
                    'id' => $row->id,
                    'company_id' => $row->company_id,
                    'name' => $row->name,
                    'description' => $row->description ?? '',
                    'is_active' => $row->is_active,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $categories->values()
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