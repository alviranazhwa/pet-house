<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produk;

class KasirCartController extends Controller
{
    public function add(Request $request)
    {
        $data = $request->validate([
            'produk_id' => ['required', 'integer'],
            'qty'       => ['nullable', 'integer', 'min:1'],
        ]);

        $qty = (int) ($data['qty'] ?? 1);

        $produk = Produk::where('is_aktif', true)->findOrFail($data['produk_id']);

        $cart = $request->session()->get('kasir_cart', []);

        if (!isset($cart[$produk->id])) {
            $cart[$produk->id] = [
                'produk_id'   => $produk->id,
                'kode_produk' => $produk->kode_produk,
                'nama_produk' => $produk->nama_produk,
                'harga_jual'  => (float) $produk->harga_jual,
                'satuan'      => $produk->satuan ?? 'pcs',
                'qty'         => 0,
            ];
        }

        $cart[$produk->id]['qty'] += $qty;

        $request->session()->put('kasir_cart', $cart);

        return redirect()->route('kasir.index')->with('ok', 'Produk ditambahkan ke keranjang.');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'produk_id' => ['required', 'integer'],
            'qty'       => ['required', 'integer', 'min:1'],
        ]);

        $cart = $request->session()->get('kasir_cart', []);

        if (isset($cart[$data['produk_id']])) {
            $cart[$data['produk_id']]['qty'] = (int) $data['qty'];
            $request->session()->put('kasir_cart', $cart);
        }

        return redirect()->route('kasir.index');
    }

    public function remove(Request $request)
    {
        $data = $request->validate([
            'produk_id' => ['required', 'integer'],
        ]);

        $cart = $request->session()->get('kasir_cart', []);

        unset($cart[$data['produk_id']]);

        $request->session()->put('kasir_cart', $cart);

        return redirect()->route('kasir.index');
    }

    public function clear(Request $request)
    {
        $request->session()->forget('kasir_cart');

        return redirect()->route('kasir.index');
    }
}
