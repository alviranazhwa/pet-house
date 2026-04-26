<?php

namespace App\Services;

use App\Models\DetailPenjualan;
use App\Models\MutasiPersediaan;
use App\Models\Penjualan;
use App\Models\Produk;
use Illuminate\Support\Facades\DB;

class PenjualanFinalizer
{
    public function __construct(
        protected JournalPoster $poster,
        protected StockAverageService $stockAverage,
    ) {
    }

    public function finalize(Penjualan $penjualan, string $modePembayaran = 'bank', ?string $memo = null): Penjualan
    {
        return DB::transaction(function () use ($penjualan, $modePembayaran, $memo) {
            /** @var Penjualan|null $p */
            $p = Penjualan::query()
                ->where('id', $penjualan->id)
                ->lockForUpdate()
                ->first();

            if (!$p || !empty($p->posted_at)) {
                return $p ?? $penjualan;
            }

            $items = $this->snapshotItems($p);
            if (empty($items)) {
                return $p;
            }

            $alreadyHasDetails = DetailPenjualan::query()
                ->where('penjualan_id', $p->id)
                ->exists();

            if (!$alreadyHasDetails) {
                foreach ($items as $row) {
                    $this->createDetailAndStockMutation($p, $row);
                }
            }

            $p->load(['details.mutasiPersediaan']);

            $this->poster->postPenjualan($p, $modePembayaran, $memo);

            $p->posted_at = now();
            $p->save();

            return $p;
        });
    }

    protected function snapshotItems(Penjualan $penjualan): array
    {
        $snapshot = $penjualan->cart_snapshot ?? null;

        if (!is_string($snapshot) || $snapshot === '') {
            return [];
        }

        $items = json_decode($snapshot, true);

        return is_array($items) ? $items : [];
    }

    protected function createDetailAndStockMutation(Penjualan $penjualan, array $row): void
    {
        $produkId = (int) ($row['produk_id'] ?? 0);
        $qty = (int) ($row['qty'] ?? 0);

        if ($produkId <= 0 || $qty <= 0) {
            return;
        }

        $produk = Produk::where('is_aktif', true)->findOrFail($produkId);
        $hppPerUnit = $this->stockAverage->averageCostBefore($produk->id, $penjualan->tanggal);

        if ($hppPerUnit <= 0) {
            $hppPerUnit = (float) ($produk->harga_beli ?? 0);
        }

        $mutasi = MutasiPersediaan::create([
            'produk_id'   => $produk->id,
            'kode_produk' => $produk->kode_produk,
            'nama_produk' => $produk->nama_produk,
            'satuan'      => $produk->satuan ?? 'pcs',
            'qty'         => $qty,
            'tipe'        => MutasiPersediaan::TIPE_KELUAR,
            'ref_tipe'    => 'PENJUALAN',
            'ref_id'      => $penjualan->id,
            'harga'       => $hppPerUnit,
            'tanggal'     => $penjualan->tanggal ?? now()->toDateString(),
            'keterangan'  => $penjualan->kode_penjualan,
        ]);

        $hargaJual = (float) ($row['harga_jual'] ?? $produk->harga_jual ?? 0);

        DetailPenjualan::create([
            'penjualan_id'         => $penjualan->id,
            'mutasi_persediaan_id' => $mutasi->id,
            'qty'                  => $qty,
            'harga'                => $hargaJual,
            'subtotal'             => $hargaJual * $qty,
        ]);
    }
}
