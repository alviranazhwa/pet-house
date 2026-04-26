<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Akun extends Model
{
    protected $table = 'akun';

    protected $fillable = [
        'kode_akun',
        'nama_akun',
        'kategori',
        'posisi_saldo',
        'is_aktif',
    ];

    public function jurnalDetail()
    {
        return $this->hasMany(JurnalDetail::class);
    }
    
}
