<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPenjualan extends Model
{
    // ✅ SESUAI MIGRATION: Schema::create('penjualan_detail' ...)
    protected $table = 'penjualan_detail';

    protected $fillable = [
        'penjualan_id',
        'mutasi_persediaan_id',
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

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }

    public function mutasiPersediaan()
    {
        return $this->belongsTo(MutasiPersediaan::class, 'mutasi_persediaan_id');
    }
}
