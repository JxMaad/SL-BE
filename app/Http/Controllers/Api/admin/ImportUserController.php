<?php

namespace App\Http\Controllers\Api\Admin;

use App\Imports\UsersImport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ImportUser extends Controller
{
    /**
     * Handle the import request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()]);
        }
    
        try {
            Excel::import(new UsersImport, $request->file('file'));
            return response()->json(['success' => 'Data pengguna berhasil di-import!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan dalam mengimpor data pengguna: ' . $e->getMessage()]);
        }
    }
}
