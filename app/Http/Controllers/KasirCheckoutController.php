<?php

namespace App\Http\Controllers;

use App\Models\MutasiPersediaan;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KasirCheckoutController extends Controller
{
    public function store(Request $request, MidtransService $midtrans)
    {
        $cart = $request->session()->get('kasir_cart', []);
        if (empty($cart)) {
            return redirect()->route('kasir.index')->with('err', 'Keranjang masih kosong.');
        }

        $tanggal = now()->toDateString();

        // total dari cart session (harga jual * qty)
        $total = 0.0;
        foreach ($cart as $row) {
            $total += ((float) ($row['harga_jual'] ?? 0)) * (int) ($row['qty'] ?? 0);
        }

        if ($total <= 0) {
            return redirect()->route('kasir.index')->with('err', 'Total transaksi tidak valid.');
        }

        // ===== cek stok (sebelum create order) =====
        $MIN_STOK = 5;
        $lowStockAlerts = [];
        $stockErrors = [];

        foreach ($cart as $row) {
            $produkId = (int) ($row['produk_id'] ?? 0);
            $qty      = (int) ($row['qty'] ?? 0);

            if ($produkId <= 0 || $qty <= 0) continue;

            $produk = Produk::where('is_aktif', true)->find($produkId);
            if (!$produk) continue;

            $stokSaatIni = MutasiPersediaan::stokSaatIni($produkId);

            if ($stokSaatIni < $qty) {
                $stockErrors[] = "{$produk->nama_produk} (stok $stokSaatIni, butuh $qty)";
                continue;
            }

            $stokAkhir = $stokSaatIni - $qty;

            if ($stokAkhir < $MIN_STOK) {
                $lowStockAlerts[] = "{$produk->nama_produk} tinggal $stokAkhir";
            }
        }

        if (!empty($stockErrors)) {
            return redirect()
                ->route('kasir.index')
                ->with('err', 'Stok tidak cukup: ' . implode(', ', $stockErrors));
        }

        // ===== idempotency ringan: kalau cart sama persis dan baru dibuat, reuse order pending =====
        $cartHash = hash('sha1', json_encode($cart) . '|' . number_format($total, 2, '.', ''));

        $lastId = $request->session()->get('last_pending_penjualan_id');
        $lastHash = $request->session()->get('last_pending_cart_hash');

        if ($lastId && $lastHash === $cartHash) {
            $existing = Penjualan::find($lastId);
            if ($existing && $existing->payment_status === 'PENDING' && !empty($existing->snap_token)) {
                // tetap kasih warn stok menipis kalau ada
                if (!empty($lowStockAlerts)) {
                    return redirect()
                        ->route('kasir.pay', $existing->id)
                        ->with('warn', 'Stok menipis: ' . implode(', ', $lowStockAlerts));
                }

                return redirect()->route('kasir.pay', $existing->id);
            }
        }

        // ===== create penjualan PENDING + snap token =====
        $penjualan = DB::transaction(function () use ($tanggal, $total, $cart, $cartHash, $midtrans) {

            /** @var Penjualan $p */
            $p = Penjualan::create([
                'tanggal'        => $tanggal,
                'total'          => $total,
                'gross_amount'   => $total,
                'keterangan'     => 'Penjualan Kasir (Midtrans)',
                'user_id'        => Auth::id() ?? 1,

                'payment_status' => 'PENDING',
                'paid_at'        => null,
                'posted_at'      => null,
                'cart_snapshot'  => json_encode(array_values($cart)),
            ]);

            // payload snap
            $payload = [
                'transaction_details' => [
                    'order_id'     => $p->kode_penjualan,
                    'gross_amount' => (int) round($total), // midtrans suka integer
                ],
                'customer_details' => [
                    'first_name' => 'Kasir',
                    'email'      => (Auth::user()?->email) ?? 'kasir@example.com',
                ],
                'item_details' => collect($cart)->map(function ($row) {
                    return [
                        'id'       => (string) ($row['produk_id'] ?? ''),
                        'price'    => (int) round((float) ($row['harga_jual'] ?? 0)),
                        'quantity' => (int) ($row['qty'] ?? 1),
                        'name'     => (string) ($row['nama_produk'] ?? 'Item'),
                    ];
                })->values()->toArray(),
            ];
           
            $snapToken = $midtrans->getSnapToken($payload);

            $p->snap_token = $snapToken;
            $p->snap_token_created_at = now();
            $p->save();

            return $p;
        });

        // simpan idempotency info ke session
        $request->session()->put('last_pending_penjualan_id', $penjualan->id);
        $request->session()->put('last_pending_cart_hash', $cartHash);

        // optional: jangan clear cart dulu (biar kalau gagal bisa retry),
        // tapi UI kamu bisa kamu putusin mau clear atau enggak.
        // Kalau mau clear supaya anti double order dari user:
        // $request->session()->forget('kasir_cart');

        if (!empty($lowStockAlerts)) {
            return redirect()
                ->route('kasir.pay', $penjualan->id)
                ->with('warn', 'Stok menipis: ' . implode(', ', $lowStockAlerts));
        }

        return redirect()->route('kasir.pay', $penjualan->id);
    }
}
