<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produk;

class KasirController extends Controller
{
    public function index(Request $request)
    {
        // Mode kasir aktif (buat step reauth admin nanti)
        $request->session()->put('mode', 'kasir');

        $q = trim((string) $request->get('q', ''));

        $produks = Produk::with('kategori')
            ->where('is_aktif', true)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('nama_produk', 'like', '%' . $q . '%')
                       ->orWhere('kode_produk', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('nama_produk')
            ->get();

        $cart = $request->session()->get('kasir_cart', []);
        $summary = $this->cartSummary($cart);

        return view('kasir.index', [
            'produks' => $produks,
            'cart'    => $cart,
            'summary' => $summary,
            'q'       => $q,
        ]);
    }

    private function cartSummary(array $cart): array
    {
        $items = 0;
        $total = 0;

        foreach ($cart as $row) {
            $items += (int) ($row['qty'] ?? 0);
            $total += ((float) ($row['harga_jual'] ?? 0)) * (int) ($row['qty'] ?? 0);
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}
