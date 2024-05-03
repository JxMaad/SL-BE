<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use App\Models\Borrow;
use App\Http\Resources\BorrowResource;
use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Support\Facades\Auth; // Tambahkan ini
use App\Models\User;
use Dompdf\Dompdf;

class BorrowController extends Controller
{
    public function index()
    {
        // Ambil data peminjaman dengan relasi user dan book
        $borrows = Borrow::with('user', 'book')->latest()->paginate(5);

        // Return with Api Resource
        return new BorrowResource(true, 'List Data borrows', $borrows);
    }

    public function store(Request $request)
    {
        // Dapatkan ID pengguna yang sedang login
        $userId = Auth::id();

        // Validasi request
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'amount_borrowed' => 'required|integer|min:1', // Tambahkan validasi untuk jumlah yang dipinjam
        ], [
            'book_id.required' => 'ID buku diperlukan.',
            'book_id.exists' => 'Buku tidak ditemukan.',
            'amount_borrowed.required' => 'Jumlah buku yang dipinjam diperlukan.',
            'amount_borrowed.integer' => 'Jumlah buku yang dipinjam harus berupa bilangan bulat.',
            'amount_borrowed.min' => 'Jumlah buku yang dipinjam harus minimal 1.',
        ]);

        // Dapatkan buku dari database
        $book = Book::findOrFail($request->book_id);

        // Periksa stok buku
        if ($book->stock_amount < $request->amount_borrowed) {
            return response()->json(['error' => 'Maaf, stok buku tidak mencukupi'], 400);
        }

        // Create borrow jika buku tersedia
        $borrow = Borrow::create([
            'book_id' => $request->input('book_id'),
            'user_id' => $userId, // Gunakan ID pengguna dari pengguna yang sedang login
            'amount_borrowed' => $request->input('amount_borrowed'), // Simpan jumlah yang dipinjam
        ]);

        if ($borrow) {
            // Kurangi stok buku sesuai dengan jumlah yang dipinjam
            $book->stock_amount -= $request->input('amount_borrowed');

            // Simpan perubahan stok buku
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
            return new BorrowResource(true, 'Detail Data borrow', $borrow);
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
            if ($borrow->status === 'pending') {
                // Update status permohonan peminjaman
                $borrow->borrowing_start = now();
                $borrow->borrowing_end = now()->addDays(7); // Misalnya, peminjaman selama 7 hari
                $borrow->status = 'accepted';
                $borrow->save();

                return response()->json(['message' => "Status permohonan peminjaman berhasil diperbarui."]);
            } else {
                return response()->json(['message' => 'Permohonan peminjaman tidak dalam status pending.'], 400);
            }
        } else {
            return response()->json(['message' => 'Permohonan peminjaman tidak ditemukan.'], 404);
        }
    }

    public function generateBorrow(Request $request)
    {
        $borrow = Borrow::all();

        $dataList = [];

        foreach ($borrow as $borrow) {
            $dataList[] = [
                'borrowing_start' => $borrow->borrowing_start,
                'borrowing_end' => $borrow->borrowing_end,
                'book_id' => $borrow->book_id,
                'user_id' => $borrow->user_id,
                'amount_borrowed' => $borrow->amount_borrowed,
                'status' => $borrow->status,
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
