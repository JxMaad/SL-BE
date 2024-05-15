<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsTableSeeder extends Seeder
{
    // Default permissions for different roles.
    protected $librarian = [
        'books.index',
        'books.create',
        'books.edit',
        'books.delete',
        'borrows.index',
        'borrows.create',
        'borrows.edit',
        'restores',
        'roles.index',
        'roles.create',
        'roles.edit',
        'roles.delete',
        'permissions.index',
        'users.index',
        'users.create',
        'users.edit',
        'users.delete',
    ];

    // Default permissions for different roles.
    protected $member = [
        'books.index',
        'borrows.index',
        'borrows.create',
        'restores',
        'users.index',
        'users.create',
        'users.edit',
    ];

    /**
     * Run the database seeds.
     */
    public function run()
    {
        //permission for books
        Permission::create(['name' => 'books.index', 'guard_name' => 'api']);
        Permission::create(['name' => 'books.create', 'guard_name' => 'api']);
        Permission::create(['name' => 'books.edit', 'guard_name' => 'api']);
        Permission::create(['name' => 'books.delete', 'guard_name' => 'api']);

        //permission for borrows
        Permission::create(['name' => 'borrows.index', 'guard_name' => 'api']);
        Permission::create(['name' => 'borrows.create', 'guard_name' => 'api']);
        Permission::create(['name' => 'borrows.edit', 'guard_name' => 'api']);
        Permission::create(['name' => 'borrows.delet', 'guard_name' => 'api']);

        // //permission for restore
        Permission::create(['name' => 'restores', 'guard_name' => 'api']);

        //permission for roles
        Permission::create(['name' => 'roles.index', 'guard_name' => 'api']);
        Permission::create(['name' => 'roles.create', 'guard_name' => 'api']);
        Permission::create(['name' => 'roles.edit', 'guard_name' => 'api']);
        Permission::create(['name' => 'roles.delete', 'guard_name' => 'api']);

        //permission for permissions
        Permission::create(['name' => 'permissions.index', 'guard_name' => 'api']);

        //permission for users
        Permission::create(['name' => 'users.index', 'guard_name' => 'api']);
        Permission::create(['name' => 'users.create', 'guard_name' => 'api']);
        Permission::create(['name' => 'users.edit', 'guard_name' => 'api']);
        Permission::create(['name' => 'users.delete', 'guard_name' => 'api']);

        Permission::create(['name' => 'guest.index', 'guard_name' => 'api']);
        Permission::create(['name' => 'guest.create', 'guard_name' => 'api']);
        Permission::create(['name' => 'guest.edit', 'guard_name' => 'api']);
        Permission::create(['name' => 'guest.delete', 'guard_name' => 'api']);

        // Assign permissions to roles
        $roles = Role::all();

        foreach ($roles as $role) {
            // Check the role
            if ($role->name === 'pustakawan') {
                $role->syncPermissions($this->librarian);
            } elseif ($role->name === 'anggota') {
                $role->syncPermissions($this->member);
            }
        }
    }
}
