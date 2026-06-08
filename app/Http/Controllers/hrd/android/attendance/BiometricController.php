<?php

namespace App\Http\Controllers\hrd\android\attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class BiometricController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'              => 'required|integer',
            'biometric_type'       => 'required|in:face,fingerprint',
            'biometric_token_hash' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $result = DB::connection('hrd_android')->select(
                'CALL upsert_user_biometrics_xx26(?, ?, ?)', 
                [$request->user_id, $request->biometric_type, $request->biometric_token_hash]
            );

            $biometricData = !empty($result) ? $result[0] : null;
            return response()->json([
                'success' => true,
                'message' => 'Data biometrik berhasil disinkronisasi ke server.',
                'data'    => $biometricData
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data ke server pusat.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}