<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembelianDetail extends Model
{
    protected $table = 'pembelian_detail';

    protected $fillable = [
        'pembelian_id',
        'produk_id',
        'nama_item',
        'mutasi_persediaan_id', // ✅ FIX: typo
        'qty',
        'harga',
        'subtotal',
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            $model->subtotal = (float) $model->qty * (float) $model->harga;
        });
    }

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    // optional helper relation kalau mau dipakai nanti
    public function mutasiPersediaan()
    {
        return $this->belongsTo(MutasiPersediaan::class, 'mutasi_persediaan_id');
    }

    public function total(): float
    {
        return (float) $this->subtotal;
    }
}
