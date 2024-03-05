<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
        })->with('roles')->latest()->paginate(5);

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

        // Fetch random image from Lorem Picsum
        $response = Http::get('https://picsum.photos/200/300');

        // Check if the request to Lorem Picsum was successful
        if ($response->ok()) {
            $imageContent = $response->body();

            // Generate unique image name
            $imageName = time() . '.jpg';

            // Store image in the filesystem
            Storage::disk('public')->put('users/' . $imageName, $imageContent);

            // Create user with image filename
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => bcrypt($request->password),
                'image'    => $imageName, // Store only the filename in the database
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
            'name'           => 'required',
            'email'          => 'required',
            'password'       => 'sometimes|confirmed',
            'image'          => 'nullable|file|mimes:jpeg,jpg,png|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Mengupdate data user lainnya
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Mengupdate password jika dimasukkan
        if ($request->filled('password')) {
            $user->update([
                'password' => bcrypt($request->password),
            ]);
        }

        // Mengupdate gambar jika dimasukkan
        if ($request->hasFile('image')) {
            // Menghapus gambar lama jika ada
            Storage::delete($user->image);

            // Mengunggah gambar baru
            $imagePath = $request->file('image')->store('images', 'public');
            $user->update([
                'image' => $imagePath,
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
            $user->status = 'active';
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
    public function destroy(User $user)
    {
        //delete role 
        if ($user->delete()) {
            //return success with Api Resource
            return new UserResource(true, 'Data User Berhasil Dihapus!', null);
        }

        //return failed with Api Resource
        return new UserResource(false, 'Data User Gagal Dihapus!', null);
    }
}
