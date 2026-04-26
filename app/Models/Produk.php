<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $table = 'produk';

    protected $fillable = [
        'kode_produk',
        'nama_produk',
        'kategori_produk_id',
        'satuan',
        'harga_beli',
        'harga_jual',
        'is_aktif',
        'keterangan',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $last = self::orderBy('id', 'desc')->first();

            $urut = $last
                ? intval(substr($last->kode_produk, -4)) + 1
                : 1;

            $model->kode_produk = 'PRD-' . str_pad($urut, 4, '0', STR_PAD_LEFT);
        });
    }

    // RELATION

    // public function persediaan()
    // {
    //     return $this->hasOne(mutasi_persediaan::class);
    // }

    public function kategori()
    {
        return $this->belongsTo(\App\Models\KategoriProduk::class, 'kategori_produk_id');
    }

}
