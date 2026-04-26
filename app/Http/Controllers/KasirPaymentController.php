<?php
// app/Http/Controllers/KasirPaymentController.php

namespace App\Http\Controllers;

use App\Models\DetailPenjualan;
use App\Models\MutasiPersediaan;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Services\JournalPoster;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KasirPaymentController extends Controller
{
    public function pay(Penjualan $penjualan, MidtransService $midtrans)
    {
        // ✅ kalau sudah PAID / posted, jangan pernah buka snap lagi
        if ($penjualan->payment_status === 'PAID' || !empty($penjualan->posted_at)) {
            return redirect()->route('kasir.finish', [
                'order_id' => $penjualan->kode_penjualan,
                'result'   => 'already_paid',
            ]);
        }

        if (empty($penjualan->snap_token)) {
            return redirect()->route('kasir.index')->with('err', 'Snap token belum tersedia.');
        }

        // ✅ Kalau local masih PENDING, cek dulu ke Midtrans (source of truth).
        $status = $midtrans->getStatus($penjualan->kode_penjualan);

        // midtrans-php biasanya throw exception; kalau service kamu ngebalikin __error, handle di sini
        if (!empty($status['__error'])) {
            return view('kasir.pay', [
                'penjualan'     => $penjualan,
                'clientKey'     => config('midtrans.client_key'),
                'midtransError' => $status['message'] ?? 'Midtrans status error',
            ]);
        }

        $newStatus = $midtrans->mapStatus(
            (string) ($status['transaction_status'] ?? ''),
            (string) ($status['fraud_status'] ?? null)
        );

        // Kalau ternyata udah PAID di Midtrans, sync lalu lempar ke finish biar finalize
        if ($newStatus === 'PAID') {
            $penjualan->payment_status = 'PAID';
            $penjualan->transaction_id = $status['transaction_id'] ?? $penjualan->transaction_id;
            $penjualan->payment_type   = $status['payment_type'] ?? $penjualan->payment_type;
            $penjualan->gross_amount   = $status['gross_amount'] ?? $penjualan->gross_amount;

            if (empty($penjualan->paid_at)) {
                $penjualan->paid_at = now();
            }

            $penjualan->save();

            return redirect()->route('kasir.finish', [
                'order_id' => $penjualan->kode_penjualan,
                'result'   => 'synced_paid',
            ]);
        }

        return view('kasir.pay', [
            'penjualan'     => $penjualan,
            'clientKey'     => config('midtrans.client_key'),
            'midtransError' => null,
        ]);
    }

    public function finish(Request $request, MidtransService $midtrans, JournalPoster $poster)
    {
        $orderId = (string) $request->query('order_id', '');
        $result  = (string) $request->query('result', '');

        $penjualan = $orderId !== ''
            ? Penjualan::query()->where('kode_penjualan', $orderId)->first()
            : null;

        if (!$penjualan) {
            return view('kasir.finish', [
                'orderId'      => $orderId,
                'result'       => $result,
                'penjualan'    => null,
                'midtransRaw'  => null,
                'midtransError'=> null,
            ]);
        }

        // 1) Source of truth: status real dari Midtrans
        $status = $midtrans->getStatus($penjualan->kode_penjualan);

        if (!empty($status['__error'])) {
            return view('kasir.finish', [
                'orderId'      => $orderId,
                'result'       => $result,
                'penjualan'    => $penjualan,
                'midtransRaw'  => null,
                'midtransError'=> $status['message'] ?? 'Midtrans status error',
            ]);
        }

        $newStatus = $midtrans->mapStatus(
            (string) ($status['transaction_status'] ?? ''),
            (string) ($status['fraud_status'] ?? null)
        );

        $penjualan->payment_status = $newStatus;
        $penjualan->transaction_id = $status['transaction_id'] ?? $penjualan->transaction_id;
        $penjualan->payment_type   = $status['payment_type'] ?? $penjualan->payment_type;
        $penjualan->gross_amount   = $status['gross_amount'] ?? $penjualan->gross_amount;

        if ($newStatus === 'PAID' && empty($penjualan->paid_at)) {
            $penjualan->paid_at = now();
        }

        $penjualan->save();

        // 2) FINALIZE (idempotent) -> detail + mutasi + jurnal (cuma sekali)
        if ($penjualan->payment_status === 'PAID' && empty($penjualan->posted_at)) {

            DB::transaction(function () use ($penjualan, $poster) {
                $snapshot = $penjualan->cart_snapshot ?? null;
                $items = [];

                if (is_string($snapshot) && $snapshot !== '') {
                    $items = json_decode($snapshot, true) ?: [];
                }

                // Guard anti transaksi hantu
                if (empty($items)) {
                    return;
                }

                // Anti double detail/mutasi: kalau sudah ada detail, skip create ulang
                $already = DetailPenjualan::query()
                    ->where('penjualan_id', $penjualan->id)
                    ->exists();

                if (!$already) {
                    foreach ($items as $row) {
                        $produkId = (int) ($row['produk_id'] ?? 0);
                        $qty      = (int) ($row['qty'] ?? 0);
                        if ($produkId <= 0 || $qty <= 0) continue;

                        $produk = Produk::where('is_aktif', true)->findOrFail($produkId);

                        $mutasi = MutasiPersediaan::create([
                            'produk_id'   => $produk->id,
                            'kode_produk' => $produk->kode_produk,
                            'nama_produk' => $produk->nama_produk,
                            'satuan'      => $produk->satuan ?? 'pcs',
                            'qty'         => $qty,
                            'tipe'        => MutasiPersediaan::TIPE_KELUAR,
                            'ref_tipe'    => 'PENJUALAN',
                            'ref_id'      => $penjualan->id,
                            // HPP: sementara pakai harga_beli
                            'harga'       => (float) ($produk->harga_beli ?? 0),
                            'tanggal'     => $penjualan->tanggal,
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

                $penjualan->load(['details.mutasiPersediaan']);

                // Midtrans = uang masuk via "bank"
                $poster->postPenjualan($penjualan, 'bank');

                $penjualan->posted_at = now();
                $penjualan->save();
            });

            // 3) Clear cart session user (cuma bisa di finish/manual)
            $request->session()->forget('kasir_cart');
            $request->session()->forget('last_pending_penjualan_id');
            $request->session()->forget('last_pending_cart_hash');
        }

        return view('kasir.finish', [
            'orderId'      => $orderId,
            'result'       => $result,
            'penjualan'    => $penjualan->fresh(),
            'midtransRaw'  => $status,
            'midtransError'=> null,
        ]);
    }

    /**
     * ===== MANUAL SETTLE (temporary workaround) =====
     * Untuk kasus: Midtrans webhook gak bisa tembus localhost.
     * Tombol ini "memaksa" transaksi jadi PAID + menjalankan finalize (mutasi + detail + jurnal) lalu clear cart.
     */
    public function manualSettle(Request $request, Penjualan $penjualan, JournalPoster $poster)
    {
        // ✅ idempotent: kalau udah posted, langsung balik ke kasir
        if (!empty($penjualan->posted_at)) {
            $request->session()->forget('kasir_cart');
            $request->session()->forget('last_pending_penjualan_id');
            $request->session()->forget('last_pending_cart_hash');

            return redirect()->route('kasir.index')->with('ok', 'Transaksi sudah pernah diposting. Keranjang dibersihkan.');
        }

        // force mark as paid
        $penjualan->payment_status = 'PAID';
        if (empty($penjualan->paid_at)) {
            $penjualan->paid_at = now();
        }
        // catatan: ini manual, jadi transaction_id/payment_type mungkin null—gapapa sementara
        $penjualan->save();

        // finalize: detail + mutasi + jurnal
        DB::transaction(function () use ($penjualan, $poster) {
            $snapshot = $penjualan->cart_snapshot ?? null;
            $items = [];

            if (is_string($snapshot) && $snapshot !== '') {
                $items = json_decode($snapshot, true) ?: [];
            }

            if (empty($items)) {
                return;
            }

            $already = DetailPenjualan::query()
                ->where('penjualan_id', $penjualan->id)
                ->exists();

            if (!$already) {
                foreach ($items as $row) {
                    $produkId = (int) ($row['produk_id'] ?? 0);
                    $qty      = (int) ($row['qty'] ?? 0);
                    if ($produkId <= 0 || $qty <= 0) continue;

                    $produk = Produk::where('is_aktif', true)->findOrFail($produkId);

                    $mutasi = MutasiPersediaan::create([
                        'produk_id'   => $produk->id,
                        'kode_produk' => $produk->kode_produk,
                        'nama_produk' => $produk->nama_produk,
                        'satuan'      => $produk->satuan ?? 'pcs',
                        'qty'         => $qty,
                        'tipe'        => MutasiPersediaan::TIPE_KELUAR,
                        'ref_tipe'    => 'PENJUALAN',
                        'ref_id'      => $penjualan->id,
                        'harga'       => (float) ($produk->harga_beli ?? 0),
                        'tanggal'     => $penjualan->tanggal,
                        'keterangan'  => $penjualan->kode_penjualan . ' (MANUAL SETTLE)',
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

            $penjualan->load(['details.mutasiPersediaan']);

            // manual settle juga anggap uang masuk via bank (karena "non-tunai")
            $poster->postPenjualan($penjualan, 'bank', 'Manual settle (localhost webhook workaround)');

            $penjualan->posted_at = now();
            $penjualan->save();
        });

        // clear cart + idempotency
        $request->session()->forget('kasir_cart');
        $request->session()->forget('last_pending_penjualan_id');
        $request->session()->forget('last_pending_cart_hash');

        return redirect()->route('kasir.index')->with('ok', '✅ Manual settle sukses: stok terpotong, jurnal terposting, keranjang dikosongkan.');
    }
}
