<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailTransaksi;
use App\Models\Produk;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'bayar'              => 'required|numeric|min:0',
            'items'              => 'required|array|min:1',
            'items.*.produk_id'  => 'required|exists:produk,id',
            'items.*.qty'        => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $total   = 0;
            $details = [];

            foreach ($request->items as $item) {
                $produk = Produk::findOrFail($item['produk_id']);

                if ($produk->stok < $item['qty']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stok {$produk->nama} tidak cukup",
                    ], 422);
                }

                $subtotal = $produk->harga * $item['qty'];
                $total   += $subtotal;

                $details[] = [
                    'produk_id'    => $produk->id,
                    'qty'          => $item['qty'],
                    'harga_satuan' => $produk->harga,
                    'subtotal'     => $subtotal,
                ];

                $produk->decrement('stok', $item['qty']);
            }

            if ($request->bayar < $total) {
                return response()->json([
                    'success' => false,
                    'message' => 'Uang bayar kurang dari total',
                ], 422);
            }

            $transaksi = Transaksi::create([
                'user_id'   => $request->user()->id,
                'total'     => $total,
                'bayar'     => $request->bayar,
                'kembalian' => $request->bayar - $total,
            ]);

            foreach ($details as $d) {
                DetailTransaksi::create(array_merge($d, ['transaksi_id' => $transaksi->id]));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil',
                'data'    => [
                    'transaksi_id' => $transaksi->id,
                    'total'        => $total,
                    'bayar'        => $request->bayar,
                    'kembalian'    => $request->bayar - $total,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Transaksi gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function index()
    {
        $transaksi = Transaksi::with(['kasir', 'detail.produk'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $transaksi,
        ]);
    }
}
