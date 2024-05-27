<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\UserExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ExportUserController extends Controller
{
    /**
     * Handle the export request.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportUsers()
    {
        return Excel::download(new UserExport, 'users.xlsx');
    }
}
