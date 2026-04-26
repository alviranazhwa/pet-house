<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Jurnal extends Model
{
    protected $table = 'jurnal';

    protected $fillable = [
        'nomor_jurnal',
        'tanggal',
        'sumber_transaksi',
        'referensi_transaksi',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $tgl = $model->tanggal ? Carbon::parse($model->tanggal) : Carbon::now();
            $bulan = $tgl->format('Ym');

            $last = self::where('nomor_jurnal', 'like', "JU-$bulan-%")
                ->orderBy('id', 'desc')
                ->first();

            $urut = $last
                ? intval(substr($last->nomor_jurnal, -4)) + 1
                : 1;

            // Hanya set nomor_jurnal kalau belum diisi manual
            if (empty($model->nomor_jurnal)) {
                $model->nomor_jurnal = 'JU-' . $bulan . '-' . str_pad($urut, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function detail()
    {
        return $this->hasMany(JurnalDetail::class, 'jurnal_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
