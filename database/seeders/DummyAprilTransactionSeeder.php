<?php

namespace Database\Seeders;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\KategoriProduk;
use App\Models\MutasiPersediaan;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\Supplier;
use App\Models\User;
use App\Services\JournalPoster;
use App\Services\PenjualanFinalizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DummyAprilTransactionSeeder extends Seeder
{
    private const MARKER = 'DUMMY_APRIL_2026';

    public function run(): void
    {
        if (Penjualan::query()->where('keterangan', 'like', '%' . self::MARKER . '%')->exists()) {
            $this->command?->info('Dummy April transactions already exist. Skipping.');
            return;
        }

        DB::transaction(function () {
            $user = User::query()->first() ?? User::query()->create([
                'name' => 'Admin Demo',
                'email' => 'admin-demo@example.com',
                'password' => bcrypt('password'),
            ]);

            $category = KategoriProduk::query()->firstOrCreate([
                'nama_kategori' => 'Produk Kucing',
            ], [
                'kode_kategori' => 'KAT-DUMMY-CAT',
                'deskripsi' => 'Data dummy produk kucing',
                'is_aktif' => true,
            ]);

            $supplier = Supplier::query()->firstOrCreate([
                'nama_supplier' => 'PT Paw Supply Nusantara',
            ], [
                'kode_supplier' => 'SUP-DUMMY-PAW',
                'telepon' => '021-555-0142',
                'email' => 'supply@example.test',
                'alamat' => 'Jakarta',
                'is_aktif' => true,
                'keterangan' => 'Supplier dummy untuk transaksi April 2026',
            ]);

            $products = [
                'Makanan Kucing Tuna 1kg' => ['satuan' => 'pack', 'harga_beli' => 42000, 'harga_jual' => 62000],
                'Pasir Kucing Wangi 5L' => ['satuan' => 'bag', 'harga_beli' => 28000, 'harga_jual' => 45000],
                'Shampoo Kucing Anti Kutu' => ['satuan' => 'botol', 'harga_beli' => 18000, 'harga_jual' => 32000],
                'Mainan Bola Kucing' => ['satuan' => 'pcs', 'harga_beli' => 9000, 'harga_jual' => 18000],
            ];

            $produkByName = [];
            foreach ($products as $name => $data) {
                $produkByName[$name] = Produk::query()->firstOrCreate([
                    'nama_produk' => $name,
                ], [
                    'kode_produk' => 'PRD-DUMMY-' . substr(md5($name), 0, 6),
                    'kategori_produk_id' => $category->id,
                    'satuan' => $data['satuan'],
                    'harga_beli' => $data['harga_beli'],
                    'harga_jual' => $data['harga_jual'],
                    'is_aktif' => true,
                    'keterangan' => 'Produk dummy untuk transaksi April 2026',
                ]);
            }

            $this->createPurchase($supplier, $user, Carbon::create(2026, 4, 3), [
                ['produk' => $produkByName['Makanan Kucing Tuna 1kg'], 'qty' => 40, 'harga' => 42000],
                ['produk' => $produkByName['Pasir Kucing Wangi 5L'], 'qty' => 35, 'harga' => 28000],
                ['produk' => $produkByName['Shampoo Kucing Anti Kutu'], 'qty' => 20, 'harga' => 18000],
            ]);

            $this->createPurchase($supplier, $user, Carbon::create(2026, 4, 12), [
                ['produk' => $produkByName['Makanan Kucing Tuna 1kg'], 'qty' => 30, 'harga' => 44000],
                ['produk' => $produkByName['Mainan Bola Kucing'], 'qty' => 50, 'harga' => 9000],
            ]);

            $this->createSale($user, Carbon::create(2026, 4, 7), [
                ['produk' => $produkByName['Makanan Kucing Tuna 1kg'], 'qty' => 8],
                ['produk' => $produkByName['Pasir Kucing Wangi 5L'], 'qty' => 5],
                ['produk' => $produkByName['Shampoo Kucing Anti Kutu'], 'qty' => 3],
            ]);

            $this->createSale($user, Carbon::create(2026, 4, 16), [
                ['produk' => $produkByName['Makanan Kucing Tuna 1kg'], 'qty' => 11],
                ['produk' => $produkByName['Mainan Bola Kucing'], 'qty' => 9],
            ]);

            $this->createSale($user, Carbon::create(2026, 4, 24), [
                ['produk' => $produkByName['Pasir Kucing Wangi 5L'], 'qty' => 7],
                ['produk' => $produkByName['Shampoo Kucing Anti Kutu'], 'qty' => 4],
                ['produk' => $produkByName['Mainan Bola Kucing'], 'qty' => 6],
            ]);

            $this->createExpense($user, Carbon::create(2026, 4, 10), 'DUMMY-APRIL-BEBAN-001', 350000);
            $this->createExpense($user, Carbon::create(2026, 4, 22), 'DUMMY-APRIL-BEBAN-002', 225000);
        });
    }

    private function createPurchase(Supplier $supplier, User $user, Carbon $date, array $items): void
    {
        $total = collect($items)->sum(fn ($item) => (float) $item['qty'] * (float) $item['harga']);

        $pembelian = Pembelian::query()->create([
            'tanggal' => $date->toDateString(),
            'supplier_id' => $supplier->id,
            'no_invoice' => 'INV-' . self::MARKER . '-' . $date->format('md'),
            'total' => $total,
            'keterangan' => self::MARKER . ' - pembelian stok awal',
            'user_id' => $user->id,
        ]);

        foreach ($items as $item) {
            /** @var Produk $produk */
            $produk = $item['produk'];
            $qty = (int) $item['qty'];
            $harga = (float) $item['harga'];

            $detail = PembelianDetail::query()->create([
                'pembelian_id' => $pembelian->id,
                'produk_id' => $produk->id,
                'nama_item' => $produk->nama_produk,
                'qty' => $qty,
                'harga' => $harga,
                'mutasi_persediaan_id' => null,
            ]);

            $mutasi = MutasiPersediaan::query()->create([
                'produk_id' => $produk->id,
                'kode_produk' => $produk->kode_produk,
                'nama_produk' => $produk->nama_produk,
                'satuan' => $produk->satuan ?? 'pcs',
                'qty' => $qty,
                'tipe' => MutasiPersediaan::TIPE_MASUK,
                'ref_tipe' => 'PEMBELIAN',
                'ref_id' => $pembelian->id,
                'harga' => $harga,
                'tanggal' => $date->toDateString(),
                'keterangan' => self::MARKER . ' - ' . $pembelian->kode_pembelian,
            ]);

            $detail->update(['mutasi_persediaan_id' => $mutasi->id]);
        }

        app(JournalPoster::class)->postPembelian($pembelian, 'bank', self::MARKER);
    }

    private function createSale(User $user, Carbon $date, array $items): void
    {
        $cart = [];
        $total = 0.0;

        foreach ($items as $item) {
            /** @var Produk $produk */
            $produk = $item['produk'];
            $qty = (int) $item['qty'];
            $hargaJual = (float) $produk->harga_jual;
            $total += $qty * $hargaJual;

            $cart[] = [
                'produk_id' => $produk->id,
                'kode_produk' => $produk->kode_produk,
                'nama_produk' => $produk->nama_produk,
                'harga_jual' => $hargaJual,
                'satuan' => $produk->satuan ?? 'pcs',
                'qty' => $qty,
            ];
        }

        $penjualan = Penjualan::query()->create([
            'tanggal' => $date->toDateString(),
            'total' => $total,
            'gross_amount' => $total,
            'keterangan' => self::MARKER . ' - penjualan kasir dummy',
            'user_id' => $user->id,
            'payment_status' => 'PAID',
            'paid_at' => $date->copy()->setTime(14, 30),
            'posted_at' => null,
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'TRX-' . self::MARKER . '-' . $date->format('mdHis'),
            'cart_snapshot' => json_encode($cart),
        ]);

        app(PenjualanFinalizer::class)->finalize($penjualan, 'bank', self::MARKER);
    }

    private function createExpense(User $user, Carbon $date, string $ref, float $amount): void
    {
        if (Jurnal::query()->where('sumber_transaksi', 'BEBAN')->where('referensi_transaksi', $ref)->exists()) {
            return;
        }

        $bebanId = (int) Akun::query()->where('kode_akun', '6001')->value('id');
        $kasId = (int) Akun::query()->where('kode_akun', '1001')->value('id');

        $jurnal = Jurnal::query()->create([
            'tanggal' => $date->toDateString(),
            'sumber_transaksi' => 'BEBAN',
            'referensi_transaksi' => $ref,
            'user_id' => $user->id,
        ]);

        JurnalDetail::query()->create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $bebanId,
            'debit' => $amount,
            'kredit' => 0,
        ]);

        JurnalDetail::query()->create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $kasId,
            'debit' => 0,
            'kredit' => $amount,
        ]);
    }
}
