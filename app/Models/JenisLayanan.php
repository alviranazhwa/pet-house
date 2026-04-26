<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisLayanan extends Model
{
    protected $table = 'jenis_layanan';

    protected $fillable = [
        'kode_layanan',
        'nama_layanan',
        'tarif',
        'keterangan',
    ];

    /**
     * Booted model event
     * Digunakan untuk auto-generate kode layanan
     * Format: GRM-0001
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            $last = self::orderBy('id', 'desc')->first();

            $urut = $last
                ? intval(substr($last->kode_layanan, -4)) + 1
                : 1;

            $model->kode_layanan = 'GRM-' . str_pad($urut, 4, '0', STR_PAD_LEFT);
        });
    }
}
