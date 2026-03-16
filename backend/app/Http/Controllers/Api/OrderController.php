<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Produk;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'tipe'       => 'required|in:dine_in,takeaway',
            'nomor_meja' => 'nullable|string|max:10',
            'catatan'    => 'nullable|string',
            'items'      => 'required|array|min:1',
            'items.*.produk_id' => 'required|exists:produk,id',
            'items.*.qty'       => 'required|integer|min:1',
        ]);

        // Generate nomor antrian
        $lastOrder = Order::whereDate('created_at', today())->orderByDesc('id')->first();
        if ($lastOrder) {
            $last = (int) substr($lastOrder->nomor_antrian, 1);
            $nomor = 'A' . str_pad($last + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nomor = 'A001';
        }

        $total = 0;
        $itemsData = [];
        foreach ($request->items as $item) {
            $produk = Produk::findOrFail($item['produk_id']);
            $subtotal = $produk->harga * $item['qty'];
            $total += $subtotal;
            $itemsData[] = [
                'produk_id' => $produk->id,
                'qty'       => $item['qty'],
                'harga'     => $produk->harga,
                'subtotal'  => $subtotal,
            ];
        }

        $order = Order::create([
            'customer_id'   => $request->user()->id,
            'nomor_antrian' => $nomor,
            'tipe'          => $request->tipe,
            'nomor_meja'    => $request->nomor_meja,
            'status'        => 'pending',
            'total'         => $total,
            'catatan'       => $request->catatan,
        ]);

        $order->items()->createMany($itemsData);

        return response()->json([
            'nomor_antrian' => $nomor,
            'order'         => $order->load('items.produk'),
        ], 201);
    }

    public function index(Request $request)
    {
        $query = Order::with(['customer', 'items.produk'])->latest();

        if ($request->tipe) {
            $query->where('tipe', $request->tipe);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        return response()->json($query->get());
    }

    public function show($id)
    {
        $order = Order::with(['customer', 'items.produk'])->findOrFail($id);
        return response()->json($order);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,proses,selesai',
        ]);

        $order = Order::findOrFail($id);
        $order->update(['status' => $request->status]);

        return response()->json($order);
    }

    public function myOrders(Request $request)
    {
        $orders = Order::with('items.produk')
            ->where('customer_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function antrian()
    {
        $orders = Order::with('customer')
            ->whereIn('status', ['proses', 'selesai'])
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get(['id', 'nomor_antrian', 'status', 'tipe', 'updated_at', 'customer_id']);

        return response()->json($orders);
    }
}
