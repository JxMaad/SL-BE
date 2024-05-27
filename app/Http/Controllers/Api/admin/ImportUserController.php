<?php

namespace App\Http\Controllers\Api\Admin;

use App\Imports\UserImport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ImportUserController extends Controller
{
    /**
     * Handle the import request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importUsers(Request $request)
    {
        // Validasi file upload
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);
    
        // Jika validasi gagal, kembalikan pesan kesalahan
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }
    
        try {
            // Import data pengguna
            Excel::import(new UserImport, $request->file('file'));
            return response()->json(['success' => 'Data pengguna berhasil di-import!']);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];
            foreach ($failures as $failure) {
                $errorMessages[] = 'Baris ' . $failure->row() . ': ' . implode(', ', $failure->errors());
            }
            return response()->json(['error' => 'Terjadi kesalahan dalam mengimpor data pengguna: ' . implode(' | ', $errorMessages)], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan dalam mengimpor data pengguna: ' . $e->getMessage()], 500);
        }
    }
}
