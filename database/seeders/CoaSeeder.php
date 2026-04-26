<?php

namespace Database\Seeders;

use App\Models\Akun;
use Illuminate\Database\Seeder;

class CoaSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['kode_akun' => '1001', 'nama_akun' => 'Kas', 'kategori' => 'aset', 'posisi_saldo' => 'debit'],
            ['kode_akun' => '1002', 'nama_akun' => 'Bank', 'kategori' => 'aset', 'posisi_saldo' => 'debit'],
            ['kode_akun' => '1201', 'nama_akun' => 'Persediaan Barang Dagang', 'kategori' => 'aset', 'posisi_saldo' => 'debit'],
            ['kode_akun' => '2001', 'nama_akun' => 'Utang Usaha', 'kategori' => 'kewajiban', 'posisi_saldo' => 'kredit'],
            ['kode_akun' => '3001', 'nama_akun' => 'Modal Pemilik', 'kategori' => 'modal', 'posisi_saldo' => 'kredit'],
            ['kode_akun' => '4001', 'nama_akun' => 'Pendapatan Penjualan', 'kategori' => 'pendapatan', 'posisi_saldo' => 'kredit'],
            ['kode_akun' => '5000', 'nama_akun' => 'Pembelian', 'kategori' => 'beban', 'posisi_saldo' => 'debit'],
            ['kode_akun' => '5001', 'nama_akun' => 'Harga Pokok Penjualan', 'kategori' => 'beban', 'posisi_saldo' => 'debit'],
            ['kode_akun' => '6001', 'nama_akun' => 'Beban Operasional', 'kategori' => 'beban', 'posisi_saldo' => 'debit'],
        ];

        foreach ($accounts as $account) {
            Akun::updateOrCreate(
                ['kode_akun' => $account['kode_akun']],
                $account + ['is_aktif' => true],
            );
        }
    }
}
