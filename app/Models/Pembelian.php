<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Pembelian extends Model
{
    protected $table = 'pembelian';

    protected $fillable = [
        'kode_pembelian',
        'no_invoice',
        'tanggal',
        'supplier_id',
        'total',
        'keterangan',
        'bukti_url',
        'user_id',
    ];

    /**
     * Auto-generate kode pembelian
     * Format: PBL-YYYYMM-0001
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            $bulan = Carbon::now()->format('Ym');

            $last = self::where('kode_pembelian', 'like', "PBL-{$bulan}-%")
                ->orderBy('id', 'desc')
                ->first();

            $urut = $last
                ? intval(substr($last->kode_pembelian, -4)) + 1
                : 1;

            $model->kode_pembelian =
                'PBL-' . $bulan . '-' . str_pad($urut, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * ======================
     * RELATIONS
     * ======================
     */

    public function details()
    {
        return $this->hasMany(PembelianDetail::class, 'pembelian_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * ======================
     * HELPER
     * ======================
     */

    public function hasBukti(): bool
    {
        return ! empty($this->bukti_url);
    }

    public function buktiUrl(): ?string
    {
        return $this->bukti_url
            ? asset('storage/' . $this->bukti_url)
            : null;
    }
}
