<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriProduk extends Model
{
    protected $table = 'kategori_produk';

    protected $fillable = [
        'kode_kategori',
        'nama_kategori',
        'deskripsi',
        'is_aktif',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $last = self::orderBy('id', 'desc')->first();

            $urut = $last
                ? intval(substr($last->kode_kategori, -4)) + 1
                : 1;

            $model->kode_kategori = 'KAT-' . str_pad($urut, 4, '0', STR_PAD_LEFT);
        });
    }

    // RELATION
    public function produk()
    {
        return $this->hasMany(Produk::class);
    }
}
