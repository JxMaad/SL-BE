<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\BookExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportBookController extends Controller {
    /**
     * Handle the export.
     *
     * @return \Illuminate\Support\Facades\Response
     */
    public function exportBook()
    {
        return Excel::download(new BookExport, 'book.xlsx');
    }
}
