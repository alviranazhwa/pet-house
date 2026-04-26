<?php

namespace App\Filament\Resources\Pembelians\Pages;

use App\Filament\Resources\Pembelians\PembelianResource;
use App\Models\MutasiPersediaan;
use App\Models\PembelianDetail;
use App\Models\Produk;
use App\Services\JournalPoster;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreatePembelian extends CreateRecord
{
    protected static string $resource = PembelianResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        $items = $this->data['items'] ?? [];
        $data['total'] = collect($items)->sum(fn ($r) => (float) ($r['subtotal'] ?? 0));

        unset($data['items']); // bukan kolom pembelian

        // mode_pembayaran bukan kolom pembelian (kita ambil dari $this->data)
        unset($data['mode_pembayaran']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {

            /** @var \App\Models\Pembelian $pembelian */
            $pembelian = static::getModel()::create($data);

            $items = $this->data['items'] ?? [];
            if (!empty($items)) {
                foreach ($items as $row) {
                    $produkId = (int) ($row['produk_id'] ?? 0);
                    $qty      = (int) ($row['qty'] ?? 0);
                    $harga    = (float) ($row['harga'] ?? 0);

                    if ($produkId <= 0 || $qty <= 0) {
                        continue;
                    }

                    $produk = Produk::find($produkId);

                    // buat detail
                    $detail = PembelianDetail::create([
                        'pembelian_id' => $pembelian->id,
                        'produk_id'    => $produkId,
                        'nama_item'    => $row['nama_item'] ?? ($produk?->nama_produk),
                        'qty'          => $qty,
                        'harga'        => $harga,
                        // subtotal auto via booted
                        'mutasi_persediaan_id' => null,
                    ]);

                    // buat mutasi MASUK + snapshot
                    $mutasi = MutasiPersediaan::create([
                        'produk_id'   => $produkId,
                        'kode_produk' => $produk?->kode_produk ?? '-',
                        'nama_produk' => $produk?->nama_produk ?? ($row['nama_item'] ?? '-'),
                        'satuan'      => $produk?->satuan ?? 'pcs',
                        'qty'         => $qty,
                        'tipe'        => MutasiPersediaan::TIPE_MASUK,
                        'ref_tipe'    => 'PEMBELIAN',
                        'ref_id'      => $pembelian->id,
                        'harga'       => $harga,
                        'tanggal'     => $pembelian->tanggal ?? now()->toDateString(),
                        'keterangan'  => 'Pembelian: ' . ($pembelian->kode_pembelian ?? $pembelian->id),
                    ]);

                    // link balik
                    $detail->update([
                        'mutasi_persediaan_id' => $mutasi->id,
                    ]);
                }
            }

            // === AUTO JURNAL ===
            $mode = $this->data['mode_pembayaran'] ?? 'kas'; // 'kas'|'bank'
            app(JournalPoster::class)->postPembelian(
                $pembelian,
                $mode,
                'Auto jurnal pembelian'
            );

            return $pembelian;
        });
    }
}
