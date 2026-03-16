<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProdukController;
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ──────────────────────────────────────────────
// PUBLIC
// ──────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);
Route::get('/produk', [ProdukController::class, 'index']);
Route::get('/orders/antrian', [OrderController::class, 'antrian']);

// Customer auth
Route::post('/customer/register', [CustomerAuthController::class, 'register']);
Route::post('/customer/login',    [CustomerAuthController::class, 'login']);

// ──────────────────────────────────────────────
// CUSTOMER (token customer)
// ──────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/customer/me',      [CustomerAuthController::class, 'me']);
    Route::get('/customer/orders',  [OrderController::class, 'myOrders']);
    Route::post('/orders',          [OrderController::class, 'store']);
});

// ──────────────────────────────────────────────
// STAFF — kasir & admin
// ──────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'staff'])->group(function () {
    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Transaksi manual kasir
    Route::get('/transaksi',  [TransaksiController::class, 'index']);
    Route::post('/transaksi', [TransaksiController::class, 'store']);

    // Pesanan masuk (self-order customer)
    Route::get('/orders',               [OrderController::class, 'index']);
    Route::get('/orders/{id}',          [OrderController::class, 'show']);
    Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
});

// ──────────────────────────────────────────────
// ADMIN only
// ──────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'staff', 'admin'])->group(function () {
    // Produk CRUD
    Route::post('/produk',        [ProdukController::class, 'store']);
    Route::post('/produk/{id}',   [ProdukController::class, 'update']);
    Route::delete('/produk/{id}', [ProdukController::class, 'destroy']);

    // Manajemen kasir
    Route::get('/kasir',         [UserController::class, 'index']);
    Route::post('/kasir',        [UserController::class, 'store']);
    Route::delete('/kasir/{id}', [UserController::class, 'destroy']);
});
