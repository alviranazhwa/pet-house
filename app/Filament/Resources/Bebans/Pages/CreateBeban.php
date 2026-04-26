<?php

namespace App\Filament\Resources\Bebans\Pages;

use App\Filament\Resources\Bebans\BebanResource;
use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateBeban extends CreateRecord
{
    protected static string $resource = BebanResource::class;

    /**
     * Karena Resource modelnya Jurnal, kita override create biar:
     * - input form = field virtual (akun_beban_id, mode_pembayaran, nominal, keterangan)
     * - output = record jurnal + detail double entry
     */
    protected function handleRecordCreation(array $data): Jurnal
    {
        return DB::transaction(function () use ($data) {

            $tanggal = $data['tanggal'] ?? now()->toDateString();
            $akunBebanId = (int) ($data['akun_beban_id'] ?? 0);
            $mode = (string) ($data['mode_pembayaran'] ?? 'kas');
            $nominal = (float) ($data['nominal'] ?? 0);
            $ket = (string) ($data['keterangan'] ?? '');

            if ($akunBebanId <= 0) {
                throw ValidationException::withMessages(['akun_beban_id' => 'Akun beban wajib dipilih.']);
            }
            if ($nominal <= 0) {
                throw ValidationException::withMessages(['nominal' => 'Nominal harus lebih dari 0.']);
            }

            // akun kas/bank dari config mapping kamu
            $kodeKasBank = $mode === 'bank'
                ? (string) config('account_map.bank', '1002')
                : (string) config('account_map.kas', '1001');

            $akunKasBankId = (int) Akun::query()->where('kode_akun', $kodeKasBank)->value('id');

            if ($akunKasBankId <= 0) {
                throw ValidationException::withMessages([
                    'mode_pembayaran' => "Akun kas/bank ($kodeKasBank) tidak ditemukan di tabel akun.",
                ]);
            }

            // Ref transaksi BEB-YYYYMM-0001 (simple generator)
            $bulan = date('Ym', strtotime($tanggal));
            $lastRef = Jurnal::query()
                ->where('sumber_transaksi', 'BEBAN')
                ->where('referensi_transaksi', 'like', "BEB-$bulan-%")
                ->orderBy('id', 'desc')
                ->value('referensi_transaksi');

            $urut = 1;
            if ($lastRef) {
                $urut = ((int) substr($lastRef, -4)) + 1;
            }

            $ref = 'BEB-' . $bulan . '-' . str_pad((string) $urut, 4, '0', STR_PAD_LEFT);

            // idempotency guard (unique index kamu juga bakal ngejaga)
            $exists = Jurnal::query()
                ->where('sumber_transaksi', 'BEBAN')
                ->where('referensi_transaksi', $ref)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Referensi beban bentrok. Coba submit ulang.',
                ]);
            }

            $jurnal = Jurnal::create([
                'tanggal'             => $tanggal,
                'sumber_transaksi'    => 'BEBAN',
                'referensi_transaksi' => $ref,
                'user_id'             => Auth::id() ?? 1,
                // nomor_jurnal auto dibuat dari booted() di model Jurnal kamu
            ]);

            // Debit: Beban (6xxx)
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id'   => $akunBebanId,
                'debit'     => $nominal,
                'kredit'    => 0,
            ]);

            // Kredit: Kas/Bank
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id'   => $akunKasBankId,
                'debit'     => 0,
                'kredit'    => $nominal,
            ]);

            // Optional: kalau kamu mau nyimpen ket di jurnal, tapi tabel jurnal kamu belum ada kolom memo/keterangan.
            // Jadi untuk sekarang ket cuma jadi "catatan user", bisa kamu tambah kolom nanti kalau mau.

            return $jurnal;
        });
    }
}
