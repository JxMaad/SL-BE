<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use App\Models\Borrow;
use App\Http\Resources\BorrowResource;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Restore;
use Illuminate\Support\Facades\Auth; // Tambahkan ini
use Dompdf\Dompdf;

class BorrowController extends Controller
{
    public function index()
    {
        // Ambil data peminjaman dengan relasi user dan book
        $borrows = Borrow::with('user', 'book')->latest()->paginate(10);

        // Return with Api Resource
        return new BorrowResource(true, 'List Data peminjaman', $borrows);
    }

    public function indexBorrowUserId(Request $request)
    {
        $user = $request->user();
    
        // Ambil data peminjaman dengan relasi user dan book berdasarkan user ID
        $borrow = Borrow::with('user', 'book')->where('user_id', $user->id)->latest()->get();
    
        // Mengembalikan data dalam format JSON
        return response()->json([
            'status' => 'success',
            'data' => $borrow
        ], 200);
    }

    public function store(Request $request)
    {
        // Dapatkan ID pengguna yang sedang login
        $userId = Auth::id();

        // Validasi request
        $request->validate([
            'borrowing_start' => 'required',
            'borrowing_end' => 'required',
            'book_id' => 'required|exists:books,id',
        ], [
            'book_id.required' => 'ID buku diperlukan.',
            'book_id.exists' => 'Buku tidak ditemukan.',
            'borrowing_start.required' => 'Tanggal awal minjam wajib di isi',
            'borrowing_end.required' => 'Tanggal akhir minjma wajib di isi',
        ]);

        // Dapatkan buku dari database
        $book = Book::findOrFail($request->book_id);

        // Periksa stok buku
        if ($book->stock_amount <= 0) {
            return response()->json(['error' => 'Maaf, stok buku tidak mencukupi'], 400);
        }

        // Create borrow jika buku tersedia
        $borrow = Borrow::create([
            'borrowing_start' => $request->input('borrowing_start'),
            'borrowing_end' => $request->input('borrowing_end'),
            'book_id' => $request->book_id, // Gunakan ID buku dari request
            'user_id' => $userId, // Gunakan ID pengguna dari pengguna yang sedang login
        ]);

        if ($borrow) {
            $book->stock_amount -= 1;
            $book->save();

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
            return new BorrowResource(true, 'Detail Data peminjaman', $borrow);
        }

        //return failed with Api Resource
        return new BorrowResource(false, 'Detail Data borrow Tidak Ditemukan!', null);
    }

    public function updateStatusBorrow($id)
    {
        // Cari permohonan peminjaman berdasarkan ID
        $borrow = Borrow::find($id);

        // Pastikan permohonan peminjaman ditemukan
        if ($borrow) {
            // Periksa apakah status permohonan peminjaman saat ini adalah 'pending'
            if ($borrow->status === 'Menunggu') {
                // Update status permohonan peminjaman
                $borrow->borrowing_start = $borrow->borrowing_start;
                $borrow->borrowing_end = $borrow->borrowing_end;
                $borrow->status = 'Diterima';
                $borrow->save();

                // Update stok buku dan status jika stok sudah habis
                $book = Book::find($borrow->book_id);
                if ($book) {
                    // Periksa apakah stok buku habis
                    if ($book->stock_amount == 0) {
                        $book->status = 'Habis';
                    }

                    // Simpan perubahan pada buku
                    $book->save();
                }

                return response()->json(['message' => "Status permohonan peminjaman berhasil diperbarui."]);
            } else {
                return response()->json(['message' => 'Permohonan peminjaman tidak dalam status menunggu.'], 400);
            }
        } else {
            return response()->json(['message' => 'Permohonan peminjaman tidak ditemukan.'], 404);
        }
    }

    public function generateBorrow(Request $request)
    {
        $borrow = Borrow::all();

        $borrows = Borrow::with('user', 'book');

        $dataList = [];

        foreach ($borrow as $borrows) {
            $dataList[] = [
                'borrowing_start' => $borrows->borrowing_start,
                'borrowing_end' => $borrows->borrowing_end,
                'book_id' => $borrows->book_id,
                'user_id' => $borrows->user_id,
                'status' => $borrows->status,
                // Tambahkan data lain yang diperlukan
            ];
        }

        // Load view PDF dengan data yang telah ditentukan
        $pdf = new Dompdf();

        $html = view('borrow', compact('dataList'))->render();

        $pdf->loadHtml($html);

        // Render PDF
        $pdf->render();

        // Kembalikan file PDF sebagai respons
        return $pdf->stream('Laporan Peminjaman Buku Perbulan.pdf');
    }

    public function destroy($id)
    {
        // Cari buku berdasarkan ID
        $borrow = Borrow::findOrFail($id);

        // Jika buku ditemukan
        if ($borrow) {
            // Hapus buku dari database
            if ($borrow->delete()) {
                // Mengembalikan respons berhasil
                return new BorrowResource(true, 'Data peminjaman berhasil dihapus!', null);
            }
        }
        // Mengembalikan respons gagal jika buku tidak ditemukan atau gagal dihapus
        return new BorrowResource(false, 'Data peminjaman gagal dihapus!', null);
    }

    /**
     * Memperbarui status buku menjadi 'loaned' setelah disetujui oleh admin.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function updateStatusBorrow($id, $borrow)
    // {
    //     // Mencari buku berdasarkan ID
    //     $book = Book::find($id);

    //     // Pastikan permohonan buku ditemukan
    //     if ($book) {
    //         // Periksa apakah status permohonan buku saat ini adalah 'available'
    //         if ($book->status === 'available')
    //         // Update permohonan peminjaman 
    //         $borrow->borrowing_start = now();
    //         $borrow->borrowing_end = now()->addDays(7); // Misalnya, peminjaman selama 7 hari
    //         $borrow->status = 'pending'; // Permohonan peminjaman masih menunggu persetujuan admin
    //         $borrow->book_id = $book->id;
    //         $borrow->user_id = auth()->user()->id; // Anggap saja kita memiliki autentikasi user

    //         // Simpan permohonan peminjaman
    //         $borrow->save();

    //         // Update status peminjaman menjadi 'accepted'
    //         $borrow->status = 'accepted';
    //         $borrow->save();

    //         return response()->json(['message' => 'Permohonan peminjaman berhasil dibuat.']);
    //     } else {
    //         return response()->json(['message' => 'Terjadi kesalahan saat memproses permohonan peminjaman.'], 500);
    //     }
    // }
}
