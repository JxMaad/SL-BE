<?php

namespace App\Imports;

use App\Models\Book;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Spatie\Permission\Models\Permission;

class UserImport implements ToCollection, WithHeadingRow
{
    /**
     * Handle the import.
     *
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Buat pengguna baru
            $books = Book::create([
                'title' => $row['title'],
                'synopsis' => $row['synopsis'],
                'isbn' => $row['isbn'],
                'writer' => $row['writer'],
                'page_amount' => $row['page_amount'],
                'stock_amount' => $row['stock_amount'],
                'published' => $row['published'],
                'publisher' => $row['publisher'],
                'category' => $row['category'],
                'image' => $row['image'],
                'status' => $row['status'],
            ]);
        }
    }
}
