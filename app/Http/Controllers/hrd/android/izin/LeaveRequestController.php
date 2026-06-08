<?php
namespace App\Http\Controllers\hrd\android\izin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LeaveRequestController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'leave_type' => 'required|in:Sakit,Cuti,Keperluan Pribadi,Dinas Luar',
            'date' => 'required|date',
            'reason' => 'required|string',
            'attachment_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        // 2. Handle Upload Foto (jika ada)
        $fileName = null;
        if ($request->hasFile('attachment_photo')) {
            $file = $request->file('attachment_photo');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/leaves'), $fileName);
        }

        try {
            DB::connection('hrd_android')->statement('CALL sp_add_leave_request_xx26(?, ?, ?, ?, ?)', [
                $request->user_id,
                $request->leave_type,
                $request->date,
                $request->reason,
                $fileName
            ]);

            return response()->json([
                'message' => 'Permohonan izin berhasil dikirim',
                'status' => 'success'
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan data ke hrd_android: ' . $e->getMessage()
            ], 500);
        }
    }
}