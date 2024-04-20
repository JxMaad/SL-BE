<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use App\Models\Borrow;
use App\Http\Resources\BorrowResource;
use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Support\Facades\Auth; // Tambahkan ini
use App\Models\User;

class BorrowController extends Controller
{
    public function index()
    {
        // Ambil data peminjaman dengan relasi user dan book
        $borrows = Borrow::with('user', 'book')->latest()->paginate(5);
        
        // Return data dengan Api Resource
        return new BorrowResource(true, 'List Data borrows', $borrows);
    }

    public function store(Request $request)
    {
        // Dapatkan ID pengguna yang sedang login
        $userId = Auth::id();

        // Validasi request
        $request->validate([
            'book_id' => 'required|exists:books,id',
        ], [
            'book_id.required' => 'ID buku diperlukan.',
            'book_id.exists' => 'Buku tidak ditemukan.',
        ]);

        // Dapatkan buku dari database
        $book = Book::findOrFail($request->book_id);

        // Periksa stok buku
        if ($book->stock_amount <= 0) {
            return response()->json(['error' => 'Maaf, stok buku habis'], 400);
        }

        // Create borrow jika buku tersedia
        $borrow = Borrow::create([
            'book_id' => $request->input('book_id'),
            'user_id' => $userId, // Gunakan ID pengguna dari pengguna yang sedang login
        ]);

        if ($borrow) {
            // Kurangi stok buku
            $book->stock_amount--;

            // Simpan perubahan stok buku
            $book->update();

            // Return success with API Resource
            return new BorrowResource(true, 'Data peminjaman berhasil disimpan!', $borrow);
        }

        // Return failed with API Resource
        return new BorrowResource(false, 'Data peminjaman gagal disimpan!', null);
    }

    public function show(Borrow $borrow, $id)
    {
        //get borrow
        $borrow = Borrow::whereId($id)->first();

        if ($borrow) {
            //return success with Api resource
            return new BorrowResource(true, 'Detail Data borrow', $borrow);
        }

        //return failed with Api Resource
        return new BorrowResource(false, 'Detail Data borrow Tidak Ditemukan!', null);
    }

    /**
     * Mengupdate status buku menjadi dipinjam dengan persetujuan admin.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatusBorrow($id)
    {
        // Temukan buku berdasarkan ID
        $borrow = Borrow::find($id);

        // Pastikan buku ditemukan
        if ($borrow) {
            // Periksa apakah status buku saat ini adalah 'pending'
            if ($borrow->status === 'pending') {
                // Update status buku menjadi 'accepted'
                $borrow->status = 'accepted';
                $borrow->save();

                return response()->json(['message' => 'Permohonan peminjaman buku berhasil diajukan.']);
            } else {
                // Jika buku tidak ditemukan, kembalikan respon error
                return response()->json(['message' => 'Peminjaman tidak ditemukan.'], 404);
            }
        }
    }
}
