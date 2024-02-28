<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use App\Models\Borrow;
use App\Http\Resources\BorrowResource;
use App\Http\Controllers\Controller;
use App\Models\Book;

class BorrowController extends Controller
{
    public function index()
    {
        $borrows = Borrow::latest()->paginate(5);
        // Return with Api Resource
        return new BorrowResource(true, 'List Data borrows', $borrows);
    }

    public function store(Request $request)
    {
        //var_dump($request->all());exit;
        $request->validate([
            'borrowing_start' => 'required|date_format:Y-m-d H:i:s',
            'borrowing_end'  => 'required|date_format:Y-m-d H:i:s|after:borrowing_start',
            'book_id' => 'required|exists:books,id',
            'user_id' => 'required',
        ], [
            'borrowing_start.required' => 'Tanggal peminjaman diperlukan.',
            'borrowing_start.date_format' => 'Format tanggal peminjaman harus Y-m-d H:i:s.',
            'borrowing_end.required' => 'Tanggal pengembalian diperlukan.',
            'borrowing_end.date_format' => 'Format tanggal pengembalian harus Y-m-d H:i:s.',
            'borrowing_end.after' => 'Tanggal pengembalian harus setelah tanggal peminjaman.',
            'book_id.required' => 'ID buku diperlukan.',
            'book_id.exists' => 'Buku tidak ditemukan.',
            'user_id.required' => 'ID pengguna diperlukan.',
        ]);

        // Dapatkan buku dari database
        $book = Book::findOrFail($request->book_id);

        // Periksa stok buku
        if ($book->stock_amount <= 0) {
            return response()->json(['error' => 'Maaf, stok buku habis'], 400);
        }

        // Create borrow jika buku tersedia
        $borrow = Borrow::create([
            'borrowing_start' => $request->input('borrowing_start'),
            'borrowing_end' => $request->input('borrowing_end'),
            'book_id' => $request->input('book_id'),
            'user_id' => $request->input('user_id'),
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
        $book = Book::find($id);

        // Pastikan buku ditemukan
        if ($book) {
            // Periksa apakah status buku saat ini adalah 'pending'
            if ($book->status === 'pending') {
                // Update status buku menjadi 'accepted'
                $book->status = 'accepted';
                $book->save();

                return response()->json(['message' => 'Status buku berhasil diperbarui menjadi diterima untuk dipinjam.']);
            } else {
                // Jika buku tidak tersedia untuk dipinjam, kembalikan respon error
                return response()->json(['message' => 'Buku tidak tersedia untuk dipinjam saat ini.'], 400);
            }
        } else {
            // Jika buku tidak ditemukan, kembalikan respon error
            return response()->json(['message' => 'Buku tidak ditemukan.'], 404);
        }
    }
}
