<?php

namespace App\Http\Controllers\crm\followUps;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\EncryptionHelper;
use Illuminate\Support\Facades\URL;

class FollowUpsController extends Controller
{
    public function show($id)
    {
        try {
            $result = DB::select(
                "CALL SelectPenawaranJoinDetail_xx25(?)",
                [$id]
            );

            if (!$result || count($result) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data penawaran tidak ditemukan'
                ], 404);
            }

            $allFollowups = DB::connection('crm')->select("CALL sp_get_tenders_followup_xx26(NULL, NULL)");
            $followupCollection = collect($allFollowups);
            
            $grouped = collect($result)
                ->groupBy('penawaran_id')
                ->map(function ($rows) use ($followupCollection) {
                    $first = $rows->first();
                    $currentTenderId = $first->penawaran_id;
                    $historyForThisTender = $followupCollection
                        ->where('tender_id', $currentTenderId)
                        ->map(function ($row) {
                            return [
                                'followup_id' => $row->id,
                                'stage' => $row->followup_stage,
                                'date' => $row->followup_date,
                                'user_id' => $row->user_id,
                                'notes' => $row->notes,
                                'result' => $row->result,
                                'next_action_plan' => $row->next_action_plan,
                                'created_at' => $row->created_at,
                            ];
                        })->values();

                    return [
                        'id' => $currentTenderId,
                        'kode' => $first->kode,
                        'customer_id' => $first->id_customer,
                        'customer_name' => $first->customer_name ?? "-",
                        'tanggal' => $first->tanggal,
                        'total_harga' => (float) $first->total_harga,
                        'status_penawaran' => $first->statusPenawaran,
                        'is_active' => (int) $first->is_active,
                        'created_by_user' => $first->user_name,
                        'created_at' => $first->created_at,         
                        'followup_history' => $historyForThisTender,
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
                'data' => $grouped
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function insert(Request $request)
    {
        $request->validate([
            // 'user_id'          => 'required|integer',
            'followup_stage'   => 'required|in:1,2,3,4,5',
            'followup_date'    => 'required|date',
            'notes'            => 'nullable|string',
            'result'           => 'required|in:pending,responded,no_answer,rejected',
            'next_action_plan' => 'nullable|string',
        ]);

        // $id = $request->id;
        $id = 1;
        try {

            // $userId = $request->user_id;
            $userId = 1;

            DB::connection('crm')->statement(
                "CALL sp_insert_tender_followup_xx26    (?, ?, ?, ?, ?, ?, ?)",
                [
                    $id,
                    $request->followup_stage,
                    $request->followup_date,
                    $userId,
                    $request->notes,
                    $request->result,
                    $request->next_action_plan
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Data follow up berhasil dicatat'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal insert follow up: ' . $e->getMessage()
            ], 500);

        }
    }

    public function preview($encryptedId)
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | DECRYPT TOKEN
            |--------------------------------------------------------------------------
            */
            $id = EncryptionHelper::decryptId($encryptedId);

            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token tidak valid'
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | GET DATA
            |--------------------------------------------------------------------------
            */
            $data = DB::select(
                'CALL GetPenawaranWithDetailsById_xx25(?)',
                [$id]
            );

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data penawaran tidak ditemukan'
                ], 404);
            }

            $header = $data[0];

            /*
            |--------------------------------------------------------------------------
            | GENERATE PDF
            |--------------------------------------------------------------------------
            */
            $pdf = Pdf::loadView(
                'crm.tenders.penawaran',
                [
                    'header'  => $header,
                    'details' => $data
                ]
            )->setPaper('A4', 'portrait');

            return $pdf->stream(
                'quotation-' . ($header->kode ?? $id) . '.pdf'
            );

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate PDF',
                'error'   => $e->getMessage(),
            ], 500);

        }
    }
}