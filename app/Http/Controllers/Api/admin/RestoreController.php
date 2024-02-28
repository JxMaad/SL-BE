<?php

namespace App\Http\Controllers\Api\Admin;

use Carbon\Carbon;
use App\Models\Borrow;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Restore;

class RestoreController extends Controller
{
    /**
     * Memproses pengembalian buku.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function returnBook($id)
    {
        // Cari peminjaman berdasarkan ID
        $borrow = Borrow::find($id);

        // Pastikan peminjaman ditemukan
        if ($borrow) {
            // Periksa apakah status peminjaman saat ini adalah 'pending' atau 'accepted'
            if ($borrow->status === 'pending' || $borrow->status === 'accepted') {
                // Buat data pengembalian buku
                $returnBook = new Restore();
                $returnBook->returndate = now();
                $returnBook->status = 'pending'; // Pengembalian masih menunggu pengecekan admin
                $returnBook->book_id = $borrow->book_id;
                $returnBook->user_id = $borrow->user_id;
                $returnBook->borrow_id = $borrow->id;

                // Simpan data pengembalian buku
                $returnBook->save();

                // Update status buku menjadi 'returned'
                $book = Book::find($borrow->book_id);
                $book->status = 'returned';
                $book->save();

                // Ubah status peminjaman menjadi selesai
                $borrow->status = 'completed';
                $borrow->save();

                return response()->json(['message' => 'Buku berhasil dikembalikan.']);
            } else {
                return response()->json(['message' => 'Buku tidak dalam status dipinjam.'], 400);
            }
        } else {
            return response()->json(['message' => 'Peminjaman tidak ditemukan.'], 404);
        }
    }


    // public function returncheck($id)
    // {
    //     // Cari data peminjaman
    //     $borrow = Borrow::findOrFail($id);

    //     // Tanggal hari ini
    //     $today = Carbon::now();

    //     // Jika tanggal hari ini lebih dari end date peminjaman
    //     if ($today->greaterThan($borrow->end_date)) {
    //         // Hitung selisih hari dari end date peminjaman
    //         $diffInDays = $today->diffInDays($borrow->end_date);

    //         // Hitung denda (misalnya denda per hari adalah 1000)
    //         $denda = $diffInDays * 1000;

    //         return response()->json(['denda' => $denda]);
    //     } else {
    //         return response()->json(['message' => 'Tidak ada denda']);
    //     }
    // }
}
