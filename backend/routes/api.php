<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProdukController;
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/login', [AuthController::class, 'login']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Produk (semua role bisa lihat, admin bisa CRUD)
    Route::get('/produk',         [ProdukController::class, 'index']);
    Route::post('/produk',        [ProdukController::class, 'store']);
    Route::post('/produk/{id}',   [ProdukController::class, 'update']); // POST karena ada file
    Route::delete('/produk/{id}', [ProdukController::class, 'destroy']);

    // Transaksi
    Route::get('/transaksi',  [TransaksiController::class, 'index']);
    Route::post('/transaksi', [TransaksiController::class, 'store']);

    // Manajemen Kasir (admin only)
    Route::get('/kasir',         [UserController::class, 'index']);
    Route::post('/kasir',        [UserController::class, 'store']);
    Route::delete('/kasir/{id}', [UserController::class, 'destroy']);
});
