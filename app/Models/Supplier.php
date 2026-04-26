<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'supplier';

    protected $fillable = [
        'kode_supplier',
        'nama_supplier',
        'telepon',
        'email',
        'alamat',
        'is_aktif',
        'keterangan',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $last = self::orderBy('id', 'desc')->first();

            $urut = $last
                ? intval(substr($last->kode_supplier, -4)) + 1
                : 1;

            $model->kode_supplier = 'SUP-' . str_pad($urut, 4, '0', STR_PAD_LEFT);
        });
    }
}
