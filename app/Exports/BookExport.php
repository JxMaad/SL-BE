<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BookExport implements WithHeadings
{
    /**
     * @return array
     */

    public function headings(): array
    {
        return [
            'title',
            'synopsis',
            'isbn',
            'writer',
            'page_amount',
            'stock_amount',
            'published',
            'publisher',
            'category',
            'image',
            'status',
        ];
    }
}
