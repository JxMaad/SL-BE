<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Buat atau update user
        $user = User::updateOrCreate(
            ['email' => $row['email']],
            [
                'name'     => $row['name'],
                'password' => Hash::make($row['password']),
                'status'   => $row['status'],
            ]
        );

        // Assign role jika ada
        if (!empty($row['role'])) {
            $roles = explode(',', $row['role']);
            $validRoles = [];

            foreach ($roles as $role) {
                $roleModel = Role::firstOrCreate(['name' => trim($role)], ['guard_name' => 'api']);
                $validRoles[] = $roleModel->name;
            }

            $user->syncRoles($validRoles);
        }

        // Assign permissions jika ada
        if (!empty($row['permission'])) {
            $permissions = explode(',', $row['permission']);
            $validPermissions = [];

            foreach ($permissions as $permission) {
                $permissionModel = Permission::firstOrCreate(['name' => trim($permission)], ['guard_name' => 'api']);
                $validPermissions[] = $permissionModel->name;
            }

            $user->syncPermissions($validPermissions);
        }

        return $user;
    }
}