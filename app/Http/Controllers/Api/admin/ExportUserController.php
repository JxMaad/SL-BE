<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ExportUser extends Controller
{
    /**
     * Handle the export request.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportUsers()
    {
        return Excel::download(new UsersExport, 'users.xlsx');
    }
}
