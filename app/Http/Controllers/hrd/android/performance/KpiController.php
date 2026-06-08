<?php

namespace App\Http\Controllers\hrd\android\performance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class KpiController extends Controller
{
    public function view(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'      => 'required|integer',
            'period_month' => 'nullable|integer|between:1,12',
            'period_year'  => 'nullable|integer|digits:4',
            'status'       => 'nullable|string|in:PENDING,IN_PROGRESS,REVIEW,APPROVED,REJECTED'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'data'    => null,
                'error'   => $validator->errors()->first()
            ], 422);
        }

        try {
            $userId = $request->input('user_id');
            $month  = $request->input('period_month');
            $year   = $request->input('period_year');
            $status = $request->input('status');
        
            $kpiData = DB::connection('hrd_android')->select('CALL sp_get_user_kpi_xx26(?, ?, ?, ?)', [
                $userId,
                $month,
                $year,
                $status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data KPI berhasil didapatkan',
                'data'    => $kpiData, 
                'error'   => null
            ], 200);

        } catch (Exception $e) {            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server saat mengambil data KPI',
                'data'    => [],
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function getDailyLogDetails(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer'
        ]);
        $kpiId = $request->input('user_id');
        try {
            $logDetails = DB::connection('hrd_android')->select(
                'CALL sp_get_daily_logs_by_kpi_xx26(?)', 
                [$kpiId]
            );

            // 4. Return respon sukses ke Android
            return response()->json([
                'status'  => 'success',
                'message' => 'Detail log harian berhasil diambil',
                'data'    => $logDetails
            ], 200);

        } catch (Exception $e) {
            // Log internal server tetap mencatat detail error untuk tim dev (tidak terlihat oleh user)
            Log::error('Koneksi database bermasalah pada detail log: ' . $e->getMessage());

            // Response error dikembalikan dengan pesan umum yang aman tanpa membawa nama prosedur
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil detail log harian.',
                'debug'   => env('APP_DEBUG') ? 'Koneksi database atau parameter tidak valid.' : null
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_jobdesk_kpi_id'   => 'required|integer',
            'date'                  => 'required|date_format:Y-m-d',
            'actual_value_submitted' => 'required|string|max:100', 
            'score_impact'          => 'nullable|numeric',
            'notes'                 => 'nullable|string',
            'attachment'            => 'nullable|image|mimes:jpeg,png,jpg|max:5120', 
        ]);

        try {
            $fileName = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/performance'), $fileName);
            }

            $cleanActualValue = str_replace('%', '', $validated['actual_value_submitted']);
            DB::connection('hrd_android')->statement(
                'CALL sp_insert_daily_progress_log_xx26(?, ?, ?, ?, ?, ?)', 
                [
                    $validated['user_jobdesk_kpi_id'],
                    $validated['date'],
                    $cleanActualValue,
                    $validated['score_impact'] ?? 0.00, 
                    $validated['notes'] ?? null,
                    $fileName
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Sukses! Log progress harian berhasil disimpan.'
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal: Anda sudah mengisi progress KPI ini pada tanggal tersebut.'
                ], 409);
            }

            Log::error('KPI DB Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses data KPI ke database hrd_android.',
                'error'   => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);

        } catch (\Exception $e) {
            Log::error('KPI System Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses KPI. Silakan coba lagi nanti.',
                'error'   => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}