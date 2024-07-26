<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Book;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ImportBookController extends Controller
{
    public function importBook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()]);
        }

        try {
            $filePath = $request->file('file')->getPathname();
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            $columns = range('A', $worksheet->getHighestDataColumn());
            $headerRow = 1;

            $columnMapping = [
                'title' => null,
                'synopsis' => null,
                'isbn' => null,
                'writer' => null,
                'page_amount' => null,
                'stock_amount' => null,
                'published' => null,
                'publisher' => null,
                'category' => null,
                'image' => null,
                'status' => null,
            ];

            // Map columns based on header
            foreach ($columns as $column) {
                $cellValue = strtolower($worksheet->getCell($column . $headerRow)->getValue());
                if (in_array($cellValue, array_keys($columnMapping))) {
                    $columnMapping[$cellValue] = $column;
                }
            }

            // Check if all required columns are found
            if (in_array(null, $columnMapping)) {
                return response()->json(['error' => 'Salah satu atau lebih kolom yang diperlukan tidak ditemukan dalam file.']);
            }

            $dataStartRow = 2;
            $dataEndRow = $worksheet->getHighestDataRow();

            $booksData = [];

            for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {
                $book = [];
                $isRowEmpty = true;
                foreach ($columnMapping as $key => $column) {
                    $value = $worksheet->getCell($column . $row)->getValue();
                    $book[$key] = $value;
                    if (!empty($value)) {
                        $isRowEmpty = false;
                    }
                }
                if ($isRowEmpty) {
                    // Skip empty rows
                    continue;
                }
                // Check if all required data is present
                if (array_search(null, $book, true) !== false) {
                    return response()->json(['error' => "Data tidak lengkap pada baris $row. Semua kolom harus diisi."]);
                }
                $booksData[] = $book;
            }

            foreach ($booksData as $data) {
                Book::create($data);
            }

            return response()->json(['success' => 'Data buku berhasil di-import!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan dalam mengimpor data buku: ' . $e->getMessage()]);
        }
    }
}
