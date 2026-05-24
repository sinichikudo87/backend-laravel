<?php

namespace App\Http\Controllers\crm\approvals;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\EncryptionHelper;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class ApprovalController extends Controller
{
    public function show($id)
    {
        try {

            $result = DB::select(
                "CALL SelectPenawaranJoinDetailByPenawaranApproval_xx25(?)",
                [$id]
            );

            if (!$result || count($result) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data penawaran tidak ditemukan'
                ], 404);
            }

            $grouped = collect($result)
                ->groupBy('penawaran_id')
                ->map(function ($rows) {

                    $first = $rows->first();

                    return [
                        'id' => $first->penawaran_id,
                        'kode' => $first->kode,
                        'customer_id' => $first->id_customer,
                        'customer_name' => $first->customer_name ?? "-",
                        'tanggal' => $first->tanggal,
                        'total_harga' => (float) $first->total_harga,
                        'status_penawaran' => $first->statusPenawaran,
                        'is_active' => (int) $first->is_active,
                        'created_by_user' => $first->user_name,
                        'created_at' => $first->created_at,
                        'details' => $rows->map(function ($row) {
                            return [
                                'detail_id' => $row->detail_id,
                                'category_id' => $row->id_categori_unit,
                                'category_name' => $row->nama_kategori_unit,
                                'qty' => (int) $row->jumlah,
                                'price_per_unit' => (float) $row->harga_satuan,
                                'subtotal' => (float) $row->subtotal,
                                'statusPenawaranDetails' => $row->statusPenawaranDetails,
                            ];
                        })->values()
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $grouped->values()
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showApprovalForm($encryptedId)
    {
        $id = EncryptionHelper::decryptId($encryptedId);
        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid ID'
            ], 400);
        }
        $data = DB::select("CALL GetPenawaranWithDetailsById_xx25(?)", [$id]);

        if (!$data || count($data) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ], 404);
        }

        // HEADER
        $first = $data[0];

        $header = [
            'penawaran_id' => $first->penawaran_id,
            'kode' => $first->kode,
            'tanggal' => $first->tanggal,
            'type_order' => $first->type_order,
            'status' => $first->status,
            'customer_name' => $first->customer_name,
            'customer_phone' => $first->customer_phone,
            'user_name' => $first->user_name,
        ];

        // DETAIL GROUPING
        $details = collect($data)->map(function ($row) {
            return [
                'detail_id' => $row->detail_id,
                'category_name' => $row->category_name,
                'qty' => $row->jumlah,
                'price' => $row->harga_satuan,
                'subtotal' => $row->subtotal,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                ...$header,
                'details' => $details
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tender_id'      => 'required|integer',
            'approval_role'  => 'required|in:purchasing,admin_keuangan,manager_marketing',
            'approver_id'    => 'required|integer',
            'status'         => 'nullable|in:pending,approved,rejected',
            'notes'          => 'nullable|string',
            'sequence'       => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {

            $result = DB::connection('crm')->select(
                'CALL sp_insert_tender_approval_xx26(?, ?, ?, ?, ?, ?)',
                [
                    $request->tender_id,
                    $request->approval_role,
                    $request->approver_id,
                    $request->status,
                    $request->notes,
                    $request->sequence
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Approval tender berhasil ditambahkan',
                'data'    => $result[0] ?? null
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal insert approval',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}