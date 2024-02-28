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
        $books = Book::with('user')->withCount('views')->when(request()->search, function ($books) {
            $books = $books->where('name', 'like', '%' . request()->search . '%');
        })->latest()->paginate(5);

        // Append query string to pagination links
        $books->appends(['search' => request()->search]);

        // Return with Api Resource
        return new BookResource(true, 'List Data Buku', $books);
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
    public function update(Request $request, book $book)
    {
        /**
         * validate request
         */
        $validator = Validator::make($request->all(), [
            'title' => 'required|unique:books', $book->id,
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

        //check image update
        if ($request->file('image')) {

            //remove old image
            Storage::disk('local')->delete('public/books/' . basename($book->image));

            //upload new image
            $image = $request->file('image');
            $image->storeAs('public/books', $image->hashName());

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
                'image' => $image->hashName(),
            ]);

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

        if ($book) {
            //ruturn success with Api Resource
            return new BookResource(true, 'Data book Berhasil Diupdate!', $book);
        }

        //return failed with Api Resource
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
            if ($book->status === 'available') {
                // Buat permohonan peminjaman baru
                $borrow = new Borrow();
                $borrow->borrowing_start = now();
                $borrow->borrowing_end = now()->addDays(7); // Misalnya, peminjaman selama 7 hari
                $borrow->status = 'pending'; // Permohonan peminjaman masih menunggu persetujuan admin
                $borrow->book_id = $book->id;
                $borrow->user_id = auth()->user()->id; // Anggap saja kita memiliki autentikasi user

                // Simpan permohonan peminjaman
                $borrow->save();

                // Update status buku menjadi 'loaned'
                $book->status = 'loaned';
                $book->save();

                return response()->json(['message' => 'Permohonan peminjaman buku berhasil diajukan.']);
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
