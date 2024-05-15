<?php

namespace App\Http\Controllers\Api\Admin;

use Carbon\Carbon;
use App\Models\Borrow;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\RestoreResource;
use App\Models\Book;
use App\Models\Restore;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Validator;

class RestoreController extends Controller
{
    public function index()
    {
        // Ambil data peminjaman dengan relasi user dan book
        $restore = Restore::with('user', 'book', 'borrow')->latest()->paginate(10);

        // Return with Api Resource
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
        // Temukan peminjaman berdasarkan ID
        $borrow = Borrow::find($id);

        // Validasi input
        $validator = Validator::make($request->all(), [
            'returndate' => 'required',
            'book_id' => 'required',
            'user_id' => 'required',
            'borrow_id' => 'required',
            'status' => 'required',
        ]);

        // Jika validasi gagal
        if ($validator->fails() || !$borrow) {
            return response()->json(['message' => 'Gagal input bro'], 422);
        }

        // Update peminjaman dengan data pengembalian yang baru
        $borrow->update([
            'returndate' => $request->input('returndate'),
            'book_id' => $request->input('book_id'),
            'user_id' => $request->input('user_id'),
            'borrow_id' => $request->input('borrow_id'),
            'status' => $request->input('status'),
        ]);

        // Simpan data pengembalian buku
        $returnBook = new Restore();
        $returnBook->returndate = $request->input('returndate');
        $returnBook->book_id = $borrow->book_id;
        $returnBook->user_id = $borrow->user_id;
        $returnBook->borrow_id = $borrow->id;
        $returnBook->status = 'Menunggu'; // Pengembalian masih menunggu pengecekan admin
        $returnBook->save();

        // Ubah status pengembalian menjadi selesai dikembalikan
        $returnBook->status = 'Dikembalikan';
        $returnBook->save();
        
        // Ubah status peminjaman menjadi selesai
        $borrow->status = 'Selesai';
        $borrow->save();

        // Hitung denda jika ada
        $this->returnCheckFine($returnBook->id);

        if ($returnBook) {
            //return success with Api resource
            return new RestoreResource(true, 'Berhasil Dikembalikan', $returnBook);
        }

        //return failed with Api Resource
        return new RestoreResource(false, 'Gagal dikembalikan', null);
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
                    $returnBook->status = 'Denda';
                    $returnBook->fine = $fine;
                    $returnBook->save();

                    return response()->json(['message' => 'Denda berhasil dihitung.', 'Denda' => $fine]);
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

    public function destroy($id)
    {
        // Cari buku berdasarkan ID
        $restore = Restore::findOrFail($id);

        // Jika buku ditemukan
        if ($restore) {
            // Hapus buku dari database
            if ($restore->delete()) {
                // Mengembalikan respons berhasil
                return new RestoreResource(true, 'Data pengembalian berhasil dihapus!', null);
            }
        }
        // Mengembalikan respons gagal jika buku tidak ditemukan atau gagal dihapus
        return new RestoreResource(false, 'Data pengembalian gagal dihapus!', null);
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
