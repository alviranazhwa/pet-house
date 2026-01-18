<?php

namespace App\Services;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalPoster
{
    /**
     * Posting jurnal untuk PEMBELIAN (TUNAI).
     *
     * Rules (minimal):
     *   Debit  : Persediaan (atau Pembelian kalau nanti kamu pakai periodic)
     *   Kredit : Kas / Bank
     *
     * @param  \App\Models\Pembelian  $pembelian
     * @param  string  $modePembayaran  'kas' | 'bank'  (key di config/account_map.php)
     * @param  string|null $memo
     * @return \App\Models\Jurnal
     */
    public function postPembelian($pembelian, string $modePembayaran = 'kas', ?string $memo = null): Jurnal
    {
        // === idempotency guard: jangan sampai keposting 2x ===
        $ref = $this->refPembelian($pembelian);

        $existing = Jurnal::query()
            ->where('sumber_transaksi', 'PEMBELIAN')
            ->where('referensi_transaksi', $ref)
            ->first();

        if ($existing) {
            return $existing; // atau throw kalau kamu pengen strict
        }

        // total pembelian: sesuaikan dengan field kamu (misal total/total_harga/grand_total)
        $total = (float) ($pembelian->total ?? $pembelian->grand_total ?? $pembelian->total_harga ?? 0);

        if ($total <= 0) {
            throw ValidationException::withMessages([
                'pembelian' => 'Total pembelian tidak valid untuk posting jurnal (<= 0).',
            ]);
        }

        // akun debit (default: persediaan)
        $debitKey = config('account_map.pembelian_pakai_persediaan', true) ? 'persediaan' : 'pembelian';

        $akunDebitId  = $this->akunIdByConfigKey($debitKey);
        $akunKreditId = $this->akunIdByConfigKey($modePembayaran); // kas / bank

        // build lines
        $lines = [
            [
                'akun_id' => $akunDebitId,
                'debit'   => $total,
                'kredit'  => 0,
                'memo'    => $memo ?? 'Pembelian - pencatatan persediaan',
            ],
            [
                'akun_id' => $akunKreditId,
                'debit'   => 0,
                'kredit'  => $total,
                'memo'    => $memo ?? 'Pembelian - pembayaran',
            ],
        ];

        $this->assertBalanced($lines);

        // IMPORTANT:
        // Panggil method ini DI DALAM DB::transaction dari flow pembelian kamu.
        // Jadi di sini kita gak bikin transaction baru.
        $jurnal = Jurnal::create([
            'tanggal'            => $pembelian->tanggal ?? now()->toDateString(),
            'sumber_transaksi'   => 'PEMBELIAN',
            'referensi_transaksi'=> $ref,
            'user_id'            => Auth::id() ?? $pembelian->user_id ?? 1,
        ]);

        foreach ($lines as $line) {
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id'   => $line['akun_id'],
                'debit'     => $line['debit'],
                'kredit'    => $line['kredit'],
            ]);
        }

        return $jurnal;
    }

    /**
     * Buat referensi transaksi yang konsisten (string).
     * Prefer kode_pembelian kalau ada, biar enak dibaca.
     */
    protected function refPembelian($pembelian): string
    {
        if (!empty($pembelian->kode_pembelian)) {
            return (string) $pembelian->kode_pembelian;
        }

        return 'PEMBELIAN#' . (string) $pembelian->id;
    }

    /**
     * Ambil akun_id dari config/account_map.php via key (kas/bank/persediaan/dll).
     * config berisi kode_akun (contoh '1001'), tapi jurnal_detail butuh akun.id.
     */
    protected function akunIdByConfigKey(string $key): int
    {
        $kode = config("account_map.$key");

        if (!$kode) {
            throw ValidationException::withMessages([
                'account_map' => "Mapping akun untuk key [$key] belum di-set di config/account_map.php",
            ]);
        }

        $akunId = Akun::query()
            ->where('kode_akun', (string) $kode)
            ->value('id');

        if (!$akunId) {
            throw ValidationException::withMessages([
                'akun' => "Akun dengan kode_akun [$kode] (key [$key]) tidak ditemukan di tabel akun.",
            ]);
        }

        return (int) $akunId;
    }

    /**
     * Validasi debit = kredit (toleransi 0.01 biar aman rounding).
     */
    protected function assertBalanced(array $lines): void
    {
        $totalDebit  = 0.0;
        $totalKredit = 0.0;

        foreach ($lines as $l) {
            $totalDebit  += (float) ($l['debit'] ?? 0);
            $totalKredit += (float) ($l['kredit'] ?? 0);
        }

        if (abs($totalDebit - $totalKredit) > 0.01) {
            throw ValidationException::withMessages([
                'jurnal' => "Jurnal tidak balance. Debit=$totalDebit, Kredit=$totalKredit",
            ]);
        }
    }

    public function postPenjualan($penjualan, string $modePembayaran = 'kas', ?string $memo = null): Jurnal
    {
        // idempotency guard
        $ref = $this->refPenjualan($penjualan);

        $existing = Jurnal::query()
            ->where('sumber_transaksi', 'PENJUALAN')
            ->where('referensi_transaksi', $ref)
            ->first();

        if ($existing) {
            return $existing;
        }

        $total = (float) ($penjualan->total ?? 0);
        if ($total <= 0) {
            throw ValidationException::withMessages([
                'penjualan' => 'Total penjualan tidak valid untuk posting jurnal (<= 0).',
            ]);
        }

        $akunKasBankId     = $this->akunIdByConfigKey($modePembayaran); // kas/bank
        $akunPendapatanId  = $this->akunIdByConfigKey('pendapatan_penjualan');

        // --- OPTIONAL perpetual (HPP & Persediaan) ---
        // hitung HPP dari mutasi_persediaan yang terkait penjualan ini
        $hpp = 0.0;
        if (method_exists($penjualan, 'details')) {
            foreach ($penjualan->details as $d) {
                $hargaPokok = (float) ($d->mutasiPersediaan?->harga ?? 0);
                $qty        = (int) ($d->qty ?? 0);
                $hpp       += $hargaPokok * $qty;
            }
        } elseif (method_exists($penjualan, 'detail')) {
            foreach ($penjualan->detail as $d) {
                $hargaPokok = (float) ($d->mutasiPersediaan?->harga ?? 0);
                $qty        = (int) ($d->qty ?? 0);
                $hpp       += $hargaPokok * $qty;
            }
        }

        $lines = [
            // Kas/Bank (D)
            ['akun_id' => $akunKasBankId, 'debit' => $total, 'kredit' => 0],
            // Pendapatan (K)
            ['akun_id' => $akunPendapatanId, 'debit' => 0, 'kredit' => $total],
        ];

        // Kalau HPP > 0, kita anggap perpetual: HPP (D), Persediaan (K)
        if ($hpp > 0.01) {
            $akunHppId        = $this->akunIdByConfigKey('hpp');
            $akunPersediaanId = $this->akunIdByConfigKey('persediaan');

            $lines[] = ['akun_id' => $akunHppId,        'debit' => $hpp, 'kredit' => 0];
            $lines[] = ['akun_id' => $akunPersediaanId, 'debit' => 0,   'kredit' => $hpp];
        }

        $this->assertBalanced($lines);

        $jurnal = Jurnal::create([
            'tanggal'             => $penjualan->tanggal ?? now()->toDateString(),
            'sumber_transaksi'    => 'PENJUALAN',
            'referensi_transaksi' => $ref, // PJL-YYYYMM-0001
            'user_id'             => Auth::id() ?? $penjualan->user_id ?? 1,
        ]);

        foreach ($lines as $line) {
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id'   => $line['akun_id'],
                'debit'     => $line['debit'],
                'kredit'    => $line['kredit'],
            ]);
        }

        return $jurnal;
    }

    protected function refPenjualan($penjualan): string
    {
        if (!empty($penjualan->kode_penjualan)) {
            return (string) $penjualan->kode_penjualan;
        }
        return 'PENJUALAN#' . (string) $penjualan->id;
    }

}
