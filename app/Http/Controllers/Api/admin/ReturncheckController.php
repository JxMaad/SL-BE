<?php

namespace App\Http\Controllers\api\admin;

use App\Http\Controllers\Controller;
use App\Models\Borrow;
use App\Models\Restore;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReturncheckController extends Controller
{
    /**
     * Menghitung denda untuk pengembalian terlambat.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function returncheck($id)
    {
        // Cari pengembalian berdasarkan ID
        $returnBook = Restore::find($id);

        // Pastikan pengembalian ditemukan
        if ($returnBook) {
            // Periksa apakah pengembalian terlambat
            $dueDate = Carbon::parse($returnBook->borrowing_end);
            $returnDate = Carbon::parse($returnBook->returndate);
            if ($returnDate->greaterThan($dueDate)) {
                // Hitung jumlah hari terlambat
                $daysLate = $returnDate->diffInDays($dueDate);

                // Hitung denda
                $fine = $daysLate * 1000; // Misalnya, denda Rp 1000 per hari

                // Update status pengembalian menjadi 'overdue' dan simpan denda
                $returnBook->status = 'overdue';
                $returnBook->fine = $fine;
                $returnBook->save();

                return response()->json(['message' => 'Denda berhasil dihitung.', 'fine' => $fine]);
            } else {
                return response()->json(['message' => 'Tidak ada denda yang harus dibayar.']);
            }
        } else {
            return response()->json(['message' => 'Pengembalian tidak ditemukan.'], 404);
        }
    }
}
