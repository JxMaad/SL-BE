<?php

namespace App\Http\Controllers\Api\Admin;

use Carbon\Carbon;
use App\Models\Borrow;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\BorrowResource;
use App\Http\Resources\RestoreResource;
use App\Models\Book;
use App\Models\Restore;

class RestoreController extends Controller
{
    public function index()
    {
        //get user
        $restore = Restore::when(request()->search, function ($restore) {
            $restore = $restore->where('name', 'like', '%' . request()->search . '%');
        })->with('roles')->latest()->paginate(5);

        //append query string to pagination links
        $restore->appends(['search' => request()->search]);

        //return with Api Resource
        return new RestoreResource(true, 'List Data User', $restore);
    }

    public function show(Restore $restore, $id)
    {
        //get borrow
        $restore = Restore::whereId($id)->first();

        if ($restore) {
            //return success with Api resource
            return new BorrowResource(true, 'Detail Data borrow', $restore);
        }

        //return failed with Api Resource
        return new BorrowResource(false, 'Detail Data borrow Tidak Ditemukan!', null);
    }

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
            if ($borrow->status === 'accepted') {
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

                // Ubah status pengembalian menjadi selesai dikembalikan
                $returnBook->status = 'returned';
                $returnBook->save();

                return response()->json(['message' => 'Buku berhasil dikembalikan.']);
            } else {
                return response()->json(['message' => 'Buku tidak dalam status dipinjam.'], 400);
            }
        } else {
            return response()->json(['message' => 'Peminjaman tidak ditemukan.'], 404);
        }
    }

    // /**
    //  * Mengupdate status user dari loading menjadi active.
    //  *
    //  * @param  int  $id
    //  * @return \Illuminate\Http\Response
    //  */
    // public function updateStatusReturn($id)
    // {
    //     // Temukan pengembalian berdasarkan ID
    //     $returnBook = Restore::find($id);

    //     // Pastikan pengembalian ditemukan
    //     if ($returnBook) {
    //         // Update status pengembalian menjadi 'active'
    //         $returnBook->status = '';
    //         $returnBook->save();

    //         return response()->json(['message' => 'Status pengembalian berhasil diperbarui menjadi aktif.']);
    //     } else {
    //         // Jika pengembalian tidak ditemukan, kembalikan respon error
    //         return response()->json(['message' => 'pengembalian tidak ditemukan.'], 404);
    //     }
    // }

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
