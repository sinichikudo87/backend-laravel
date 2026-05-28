<?php

namespace App\Http\Controllers\crm\tenders;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\EncryptionHelper;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class TenderController extends Controller
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

    public function updateDetail(Request $request, $id) 
    {

        $request->validate([

            'qty' => 'required|integer|min:1',
            'price_per_unit' => 'required|numeric|min:0',
            'notes' => 'nullable|string',

        ]);

        try {

            $result = DB::select(
                "CALL sp_update_tender_detail_xx26(?, ?, ?, ?)",
                [

                    $id,
                    $request->qty,
                    $request->price_per_unit,
                    $request->notes,

                ]
            );

            if (empty($result)) {

                return response()->json([
                    'success' => false,
                    'message' => 'Detail tender tidak ditemukan'
                ], 404);

            }

            return response()->json([
                'success' => true,
                'message' =>
                    'Detail tender berhasil diupdate',
                'data' => $result[0]

            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);

        }
    }

    public function updateStatusHeader(Request $request, $id)
    {
        try {

            $request->validate([
                'is_status' => 'required|in:0,1',
            ]);

            DB::select(
                'CALL UpdateStatusPenawaranHeader_xx25(?, ?)',
                [
                    $id,
                    $request->is_status == 1 ? 'approval' : 'rejected'
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diupdate',
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error update status',
                'error' => $e->getMessage(),
            ], 500);

        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {

            $request->validate([
                'is_status' => 'required|in:0,1',
            ]);

            DB::select(
                'CALL UpdateStatusPenawaranDetail_xx25(?, ?)',
                [
                    $id,
                    $request->is_status == 1 ? 'approval' : 'rejected'
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diupdate',
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error update status',
                'error' => $e->getMessage(),
            ], 500);

        }
    }

    public function preview($id)
    {
        try {
            $data = DB::select(
                'CALL GetPenawaranWithDetailsById_xx25(?)',
                [$id]
            );

            if (empty($data)) {
                return abort(404, 'Data Penawaran tidak ditemukan.');
            }
            $header = $data[0];
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

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate PDF',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function getWithDetailsById($encryptedId)
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
            'penawaran_id'   => $first->penawaran_id,
            'kode'           => $first->kode,
            'tanggal'        => $first->tanggal,
            'type_order'     => $first->type_order,
            'status'         => $first->status,
            'customer_name'  => $first->customer_name,
            'customer_phone' => $first->customer_phone,
            'user_name'      => $first->user_name,
        ];

        // DETAIL GROUPING
        $details = collect($data)->map(function ($row) {
            return [
                'detail_id'     => $row->detail_id,
                'category_name' => $row->category_name,
                'qty'           => $row->jumlah,
                'price'         => $row->harga_satuan,
                'subtotal'      => $row->subtotal,
            ];
        });

        $logs = [];

        try {
            $logsData = DB::connection('crm')->select(
                "CALL sp_get_tender_negotiations_by_tender_id_xx26(?)",
                [$header['penawaran_id']]
            );

            $logs = collect($logsData)->map(function ($row) {

                return [
                    'id'                => $row->id ?? null,
                    'tenders_id'        => $row->tender_id ?? null,
                    'sesi'              => (int) $row->session_number,

                    'harga_marketing'   => (float) ($row->marketing_price ?? 0),
                    'catatan_marketing' => !empty($row->marketing_note)
                                            ? $row->marketing_note
                                            : '-',

                    'harga_customer'    => (float) ($row->customer_price ?? 0),
                    'catatan_customer'  => !empty($row->customer_note)
                                            ? $row->customer_note
                                            : '-',

                    'harga_deal'        => (float) ($row->deal_price ?? 0),

                    'status_negosiasi'  => $row->negotiation_status ?? 'ongoing',

                    'created_at'        => $row->created_at ?? null,
                    'updated_at'        => $row->updated_at ?? null,
                ];
            })->values()->all();

        } catch (\Exception $e) {
            Log::error('Gagal memuat log negosiasi', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $logs = [];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'penawaran_id'   => $header['penawaran_id'],
                'kode'           => $header['kode'],
                'tanggal'        => $header['tanggal'],
                'type_order'     => $header['type_order'],
                'status'         => $header['status'],
                'customer_name'  => $header['customer_name'],
                'customer_phone' => $header['customer_phone'],
                'user_name'      => $header['user_name'],
                'details'        => $details,
                'logs'           => $logs 
            ]
        ]);
    }

    public function store(Request $request)
    {
        $decryptedId = EncryptionHelper::decryptId($request->tender_id);

        if (!$decryptedId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid tender id'
            ], 400);
        }

        $request->validate([
            'tender_id'           => 'required|string',
            'request_by'          => 'required|in:marketing,customer',

            'marketing_price'     => 'nullable|numeric',
            'marketing_note'      => 'nullable|string',

            'customer_price'      => 'nullable|numeric',
            'customer_note'       => 'nullable|string',

            'deal_price'          => 'nullable|numeric',

            'negotiation_status'  => 'nullable|in:ongoing,deal,cancel',
        ]);

        try {

            $result = DB::connection('crm')->select(
                "CALL sp_insert_tenders_negosiasi_xx26(?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $decryptedId,

                    $request->request_by,

                    $request->marketing_price,
                    $request->marketing_note,

                    $request->customer_price,
                    $request->customer_note,

                    $request->deal_price,

                    $request->negotiation_status ?? 'ongoing',
                ]
            );

            $newId = $result[0]->new_negotiation_id ?? null;

            return response()->json([
                'success' => true,
                'message' => 'Negosiasi berhasil disimpan',
                'data' => [
                    'negotiation_id' => $newId
                ]
            ], 201);

        } catch (\Exception $e) {

            Log::error('Gagal insert negosiasi tender', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateNegotiationDeal(Request $request)
    {
        try {
            $id = EncryptionHelper::decryptId($request->tender_id);
            $request->validate([
                'tender_id'  => 'required',
                'deal_price' => 'nullable|numeric'
            ]);

            $result = DB::connection('crm')->select(
                "CALL sp_update_tender_negotiation_deal_xx26(?, ?)",
                [
                    $id,
                    $request->deal_price ?? 0
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Negotiation successfully updated to DEAL',
                'data'    => $result[0] ?? null
            ]);

        } catch (\Exception $e) {

            \Log::error('Failed update negotiation deal', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed update negotiation deal',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

   public function storeDocuments(Request $request, $encryptedId)
    {
        $validator = Validator::make($request->all(), [
            'berkas' => 'required|array|min:1',
            'berkas.*.file' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'berkas.*.deskripsi' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $id = EncryptionHelper::decryptId($encryptedId);

            $preparedDocuments = [];

            foreach ($request->file('berkas') as $index => $item) {

                $file = $item['file'];

                $originalName = $file->getClientOriginalName();
                $fileType     = $file->getClientMimeType();
                $fileSize     = $file->getSize();            
                $path = $file->store(
                    'customer_tenders/documents/' . date('Y/m'),
                    'public'
                );

                $deskripsi = $request->input("berkas.{$index}.deskripsi");

                $preparedDocuments[] = [
                    'deskripsi'       => $deskripsi,
                    'nama_file_asli'  => $originalName,
                    'file_path'       => $path,
                    'file_type'       => $fileType,
                    'file_size'       => $fileSize
                ];
            }

            $jsonDocumentsString = json_encode($preparedDocuments);

            DB::connection('crm')->statement(
                "CALL sp_insert_customer_tender_documents_xx26(?, ?)",
                [$id, $jsonDocumentsString]
            );

            return response()->json([
                'success' => true,
                'message' => 'Seluruh dokumen pendukung tender berhasil disimpan!'
            ], 200);

        } catch (Exception $e) {

            if (!empty($preparedDocuments)) {
                foreach ($preparedDocuments as $doc) {
                    Storage::disk('public')->delete($doc['file_path']);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses penyimpanan dokumen',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getDocuments($encryptedId)
    {
        try {

            $id = EncryptionHelper::decryptId($encryptedId);

            $documents = DB::connection('crm')->select(
                "CALL sp_select_customer_tender_documents_xx26(?)",
                [$id]
            );

            $formattedDocuments = array_map(function ($doc) {
                $doc->file_url = asset('storage/' . $doc->file_path);        
                if ($doc->file_size >= 1048576) {
                    $doc->formatted_size =
                        number_format($doc->file_size / 1048576, 2) . ' MB';
                } else {
                    $doc->formatted_size =
                        number_format($doc->file_size / 1024, 2) . ' KB';
                }

                return $doc;

            }, $documents);

            return response()->json([
                'success' => true,
                'message' => 'Daftar dokumen berhasil ditemukan.',
                'data' => $formattedDocuments
            ], 200);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar dokumen pendukung',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}