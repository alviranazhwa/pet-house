<?php

namespace App\Http\Controllers;

use App\Models\DetailPenjualan;
use App\Models\MutasiPersediaan;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Services\MidtransService;
use App\Services\JournalPoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request, MidtransService $midtrans, JournalPoster $poster)
    {
        $data = $request->all();

        // 1) Verify signature
        if (!$midtrans->verifySignature($data)) {
            return response()->json(['message' => 'Invalid signature'], Response::HTTP_FORBIDDEN);
        }

        $orderId = (string) ($data['order_id'] ?? '');
        if ($orderId === '') {
            return response()->json(['message' => 'Missing order_id'], Response::HTTP_BAD_REQUEST);
        }

        /** @var Penjualan|null $penjualan */
        $penjualan = Penjualan::query()->where('kode_penjualan', $orderId)->first();
        if (!$penjualan) {
            return response()->json(['message' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $newStatus = $midtrans->mapStatus(
            (string) ($data['transaction_status'] ?? ''),
            (string) ($data['fraud_status'] ?? null)
        );

        // 2) Update payment fields (safe)
        $penjualan->payment_status = $newStatus;
        $penjualan->transaction_id = $data['transaction_id'] ?? $penjualan->transaction_id;
        $penjualan->payment_type   = $data['payment_type'] ?? $penjualan->payment_type;
        $penjualan->gross_amount   = $data['gross_amount'] ?? $penjualan->gross_amount;

        if ($newStatus === 'PAID' && empty($penjualan->paid_at)) {
            $penjualan->paid_at = now();
        }

        $penjualan->save();

        // 3) FINALIZE idempotent (posted_at sebagai guard) + LOCK biar anti dobel webhook
        if ($newStatus === 'PAID') {
            DB::transaction(function () use ($penjualan, $poster) {

                // lock row penjualan biar kalau webhook masuk 2x gak balapan
                $p = Penjualan::query()
                    ->where('id', $penjualan->id)
                    ->lockForUpdate()
                    ->first();

                if (!$p) return;

                // sudah pernah finalize? skip
                if (!empty($p->posted_at)) {
                    return;
                }

                // ===== ambil snapshot cart =====
                $snapshotRaw = $p->cart_snapshot ?? null;
                $items = [];

                if (is_string($snapshotRaw) && $snapshotRaw !== '') {
                    $decoded = json_decode($snapshotRaw, true);
                    if (is_array($decoded)) {
                        $items = $decoded;
                    }
                }

                if (empty($items)) {
                    // kalau gak ada snapshot, kita gak bisa buat mutasi & detail
                    // jangan set posted_at, biar bisa kamu investigasi
                    return;
                }

                // ===== buat mutasi + detail penjualan =====
                foreach ($items as $row) {
                    $produkId = (int) ($row['produk_id'] ?? 0);
                    $qty      = (int) ($row['qty'] ?? 0);

                    if ($produkId <= 0 || $qty <= 0) continue;

                    $produk = Produk::where('is_aktif', true)->find($produkId);
                    if (!$produk) continue;

                    // mutasi persediaan: KELUAR (stok berkurang)
                    $mutasi = MutasiPersediaan::create([
                        'produk_id'   => $produk->id,
                        'kode_produk' => $produk->kode_produk,
                        'nama_produk' => $produk->nama_produk,
                        'satuan'      => $produk->satuan ?? 'pcs',
                        'qty'         => $qty,
                        'tipe'        => 'KELUAR', // WAJIB match ENUM
                        'ref_tipe'    => 'PENJUALAN',
                        'ref_id'      => $p->id,
                        'harga'       => (float) ($produk->harga_beli ?? 0), // HPP per item
                        'tanggal'     => $p->tanggal ?? now()->toDateString(),
                        'keterangan'  => $p->kode_penjualan,
                    ]);

                    DetailPenjualan::create([
                        'penjualan_id'         => $p->id,
                        'mutasi_persediaan_id' => $mutasi->id,
                        'qty'                  => $qty,
                        'harga'                => (float) ($row['harga_jual'] ?? $produk->harga_jual ?? 0),
                        'subtotal'             => (float) ($row['harga_jual'] ?? $produk->harga_jual ?? 0) * $qty,
                    ]);
                }

                // reload relasi biar JournalPoster bisa hitung HPP
                $p->load(['details.mutasiPersediaan']);

                // payment_type bank transfer / VA -> biasanya masuk "bank"
                $poster->postPenjualan($p, 'bank');

                // tandai final
                $p->posted_at = now();
                $p->save();
            });
        }

        return response()->json(['message' => 'OK']);
    }
}
