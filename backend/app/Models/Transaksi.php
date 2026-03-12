<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasUuids;

    protected $table    = 'transaksi';
    protected $fillable = ['user_id', 'total', 'bayar', 'kembalian'];

    public function kasir()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detail()
    {
        return $this->hasMany(DetailTransaksi::class, 'transaksi_id');
    }
}
