<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\Borrow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    /**
     * Display a listing of the resource
     * 
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get all books regardless of the user
        $books = Book::latest()->paginate(6);

    // Calculate total books
    $totalBuku = Book::count();

    // Return with Api Resource
    return new BookResource(true, 'List Data Buku', $books, $totalBuku);
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /**
         * Validate request
         */
        $validator = Validator::make($request->all(), [
            'title' => 'required|unique:books',
            'synopsis' => 'required',
            'isbn' => 'nullable|string',
            'writer' => 'nullable|string',
            'page_amount' => 'nullable|integer',
            'stock_amount' => 'nullable|integer',
            'published' => 'required',
            'category' => 'nullable|string',
            'image' => 'required|file|mimes:jpeg,jpg,png|max:2000',

        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        //upload image
        $image = $request->file('image');
        $image->storeAs('public/books', $image->hashName());

        //create book
        $book = book::create([
            'title' => $request->input('title'),
            'synopsis' => $request->input('synopsis'),
            'isbn' => $request->input('isbn'),
            'writer' => $request->input('writer'),
            'page_amount' => $request->input('page_amount'),
            'stock_amount' => $request->input('stock_amount'),
            'published' => $request->input('published'),
            'category' => $request->input('category'),
            'image' => $image->hashName(),
        ]);

        // //push notifications firebase
        // fcm()
        //     ->toTopic('push-notifications')
        //     ->priority('normal')
        //     ->timeToLive(0)
        //     ->notification([
        //         'titel'         => 'Berita Baru !',
        //         'body'          => 'Disini akan menampilkan judul berita baru',
        //         'click_action'  => 'OPEN_ACTIVITY'
        //     ])
        //     ->send();

        if ($book) {
            //return success with Api Resource
            return new BookResource(true, 'Data book Berhasil Disimpan!', $book);
        }

        //return failed with Api Resource
        return new BookResource(false, 'Data book Gagal Disimpan!', null);
    }

    /**
     * Display the specified resource.
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        //get book$book
        $book = Book::whereId($id)->first();

        if ($book) {
            //return success with Api resource
            return new BookResource(true, 'Detail Data book', $book);
        }

        //return failed with Api Resource
        return new BookResource(false, 'Detail Data book Tidak Ditemukan!', null);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //get book
        $book = Book::findOrFail($id);

        /**
         * validate request
         */
        $validator = Validator::make($request->all(), [
            'title' => 'required|unique:books,title,' . $id,
            'synopsis' => 'required',
            'isbn' => 'required|string',
            'writer' => 'required|string',
            'page_amount' => 'required|integer',
            'stock_amount' => 'required|integer',
            'published' => 'required',
            'category' => 'required|string',
            'image' => 'required|file|mimes:jpeg,jpg,png|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        //check image update
        if ($request->file('image')) {

            //remove old image
            Storage::disk('local')->delete('public/books/' . basename($book->image));

            //upload new image
            $image = $request->file('image');
            $imagePath = $image->storeAs('public/books', $image->hashName());

            //update new image
            $book->update([
                'title' => $request->input('title'),
                'synopsis' => $request->input('synopsis'),
                'isbn' => $request->input('isbn'),
                'writer' => $request->input('writer'),
                'page_amount' => $request->input('page_amount'),
                'stock_amount' => $request->input('stock_amount'),
                'published' => $request->input('published'),
                'category' => $request->input('category'),
                'image' => $imagePath,
            ]);
        } else {
            //update no image
            $book->update([
                'title' => $request->input('title'),
                'synopsis' => $request->input('synopsis'),
                'isbn' => $request->input('isbn'),
                'writer' => $request->input('writer'),
                'page_amount' => $request->input('page_amount'),
                'stock_amount' => $request->input('stock_amount'),
                'published' => $request->input('published'),
                'category' => $request->input('category'),
            ]);
        }

        // check if the update was successful
        if ($book->wasChanged()) {
            // return success with Api Resource
            return new BookResource(true, 'Data book Berhasil Diupdate!', $book);
        }

        // return failed with Api Resource
        return new BookResource(false, 'Data book Gagal Diupdate!', null);
    }

    /**
     * Memperbarui status buku menjadi 'loaned' setelah disetujui oleh admin.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatusBook($id)
    {
        // Cari buku berdasarkan ID
        $book = Book::find($id);

        // Pastikan buku ditemukan
        if ($book) {
            // Periksa apakah status buku saat ini adalah 'available'
            if ($book->status === 'tersedia') {
                // Buat permohonan peminjaman baru
                $borrow = new Borrow();
                $borrow->borrowing_start = now();
                $borrow->borrowing_end = now()->addDays(7); // Misalnya, peminjaman selama 7 hari
                $borrow->status = 'tertunda'; // Permohonan peminjaman masih menunggu persetujuan admin
                $borrow->book_id = $book->id;
                $borrow->user_id = auth()->user()->id; // Anggap saja kita memiliki autentikasi user

                // Simpan permohonan peminjaman
                $borrow->save();

                // Update status buku menjadi 'loaned'
                $book->status = 'loaned';
                $book->save();

                return response()->json(['message' => 'Status buku berhasil diperbarui menjadi diterima untuk dipinjam.']);
            } else {
                return response()->json(['message' => 'Buku tidak tersedia untuk dipinjam saat ini.'], 400);
            }
        } else {
            return response()->json(['message' => 'Buku tidak ditemukan.'], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Cari buku berdasarkan ID
        $book = Book::find($id);

        // Jika buku ditemukan
        if ($book) {
            // Hapus gambar
            Storage::disk('local')->delete('public/books/' . basename($book->image));

            // Hapus buku dari database
            if ($book->delete()) {
                // Mengembalikan respons berhasil
                return new BookResource(true, 'Data buku berhasil dihapus!', null);
            }
        }
        // Mengembalikan respons gagal jika buku tidak ditemukan atau gagal dihapus
        return new BookResource(false, 'Data buku gagal dihapus!', null);
    }
}
