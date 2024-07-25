<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReadResource;
use App\Models\Read;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReadController extends Controller
{
    public function readbookindex()
    {
        // Get all books regardless of the user
        $readbooks = Read::latest()->paginate(8);

        // Append query string to pagination links
        $readbooks->appends(['search' => request()->search]);

        // Return with Api Resource
        return new ReadResource(true, 'List Buku Online', $readbooks);
    }

    public function readbookstore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|unique:reads,',
            'writer' => 'required',
            'page_amount' => 'nullable',
            'published' => 'required',
            'publisher' => 'nullable',
            'category' => 'nullable',
            'image' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Simpan gambar ke storage
        $image = $request->file('image');
        $image->storeAs('public/readbook', $image->hashName());

        $readbook = Read::create([
            'title' => $request->input('title'),
            'writer' => $request->input('writer'),
            'page_amount' => $request->input('page_amount'),
            'published' => $request->input('published'),
            'publisher' => $request->input('publisher'),
            'category' => $request->category,
            'image' => $image->hashName(),
        ]);

        $readbook->save();

        if ($readbook) {
            //return success with Api Resource
            return new ReadResource(true, 'Data buku online Berhasil Disimpan!', $readbook);
        }

        //return failed with Api Resource
        return new ReadResource(false, 'Data buku online Gagal Disimpan!', null);
    }

    public function readbookshow($id)
    {
        //get book$book
        $readbook = Read::whereId($id)->first();

        if ($readbook) {
            //return success with Api resource
            return new ReadResource(true, 'Detail Data baca online', $readbook);
        }

        //return failed with Api Resource
        return new ReadResource(false, 'Detail Data baca online Tidak Ditemukan!', null);
    }

    public function readbookupdate(Request $request, $id)
    {
        $readbook = Read::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'required|unique:reads,title,'. $id,
            'writer' => 'required',
            'page_amount' => 'required',
            'published' => 'required',
            'publisher' => 'required',
            'category' => 'required',
            'image' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // //check image update
        if ($request->file('image')) {

            // Jika ada gambar baru diunggah
            if ($request->hasFile('image')) {
                //remove old image
                Storage::disk('local')->delete('public/readbook/' . basename($readbook->image));

                //uplod new image
                $image = $request->file('image');
                $image->storeAs('public/readbook', $image->hashName());
            }

            //update new image
            $readbook->update([
                'title' => $request->input('title'),
                'writer' => $request->input('writer'),
                'page_amount' => $request->input('page_amount'),
                'published' => $request->input('published'),
                'publisher' => $request->input('publisher'),
                'category' => $request->category,
                'image' => $image->hashName(),
            ]);
        } else {
            //update no image
            $readbook->update([
                'title' => $request->input('title'),
                'writer' => $request->input('writer'),
                'page_amount' => $request->input('page_amount'),
                'published' => $request->input('published'),
                'publisher' => $request->input('publisher'),
                'category' => $request->category,
            ]);
        }

        $readbook->save();

        // check if the update was successful
        if ($readbook->wasChanged()) {
            // return success with Api Resource
            return new ReadResource(true, 'Data buku Berhasil Diupdate!', $readbook);
        }

        // return failed with Api Resource
        return new ReadResource(false, 'Data buku Gagal Diupdate!', null);
    }

    public function readbookdestroy($id) {
        // Cari buku berdasarkan ID
        $readbook = Read::findOrFail($id);

        // Jika buku ditemukan
        if ($readbook) {
            // Hapus gambar dari storage
            //remove image
            Storage::disk('local')->delete('public/readbook/' . basename($readbook->image));

            // Hapus buku dari database
            if ($readbook->delete()) {
                // Mengembalikan respons berhasil
                return new ReadResource(true, 'Data buku berhasil dihapus!', null);
            }
        }
        // Mengembalikan respons gagal jika buku tidak ditemukan atau gagal dihapus
        return new ReadResource(false, 'Data buku gagal dihapus!', null);
    }
}
