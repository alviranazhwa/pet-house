<?php

namespace App\Filament\Resources\Pembelians\Pages;

use App\Filament\Resources\Pembelians\PembelianResource;
use App\Models\Jurnal;
use App\Models\MutasiPersediaan;
use App\Models\PembelianDetail;
use App\Models\Produk;
use App\Services\JournalPoster;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditPembelian extends EditRecord
{
    protected static string $resource = PembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Biar repeater 'items' keisi saat edit
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;

        $data['items'] = $record->details()
            ->orderBy('id')
            ->get()
            ->map(fn ($d) => [
                'detail_id'            => $d->id,
                'produk_id'            => $d->produk_id,
                'nama_item'            => $d->nama_item,
                'mutasi_persediaan_id' => $d->mutasi_persediaan_id,
                'qty'                  => (int) $d->qty,
                'harga'                => (float) $d->harga,
                'subtotal'             => (float) $d->subtotal,
            ])
            ->toArray();

        // default mode pembayaran di UI (jika user gak ubah)
        $data['mode_pembayaran'] = 'kas';

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_id'] = Auth::id();

        $items = $this->data['items'] ?? [];
        $data['total'] = collect($items)->sum(fn ($r) => (float) ($r['subtotal'] ?? 0));

        unset($data['items']);
        unset($data['mode_pembayaran']); // bukan kolom pembelian

        return $data;
    }

    protected function afterSave(): void
    {
        $pembelian = $this->record;

        DB::transaction(function () use ($pembelian) {

            $items = $this->data['items'] ?? [];

            // --- Ambil detail lama + mapping mutasi untuk cleanup
            $oldDetails = PembelianDetail::query()
                ->where('pembelian_id', $pembelian->id)
                ->get();

            $oldById = $oldDetails->keyBy('id');

            $incomingIds = collect($items)
                ->pluck('detail_id')
                ->filter()
                ->map(fn ($v) => (int) $v)
                ->values();

            // --- Delete detail yang hilang + hapus mutasinya
            $toDelete = $oldDetails->whereNotIn('id', $incomingIds);
            foreach ($toDelete as $d) {
                if (!empty($d->mutasi_persediaan_id)) {
                    MutasiPersediaan::query()->where('id', $d->mutasi_persediaan_id)->delete();
                }
                $d->delete();
            }

            // --- Upsert details + sync mutasi
            foreach ($items as $row) {
                $detailId  = (int) ($row['detail_id'] ?? 0);
                $produkId  = (int) ($row['produk_id'] ?? 0);
                $qty       = (int) ($row['qty'] ?? 0);
                $harga     = (float) ($row['harga'] ?? 0);

                if ($produkId <= 0 || $qty <= 0) {
                    continue;
                }

                $produk = Produk::find($produkId);

                if ($detailId > 0 && $oldById->has($detailId)) {
                    // update existing detail
                    $detail = $oldById->get($detailId);
                    $detail->update([
                        'produk_id' => $produkId,
                        'nama_item' => $row['nama_item'] ?? ($produk?->nama_produk),
                        'qty'       => $qty,
                        'harga'     => $harga,
                        // subtotal auto via booted saving
                    ]);
                } else {
                    // create new detail
                    $detail = PembelianDetail::create([
                        'pembelian_id' => $pembelian->id,
                        'produk_id'    => $produkId,
                        'nama_item'    => $row['nama_item'] ?? ($produk?->nama_produk),
                        'qty'          => $qty,
                        'harga'        => $harga,
                        'mutasi_persediaan_id' => null,
                    ]);
                }

                // sync mutasi (idempotent by mutasi_persediaan_id)
                if (!empty($detail->mutasi_persediaan_id)) {
                    MutasiPersediaan::query()
                        ->where('id', $detail->mutasi_persediaan_id)
                        ->update([
                            'produk_id'   => $produkId,
                            'kode_produk' => $produk?->kode_produk ?? '-',
                            'nama_produk' => $produk?->nama_produk ?? ($detail->nama_item ?? '-'),
                            'satuan'      => $produk?->satuan ?? 'pcs',
                            'qty'         => $qty,
                            'tipe'        => MutasiPersediaan::TIPE_MASUK,
                            'ref_tipe'    => 'PEMBELIAN',
                            'ref_id'      => $pembelian->id,
                            'harga'       => $harga,
                            'tanggal'     => $pembelian->tanggal ?? now()->toDateString(),
                            'keterangan'  => 'Pembelian: ' . ($pembelian->kode_pembelian ?? $pembelian->id),
                            'updated_at'  => now(),
                        ]);
                } else {
                    $mutasi = MutasiPersediaan::create([
                        'produk_id'   => $produkId,
                        'kode_produk' => $produk?->kode_produk ?? '-',
                        'nama_produk' => $produk?->nama_produk ?? ($detail->nama_item ?? '-'),
                        'satuan'      => $produk?->satuan ?? 'pcs',
                        'qty'         => $qty,
                        'tipe'        => MutasiPersediaan::TIPE_MASUK,
                        'ref_tipe'    => 'PEMBELIAN',
                        'ref_id'      => $pembelian->id,
                        'harga'       => $harga,
                        'tanggal'     => $pembelian->tanggal ?? now()->toDateString(),
                        'keterangan'  => 'Pembelian: ' . ($pembelian->kode_pembelian ?? $pembelian->id),
                    ]);

                    $detail->update([
                        'mutasi_persediaan_id' => $mutasi->id,
                    ]);
                }
            }

            // --- REPOST JURNAL (hapus yang lama dulu)
            $ref = !empty($pembelian->kode_pembelian)
                ? (string) $pembelian->kode_pembelian
                : 'PEMBELIAN#' . (string) $pembelian->id;

            Jurnal::query()
                ->where('sumber_transaksi', 'PEMBELIAN')
                ->where('referensi_transaksi', $ref)
                ->delete(); // cascade delete jurnal_detail

            $mode = $this->data['mode_pembayaran'] ?? 'kas';

            app(JournalPoster::class)->postPembelian(
                $pembelian,
                $mode,
                'Auto jurnal pembelian (repost)'
            );
        });
    }
}
