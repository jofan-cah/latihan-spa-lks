<?php

namespace Database\Seeders;

use App\Models\Produk;
use Illuminate\Database\Seeder;

class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        $produk = [
            ['nama' => 'Es Teh',      'kategori' => 'Minuman', 'harga' => 5000,  'stok' => 100],
            ['nama' => 'Nasi Goreng', 'kategori' => 'Makanan', 'harga' => 15000, 'stok' => 50],
            ['nama' => 'Mie Goreng',  'kategori' => 'Makanan', 'harga' => 13000, 'stok' => 50],
            ['nama' => 'Air Mineral', 'kategori' => 'Minuman', 'harga' => 3000,  'stok' => 200],
        ];
        foreach ($produk as $p) {
            Produk::create($p);
        }
    }
}
