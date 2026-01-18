<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutasiPersediaan extends Model
{
    protected $table = 'mutasi_persediaan';

    protected $fillable = [
        'produk_id',
        'kode_produk',
        'nama_produk',
        'satuan',
        'qty',
        'tipe',
        'ref_tipe',
        'ref_id',
        'harga',
        'tanggal',
        'keterangan',
    ];
    
    public const TIPE_MASUK = 'MASUK';
    public const TIPE_KELUAR = 'KELUAR';
    public const TIPE_PENYESUAIAN = 'PENYESUAIAN';

    public static function stokSaatIni(int $produkId): int
    {
        $masuk = self::query()
            ->where('produk_id', $produkId)
            ->where('tipe', self::TIPE_MASUK)
            ->sum('qty');

        $keluar = self::query()
            ->where('produk_id', $produkId)
            ->where('tipe', self::TIPE_KELUAR)
            ->sum('qty');

        // PENYESUAIAN: kalau kamu pakai qty positif untuk tambah dan negatif untuk kurang,
        // tinggal tambahkan sum penyesuaian di sini.
        $penyesuaian = self::query()
            ->where('produk_id', $produkId)
            ->where('tipe', self::TIPE_PENYESUAIAN)
            ->sum('qty');

        return (int) $masuk - (int) $keluar + (int) $penyesuaian;
    }

    protected $casts = [
        'tanggal' => 'date',
        'harga'   => 'decimal:2',
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
