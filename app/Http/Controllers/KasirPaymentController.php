<?php
// app/Http/Controllers/KasirPaymentController.php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Services\MidtransService;
use App\Services\PenjualanFinalizer;
use Illuminate\Http\Request;

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

    public function finish(Request $request, MidtransService $midtrans, PenjualanFinalizer $finalizer)
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
            $finalizer->finalize($penjualan, 'bank');

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
    public function manualSettle(Request $request, Penjualan $penjualan, PenjualanFinalizer $finalizer)
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

        // manual settle juga anggap uang masuk via bank (karena "non-tunai")
        $finalizer->finalize($penjualan, 'bank', 'Manual settle (localhost webhook workaround)');

        // clear cart + idempotency
        $request->session()->forget('kasir_cart');
        $request->session()->forget('last_pending_penjualan_id');
        $request->session()->forget('last_pending_cart_hash');

        return redirect()->route('kasir.index')->with('ok', '✅ Manual settle sukses: stok terpotong, jurnal terposting, keranjang dikosongkan.');
    }
}
