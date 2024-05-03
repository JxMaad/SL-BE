<?php

namespace App\Http\Controllers\Api\admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\GuestBookResource;
use App\Models\GuestBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GuestBookController extends Controller
{
    public function indexGuest()
    {
        //get guest
        $guest = GuestBook::when(request()->search, function ($guest) {
            $guest = $guest->where('name', 'like', '%' . request()->search . '%');
        })->latest()->paginate(10);

        //append query string to pagination links
        $guest->appends(['search' => request()->search]);

        //return with Api Resource
        return new GuestBookResource(true, 'List Data Tamu', $guest);
    }

    public function storeGuest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required',
            'class' => 'required',
            'departemen' => 'required',
            'email' => 'required',
            'address'   => 'required',
            'goals' => 'required',
            'telp'  => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $guest = GuestBook::create([
            'name' => $request->input('name'),
            'class' => $request->input('class'),
            'departemen' => $request->input('departemen'),
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'goals' => $request->input('goals'),
            'telp' => $request->input('telp'),
        ]);

        if ($guest) {
            //return success with Api Resource
            return new GuestBookResource(true, 'Data Tamu Berhasil Disimpan!', $guest);
        }

        //return failed with Api Resource
        return new GuestBookResource(false, 'Data Tamu Gagal Disimpan!', null);
    }

    public function updateGuest(Request $request, $id)
    {
        $guest = GuestBook::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'  => 'required',
            'class' => 'required',
            'departemen' => 'required',
            'email' => 'required',
            'address'   => 'required',
            'goals' => 'required',
            'telp'  => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $guest = GuestBook::create([
            'name' => $request->input('name'),
            'class' => $request->input('class'),
            'departemen' => $request->input('departemen'),
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'goals' => $request->input('goals'),
            'telp' => $request->input('telp'),
        ]);

        if ($guest) {
            //return success with Api Resource
            return new GuestBookResource(true, 'Data Tamu Berhasil Diedit!', $guest);
        }

        //return failed with Api Resource
        return new GuestBookResource(false, 'Data Tamu Gagal Diedit!', null);
    }

    public function destroyGuest($id)
    {
        $guest = GuestBook::find($id);

        // Hapus buku dari database
        if ($guest->delete()) {
            // Mengembalikan respons berhasil
            return new GuestBookResource(true, 'Data Tamu berhasil dihapus!', null);

            // Mengembalikan respons gagal jika buku tidak ditemukan atau gagal dihapus
            return new GuestBookResource(false, 'Data Tamu gagal dihapus!', null);
        }
    }
}
