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
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Validator;

class RestoreController extends Controller
{
    public function index()
    {
        //get restore
        $restore = Restore::when(request()->search, function ($restore) {
            $restore = $restore->where('name', 'like', '%' . request()->search . '%');
        })->latest()->paginate(10);

        //append query string to pagination links
        $restore->appends(['search' => request()->search]);

        //return with Api Resource
        return new RestoreResource(true, 'List Data Pengembalian', $restore);
    }

    public function show(Restore $restore, $id)
    {
        //get restore
        $restore = Restore::whereId($id)->first();

        if ($restore) {
            //return success with Api resource
            return new RestoreResource(true, 'Detail Data Pengembalian', $restore);
        }

        //return failed with Api Resource
        return new RestoreResource(false, 'Detail Data Pengembalian Tidak Ditemukan!', null);
    }

    public function returnBookUser(Request $request, $id)
    {
        $borrow = Borrow::find($id);

        $validator = Validator::make($request->all(), [
            'returndate' => 'required',
            'book_id' => 'required',
            'user_id' => 'required',
            'borrow_id' => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'gagal input bro', 422]);
        }

        $borrow->update([
            'returndate' => $request->input('returndate'),
            'book_id' => $request->input('book_id'),
            'user_id' => $request->input('user_id'),
            'borrow_id' => $request->input('borrow_id'),
            'status' => $request->input('status'),
        ]);

        if ($borrow->wasChanged()) {
            // return success with Api Resource

            // return new BorrowResource(true, 'Data kontol Berhasil Diupdate!', $borrow);
            $returnBook = new Restore();
            $returnBook->returndate = $request->input('returndate');
            $returnBook->book_id = $borrow->book_id;
            $returnBook->user_id = $borrow->user_id;
            $returnBook->borrow_id = $borrow->id;
            $returnBook->status = 'pending'; // Pengembalian masih menunggu pengecekan admin

            // Simpan data pengembalian buku
            $returnBook->save();

            // Ubah status peminjaman menjadi selesai
            $borrow->status = 'completed';
            $borrow->save();

            // Ubah status pengembalian menjadi selesai dikembalikan
            $returnBook->status = 'returned';
            $returnBook->save();

            // Hitung denda jika ada
            $this->returnCheckFine($returnBook->id);

            return response()->json(['message' => 'Buku berhasil dikembalikan.']);
        }

        return new BorrowResource(false, 'Buku gagal dikembalikan', null);
    }

    public function returnCheckFine($id)
    {
        // Cari pengembalian berdasarkan ID
        $returnBook = Restore::find($id);

        // Pastikan pengembalian ditemukan
        if ($returnBook) {
            // Cari data peminjaman berdasarkan ID peminjaman
            $borrow = Borrow::find($returnBook->borrow_id);

            // Pastikan peminjaman ditemukan
            if ($borrow) {
                // Periksa apakah pengembalian terlambat
                $dueDate = Carbon::parse($borrow->borrowing_end);
                $returnDate = Carbon::parse($returnBook->returndate);

                // Periksa apakah pengembalian terlambat dari tanggal jatuh tempo peminjaman
                if ($returnDate->greaterThan($dueDate)) {
                    // Hitung jumlah hari terlambat
                    $daysLate = $returnDate->diffInDays($dueDate);

                    // Hitung denda
                    $fine = $daysLate * 1000; // Misalnya, denda Rp 1000 per hari

                    // Update status pengembalian menjadi 'overdue' dan simpan denda
                    $returnBook->status = 'overdue';
                    $returnBook->fine = $fine;
                    $returnBook->save();

                    return response()->json(['message' => 'Denda berhasil dihitung.', 'denda' => $fine]);
                } else {
                    return response()->json(['message' => 'Tidak ada denda yang harus dibayar.']);
                }
            } else {
                return response()->json(['message' => 'Peminjaman tidak ditemukan.'], 404);
            }
        } else {
            return response()->json(['message' => 'Pengembalian tidak ditemukan.'], 404);
        }
    }

    public function generateRestorePdf(Request $request)
    {
        $returnBook = Restore::all();

        $dataList = [];

        foreach ($returnBook as $returnBook) {
            $dataList[] = [
                'returndate' => $returnBook->returndate,
                'book_id' => $returnBook->book_id,
                'user_id' => $returnBook->user_id,
                'borrow_id' => $returnBook->borrow_id,
                'status' => $returnBook->status,
                // Tambahkan data lain yang diperlukan
            ];
        }

        // Load view PDF dengan data yang telah ditentukan
        $pdf = new Dompdf();

        $html = view('restore', compact('dataList'))->render();

        $pdf->loadHtml($html);

        // Render PDF
        $pdf->render();

        // Kembalikan file PDF sebagai respons
        return $pdf->stream('Laporan Pengembalian Buku Perbulan.pdf');
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
