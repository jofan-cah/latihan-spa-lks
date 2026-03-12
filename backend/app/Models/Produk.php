<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produk extends Model
{
    use HasUuids, SoftDeletes;

    protected $table    = 'produk';
    protected $fillable = ['nama', 'kategori', 'harga', 'stok', 'foto'];
    protected $appends  = ['foto_url'];

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? asset('storage/' . $this->foto) : null;
    }
}
