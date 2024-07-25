<?php

use App\Http\Controllers\Api\Admin\BookController;
use App\Http\Controllers\Api\Admin\BorrowController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ExportBookController;
use App\Http\Controllers\Api\Admin\ExportUserController;
use App\Http\Controllers\Api\admin\GuestBookController;
use App\Http\Controllers\Api\Admin\ImportUserController;
use App\Http\Controllers\Api\Admin\MemberController;
use App\Http\Controllers\api\admin\ReadController;
use App\Http\Controllers\api\admin\RestoreController;
use App\Http\Controllers\api\admin\ReturncheckController;
use App\Http\Controllers\api\admin\VerificationUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
dpraer

|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});*/

// Exclude login and register routes from auth:api middleware
Route::post('/login', [App\Http\Controllers\Api\Auth\LoginController::class, 'index']);

// Route::post('/register', [UserController::class, 'store']);

// Apply auth:api middleware to all routes
Route::middleware('auth:api')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'getAllData']);

    Route::post('/logout', [App\Http\Controllers\Api\Auth\LoginController::class, 'logout']);

    Route::prefix('user')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware(['permission:users.index', 'role:admin|pustakawan']);
        Route::get('/{id}', [UserController::class, 'show'])->middleware(['permission:users.index']);
        Route::post('/create', [UserController::class, 'store'])->middleware(['permission:users.create', 'role:admin|pustakawan']);
        Route::post('/{id}/update', [UserController::class, 'update'])->middleware(['permission:users.edit']);
        Route::delete('/{id}', [UserController::class, 'destroy'])->middleware(['permission:users.delete', 'role:admin|pustakawan']);
        Route::put('/{id}/update-status', [UserController::class, 'updateStatusUser'])->middleware('permission:users.edit', 'role:admin|pustakawan');
        Route::post('/import-user', [ImportUserController::class, 'import'])->middleware(['role:admin|pustakawan']);
    });

    Route::prefix('book')->group(function () {
        Route::get('/', [BookController::class, 'index'])->middleware(['permission:books.index']);
        Route::get('/{id}', [BookController::class, 'show'])->middleware(['permission:books.index']);
        Route::post('/create', [BookController::class, 'store'])->middleware(['permission:books.create', 'role:admin|pustakawan']);
        Route::post('/{id}/update', [BookController::class, 'update'])->middleware(['permission:books.edit', 'role:admin|pustakawan']);
        Route::delete('/{id}', [BookController::class, 'destroy'])->middleware(['permission:books.delete', 'role:admin|pustakawan']);
        Route::put('/{id}/update-status', [BookController::class, 'updateStatusBook'])->middleware('permission:books.edit', 'role:admin|pustakawan');
        Route::post('/import-book', [ImportUserController::class, 'importBook'])->middleware(['role:admin|pustakawan']);
    });

    Route::prefix('borrow')->group(function () {
        Route::get('/', [BorrowController::class, 'index'])->middleware(['permission:borrows.index']);
        Route::get('/{id}', [BorrowController::class, 'show'])->middleware(['permission:borrows.index']);
        Route::post('/create', [BorrowController::class, 'store'])->middleware(['permission:borrows.create', ]);
        Route::put('/{id}/update-status', [BorrowController::class, 'updateStatusBorrow'])->middleware(['permission:borrows.edit', 'role:admin|pustakawan']);
        Route::post('/generateBorrowPdf', [BorrowController::class, 'generateBorrow'])->middleware(['permission:borrows.index', 'role:admin|pustakawan']);
        Route::delete('/{id}', [BorrowController::class, 'destroy'])->middleware(['permission:borrows.delete', 'role:admin|pustakawan']);
        Route::get('/{user_id}/index-borrow', [BorrowController::class, 'indexBorrowUserId'])->middleware(['role:anggota']);
    });

    Route::prefix('restore')->group(function () {
        Route::get('/', [RestoreController::class, 'index'])->middleware(['permission:restores']);
        Route::get('/{id}/check-fine', [RestoreController::class, 'returnCheckFine'])->middleware(['permission:restores', 'role:admin|pustakawan']);
        Route::get('/{id}', [RestoreController::class, 'show'])->middleware(['permission:restores']);
        Route::post('/{id}',[RestoreController::class, 'returnBookUser'])->middleware(['permission:restores', 'role:admin|pustakawan']);
        Route::delete('/{id}', [RestoreController::class, 'destroy'])->middleware(['permission:restores', 'role:admin|pustakawan']);
        Route::put('/{id}/update-status', [RestoreController::class, 'updateStatusReturn'])->middleware(['permission:restores', 'role:admin|pustakawan']);
        Route::get('/{user_id}/index-restore', [RestoreController::class, 'indexRestoreUserId'])->middleware(['role:anggota']);
        Route::put('/{id}/update-fine', [RestoreController::class, 'updateStatusFine'])->middleware(['permission:restores', 'role:admin|pustakawan']);
    });

    Route::prefix('guestbook')->group(function () {
        Route::get('/', [GuestBookController::class, 'indexGuest'])->middleware(['permission:guest.index']);
        Route::post('/create', [GuestBookController::class, 'storeGuest'])->middleware(['permission:guest.create']);
        Route::post('/{id}/update', [GuestBookController::class, 'updateGuest'])->middleware(['permission:guest.edit']);
        Route::delete('/{id}', [GuestBookController::class, 'destroyGuest'])->middleware(['permission:guest.delete']);
    });

    Route::get('/alluser/export', [ExportUserController::class, 'export'])->middleware(['role:admin|pustakawan']);

    Route::get('/allbook/export', [ExportBookController::class, 'exportBook'])->middleware(['role:admin|pustakawan']);

    Route::get('/book-restore/index-fine', [RestoreController::class, 'indexFine'])->middleware(['permission:restores','role:anggota']);

    Route::post('/generateRestorePdf', [RestoreController::class, 'generateRestore'])->middleware(['permission:restores', 'role:admin|pustakawan']);

    Route::prefix('readbook')->group(function () {
        Route::get('/', [ReadController::class, 'readbookindex'])->middleware(['permission:read.index']);
        Route::post('/create', [ReadController::class, 'readbookstore'])->middleware(['permission:read.create', 'role:admin|pustakawan']);
        Route::get('/{id}', [ReadController::class, 'readbookshow'])->middleware(['permission:read.index']);
        Route::post('/update/{id}', [ReadController::class, 'readbookupdate'])->middleware(['permission:read.edit', 'role:admin|pustakawan']);
        Route::delete('/{id}', [ReadController::class, 'readbookdestroy'])->middleware(['permission:read.delete', 'role:admin|pustakawan']);
    });
});