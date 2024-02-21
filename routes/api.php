<?php

use App\Http\Controllers\Api\Admin\BookController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\api\admin\RestoreController;
use App\Http\Controllers\BorrowController;
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
Route::post('/register', [UserController::class, 'store']);

// Apply auth:api middleware to all routes
Route::middleware('auth:api')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'getAllData']);

    Route::post('/logout', [App\Http\Controllers\Api\Auth\LoginController::class, 'logout']);

    Route::prefix('user')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware(['permission:users.index', 'role:admin|pustakawan']);
        Route::get('/{id}', [UserController::class, 'show'])->middleware('permission:users.index');
        Route::post('/create', [UserController::class, 'store'])->middleware('permission:users.create');
        Route::put('/{id}/update', [UserController::class, 'update'])->middleware('permission:users.edit');
        Route::delete('/{id}', [UserController::class, 'destroy'])->middleware(['permission:users.delete', 'role:admin|pustakawan']);
    });

    Route::prefix('book')->group(function () {
        Route::get('/', [BookController::class, 'index'])->middleware(['permission:books.index']);
        Route::get('/{id}', [BookController::class, 'show'])->middleware('permission:books.index');
        Route::post('/create', [BookController::class, 'store'])->middleware(['permission:books.create', 'role:admin|pustakawan']);
        Route::put('/{id}/update', [BookController::class, 'update'])->middleware(['permission:books.edit', 'role:admin|pustakawan']);
        Route::delete('/{id}', [BookController::class, 'destroy'])->middleware(['permission:books.delete', 'role:admin|pustakawan']);
    });

    Route::prefix('borrow')->group(function () {
        Route::get('/', [BorrowController::class, 'index'])->middleware(['permission:borrows.index', 'role:admin|pustakawan']);
        Route::get('/{id}', [BorrowController::class, 'show'])->middleware('permission:borrows.index');
        Route::post('/create', [BorrowController::class, 'store'])->middleware(['permission:borrows.create', ]);
        Route::put('/{id}/update', [BorrowController::class, 'update'])->middleware(['permission:borrows.edit']);
        Route::delete('/{id}', [BorrowController::class, 'destroy'])->middleware(['permission:borrows.delete']);
    });

    Route::prefix('restore')->group(function () {
        Route::get('/returnbook', [RestoreController::class, 'returnBook'])->middleware(['permission:restores']);
    });
});