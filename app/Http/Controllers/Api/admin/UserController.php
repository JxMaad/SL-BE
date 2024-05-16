<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\UserExport;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Imports\UserImport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    /**
     * Display a listing of the resource
     * 
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //get user
        $users = User::when(request()->search, function ($users) {
            $users = $users->where('name', 'like', '%' . request()->search . '%');
        })->with('roles')->latest()->paginate(10);

        //append query string to pagination links
        $users->appends(['search' => request()->search]);

        //return with Api Resource
        return new UserResource(true, 'List Data User', $users);
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /**
         * Validate request
         */
        $validator = Validator::make($request->all(), [
            'name'         => 'required',
            'email'        => 'required|unique:users',
            'password'     => 'required|confirmed',
            // 'roles'        => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Create user
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // Assign role to user
        $user->assignRole($request->roles);

        if ($user) {
            // Return success with Api Resource
            return new UserResource(true, 'Data User Berhasil Disimpan!', $user);
        }

        // Return failed with Api Resource
        return new UserResource(false, 'Data User Gagal Disimpan!', null);
    }

    /**
     * Display the specified resource.
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        //get user
        $user = User::with('roles')->find($request->id);

        if ($user) {
            //return success with Api resource
            return new UserResource(true, 'Detail Data User', $user);
        }

        //return failed with Api Resource
        return new UserResource(false, 'Detail Data User Tidak Ditemukan!', null);
    }

    public function update(Request $request, $id)
    {
        // Mengambil data user yang akan diupdate
        $user = User::findOrFail($id);

        /**
         * Validate request
         */
        $validator = Validator::make($request->all(), [
            'name'           => 'sometimes',
            'email'          => 'sometimes',
            'password'       => 'sometimes|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Mengupdate data user lainnya
        if ($request->filled('name')) {
            $user->update([
                'name' => $request->name,
            ]);
        }

        if ($request->filled('email')) {
            $user->update([
                'email' => $request->email,
            ]);
        }

        // Mengupdate password jika dimasukkan
        if ($request->filled('password')) {
            $user->update([
                'password' => bcrypt($request->password),
            ]);
        }

        // Menyinkronkan peran pengguna jika dimasukkan
        if ($request->filled('roles')) {
            $user->syncRoles($request->roles);
        }

        // Mengembalikan respons sesuai keberhasilan atau kegagalan
        if ($user) {
            return new UserResource(true, 'Data User Berhasil Diupdate!', $user);
        } else {
            return new UserResource(false, 'Data User Gagal Diupdate!', null);
        }
    }

    /**
     * Mengupdate status user dari loading menjadi active.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatusUser($id)
    {
        // Temukan pengguna berdasarkan ID
        $user = User::find($id);

        // Pastikan pengguna ditemukan
        if ($user) {
            // Update status pengguna menjadi 'active'
            $user->status = 'Aktif';
            $user->save();

            return response()->json(['message' => 'Status pengguna berhasil diperbarui menjadi aktif.']);
        } else {
            // Jika pengguna tidak ditemukan, kembalikan respon error
            return response()->json(['message' => 'Pengguna tidak ditemukan.'], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            // Jika user tidak ditemukan, kembalikan respons dengan Api Resource
            return new UserResource(false, 'Data User Tidak Ditemukan!', null);
        }

        // Menghapus user
        if ($user->delete()) {
            // Kembalikan respons berhasil dengan Api Resource
            return new UserResource(true, 'Data User Berhasil Dihapus!', null);
        }

        // Jika gagal menghapus, kembalikan respons gagal dengan Api Resource
        return new UserResource(false, 'Data User Gagal Dihapus!', null);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new UserImport, $request->file('file'));

        return response()->json(['message' => 'User berhasil diimport!'], 200);
    }

    public function export()
    {
        return Excel::download(new UserExport, 'users.xlsx');
    }
}