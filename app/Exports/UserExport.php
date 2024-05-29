<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserExport implements WithHeadings
{
    /**
     * @return array
     */
    
    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Password',
            'Status',
        ];
    }
}

