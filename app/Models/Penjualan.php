<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Penjualan extends Model
{
    protected $table = 'penjualan';

   protected $fillable = [
    'kode_penjualan',
    'tanggal',
    'total',
    'keterangan',
    'user_id',

    // midtrans fields
    'payment_status',
    'snap_token',
    'snap_token_created_at',
    'transaction_id',
    'payment_type',
    'gross_amount',
    'paid_at',
    'posted_at',
    'cart_snapshot',
];

    protected static function booted()
    {
        static::creating(function ($model) {
            $bulan = Carbon::now()->format('Ym');

            $last = self::where('kode_penjualan', 'like', "PJL-$bulan-%")
                ->orderBy('id', 'desc')
                ->first();

            $urut = $last
                ? intval(substr($last->kode_penjualan, -4)) + 1
                : 1;

            $model->kode_penjualan = 'PJL-' . $bulan . '-' . str_pad($urut, 4, '0', STR_PAD_LEFT);
        });
    }

    public function details()
    {
        return $this->hasMany(DetailPenjualan::class, 'penjualan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
