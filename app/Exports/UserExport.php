<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return User::select('name', 'email', 'password', 'status', 'role', 'permission')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'password',
            'status',
            'role',
            'permission',
        ];
    }

    /**
     * @param \App\Models\User $user
     * @return array
     */
    public function map($user): array
    {
        return [
            $user->name,
            $user->email,
            $user->password,
            $user->status,
            $user->role,
            $user->permission,
        ];
    }
}

