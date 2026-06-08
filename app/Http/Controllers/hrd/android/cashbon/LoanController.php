<?php
namespace App\Http\Controllers\hrd\android\cashbon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Carbon;

class LoanController extends Controller
{
    public function view($user_id)
    {
        try {
            $currentMonth = Carbon::now()->month;
            $currentYear  = Carbon::now()->year;

            $loans = DB::connection('hrd_android')->select(
                'CALL sp_get_loans_by_user_xx26(?, ?, ?)',
                [
                    (int) $user_id,
                    (int) $currentMonth,
                    (int) $currentYear
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Sukses! Data riwayat kasbon berhasil diambil.',
                'data'    => $loans
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses data.',
                'error'   => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function store(Request $request)
    {     
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'amount'  => 'required|numeric|min:10000',
            'reason'  => 'required|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'error'   => $validator->errors()
            ], 422);
        }
        
        $validated = $validator->validated();

        try {
            \Illuminate\Support\Facades\DB::connection('hrd_android')->statement(
                'CALL sp_insert_loan_xx26(?, ?, ?, @out_inserted_id)',
                [
                    $validated['user_id'],
                    $validated['amount'],
                    $validated['reason']
                ]
            );
            
            $result = \Illuminate\Support\Facades\DB::connection('hrd_android')->select('SELECT @out_inserted_id AS new_loan_id');
            $newLoanId = !empty($result) ? $result[0]->new_loan_id : null;

            if (!$newLoanId || $newLoanId == 0) {
                throw new \Exception('Gagal mendapatkan ID transaksi dari database.');
            }

            return response()->json([
                'success' => true,
                'message' => 'Sukses! Pengajuan kasbon Anda berhasil dikirim dan menunggu validasi.',
                'data'    => [
                    'loan_id' => (int) $newLoanId
                ]
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::error('Loan DB Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses pengajuan kasbon ke database.',
                'error'   => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Loan System Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi nanti.',
                'error'   => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}