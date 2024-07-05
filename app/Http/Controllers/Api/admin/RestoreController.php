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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RestoreController extends Controller
{
    public function index(Request $request)
    {
        // Ambil parameter query untuk filtering
        $status = $request->query('status');

        // Ambil data peminjaman dengan relasi user dan book
        $query = Restore::with('user', 'book', 'borrow')->latest();

        // Filter berdasarkan status
        if (!is_null($status)) {
            $query->where('status', $status);
        }

        // Dapatkan data dengan pagination
        $restores = $query->paginate(10);

        // Return with Api Resource
        return new RestoreResource(true, 'List Data Pengembalian', $restores);
    }

    public function indexRestoreUserId(Request $request)
    {
        $user = $request->user();

        // Ambil data peminjaman dengan relasi user dan book berdasarkan user ID
        $restore = Restore::with('user', 'book', 'borrow')->where('user_id', $user->id)->latest()->get();

        // Mengembalikan data dalam format JSON
        return response()->json([
            'status' => 'success',
            'data' => $restore
        ], 200);
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
            'returndate' => 'required|date',
            'status' => 'required|string',
        ]);

        // // Jika validasi gagal atau peminjaman tidak ditemukan
        // if ($validator->fails() || !$borrow) {
        //     return response()->json(['message' => 'Gagal input bro-bro'], 422);
        // }

        // Update peminjaman dengan data pengembalian yang baru
        $borrow->update([
            'returndate' => $request->input('returndate'),
            'status' => $request->input('status'),
        ]);

        // Simpan data pengembalian buku
        $returnBook = new Restore();
        $returnBook->returndate = $request->input('returndate');
        $returnBook->book_id = $borrow->book_id;
        $returnBook->user_id = $borrow->user_id;
        $returnBook->borrow_id = $borrow->id;
        $returnBook->status = $request->input('status'); // Gunakan status yang diinputkan
        $returnBook->save();

        // Cari buku terkait menggunakan book_id dari permohonan pengembalian
        $book = Book::find($returnBook->book_id);

        // Tambah stok buku
        $book->stock_amount += 1;
        $book->status = 'Tersedia';
        $book->save();

        // Cari permohonan peminjaman terkait menggunakan book_id
        $borrow = Borrow::where('book_id', $returnBook->book_id)->first();

        // Pastikan permohonan peminjaman ditemukan
        if ($borrow) {
            // Ubah status peminjaman menjadi 'Selesai'
            $borrow->status = 'Selesai';
            $borrow->save();
        }

        // Hitung denda jika ada
        $this->returnCheckFine($returnBook->id);

        if ($returnBook) {
            //return success with Api resource
            return new RestoreResource(true, 'Berhasil Dikembalikan', $returnBook);
        }

        //return failed with Api Resource
        return new RestoreResource(false, 'Gagal dikembalikan', null);
    }

    public function updateStatusReturn($id)
    {
        // Cari permohonan pengembalian berdasarkan ID
        $returnBook = Restore::find($id);

        // Pastikan permohonan pengembalian ditemukan
        if ($returnBook) {
            // Periksa apakah status permohonan pengembalian saat ini adalah 'Menunggu'
            if ($returnBook->status === 'Dikembalikan') {;

                // Cari buku terkait menggunakan book_id dari permohonan pengembalian
                $book = Book::find($returnBook->book_id);

                // Tambah stok buku
                $book->stock_amount += 1;
                $book->status = 'Tersedia';
                $book->save();

                // Cari permohonan peminjaman terkait menggunakan book_id
                $borrow = Borrow::where('Id', $returnBook->book_id)->first();

                // Pastikan permohonan peminjaman ditemukan
                if ($borrow) {
                    // Ubah status peminjaman menjadi 'Selesai'
                    $borrow->status = 'Selesai';
                    $borrow->save();
                }

                return response()->json(['message' => "Status pengembalian berhasil diperbarui."]);
            } else {
                return response()->json(['message' => 'Permohonan pengembalian tidak dalam status menunggu.'], 400);
            }
        } else {
            return response()->json(['message' => 'Permohonan pengembalian tidak ditemukan.'], 404);
        }
    }

    public function updateStatusFine($id)
    {
        // Cari permohonan pengembalian berdasarkan ID
        $fine = Restore::find($id);

        // Pastikan permohonan pengembalian ditemukan
        if ($fine !== null) {
            // Periksa apakah status permohonan pengembalian saat ini adalah 'Denda Belum Dibayar'
            if ($fine->status === 'Denda Belum Dibayar') {
                // Ubah status pengembalian menjadi 'Denda Dibayar'
                $fine->status = 'Denda Dibayar';
                $fine->save();

                // Cari permohonan peminjaman terkait menggunakan borrow_id
                $borrow = Borrow::where('id', $fine->borrow_id)->first();

                // Pastikan permohonan peminjaman ditemukan
                if ($borrow !== null) {
                    // Ubah status peminjaman menjadi 'Selesai'
                    $borrow->status = 'Selesai';
                    $borrow->save();
                }

                return response()->json(['message' => 'Status Denda berhasil diperbarui bro.'], 200);
            } else {
                return response()->json(['message' => 'Permohonan pengembalian tidak dalam status menunggu, sabar yah.'], 400);
            }
        } else {
            return response()->json(['message' => 'Permohonan pengembalian tidak ditemukan.'], 404);
        }
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
                    $returnBook->status = 'Denda Belum Dibayar';
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

    public function generateRestore(Request $request)
    {
        $restore = Restore::all();

        $restores = Restore::with('user', 'book', 'borrow')->get(); // Mengambil semua data peminjaman dengan relasi user dan book

        $dataList = [];

        foreach ($restore as $restores) {
            $dataList[] = [
                'returndate' => $restores->returndate,
                'book_id' => $restores->book->title, // Mengambil title dari relasi book
                'user_id' => $restores->user->name, // Mengambil name dari relasi user
                'borrow_id' => $restores->borrow_id,
                'status' => $restores->status,
                // Tambahkan data lain yang diperlukan
            ];
        }

        // Load view PDF dengan data yang telah ditentukan
        $pdf = new Dompdf();

        // Render view 'restore' dengan compact data 'dataList' ke dalam HTML
        $html = view('restore', compact('dataList'))->render();

        // Load HTML ke dalam Dompdf
        $pdf->loadHtml($html);

        // Render PDF (proses rendering HTML menjadi PDF)
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

    public function indexFine()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            // Ambil data restore yang terkait dengan user yang sedang login
            $restores = Restore::where('user_id', $user->id)->with('borrow')->get();

            if ($restores->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada data denda yang ditemukan untuk user ini'
                ], 404);
            }

            // Buat collection untuk menampung hasil akhir
            $data = $restores->filter(function ($restore) {
                $returnDate = new \DateTime($restore->returndate);
                $borrowingEnd = new \DateTime($restore->borrow->borrowing_end);

                // Hanya ambil data jika return_date melewati borrowing_end
                return $returnDate > $borrowingEnd;
            })->map(function ($restore) {
                $returnDate = new \DateTime($restore->returndate);
                $borrowingEnd = new \DateTime($restore->borrow->borrowing_end);

                // Hitung jumlah hari keterlambatan
                $day_borrow_missed = $returnDate->diff($borrowingEnd)->days;

                // Hanya buat entri jika ada keterlambatan
                if ($day_borrow_missed > 0) {
                    $status_keterlambatan = "Terlambat $day_borrow_missed hari";

                    return [
                        'returndate' => $restore->returndate,
                        'borrowing_end' => $restore->borrow->borrowing_end,
                        'status_keterlambatan' => $status_keterlambatan,
                        'book_id' => $restore->book_id,
                        'fine' => $restore->fine,
                    ];
                }
            })->filter(); // Filter out null values

            if ($data->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada data denda yang ditemukan yang telah melewati tanggal peminjaman'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data denda berhasil diambil',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data denda',
                'error' => $e->getMessage()
            ], 500);
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
