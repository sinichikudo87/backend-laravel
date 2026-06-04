<?php

namespace App\Http\Controllers\hrd\android\login;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {        
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email dan password wajib diisi dengan benar',
                'errors'  => $validator->errors()
            ], 400);
        }

        try {
            $results = DB::connection('hrd_android')->select('CALL sp_login_user_xx26(?)', [$request->email]);

            if (empty($results)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email tidak terdaftar atau akun tidak aktif'
                ], 401);
            }

            $user = $results[0];
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password yang Anda masukkan salah'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'data' => [
                    'id'                => $user->id,
                    'id_companies_dash' => $user->id_companies_dash,
                    'id_division'       => $user->id_division,
                    'name'              => $user->name,
                    'email'             => $user->email,
                    'telepon'           => $user->telepon,
                    'company_name'      => $user->company_name,
                    'division_name'     => $user->division_name,
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Login API Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}