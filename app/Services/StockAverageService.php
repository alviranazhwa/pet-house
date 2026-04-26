<?php

namespace App\Services;

use App\Models\MutasiPersediaan;
use App\Models\Produk;
use Illuminate\Support\Carbon;

class StockAverageService
{
    public function build(int $produkId, ?string $from = null, ?string $until = null): array
    {
        $produk = Produk::findOrFail($produkId);

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $untilDate = $until ? Carbon::parse($until)->endOfDay() : null;

        // =========================
        // SALDO AWAL
        // =========================
        $saldoAwalMasuk = MutasiPersediaan::where('produk_id', $produkId)
            ->where('tipe', 'MASUK')
            ->when($fromDate, fn ($q) => $q->whereDate('tanggal', '<', $fromDate))
            ->sum('qty');

        $saldoAwalKeluar = MutasiPersediaan::where('produk_id', $produkId)
            ->where('tipe', 'KELUAR')
            ->when($fromDate, fn ($q) => $q->whereDate('tanggal', '<', $fromDate))
            ->sum('qty');

        $saldoQty = $saldoAwalMasuk - $saldoAwalKeluar;
        $saldoNilai = $saldoQty * (float) ($produk->harga_beli ?? 0);
        $avg = $saldoQty > 0 ? $saldoNilai / $saldoQty : 0;

        // =========================
        // MUTASI DALAM PERIODE
        // =========================
        $query = MutasiPersediaan::where('produk_id', $produkId)
            ->when($fromDate, fn ($q) => $q->whereDate('tanggal', '>=', $fromDate))
            ->when($untilDate, fn ($q) => $q->whereDate('tanggal', '<=', $untilDate))
            ->orderBy('tanggal')
            ->orderBy('id');

        $mutasi = $query->get();

        $rows = [];

        // baris saldo awal
        if ($saldoQty > 0) {
            $rows[] = [
                'group'   => Carbon::parse($from)->format('F Y'),
                'tanggal' => Carbon::parse($from)->format('d/m/Y'),
                'ref'     => 'SALDO AWAL',
                'masuk'   => null,
                'keluar'  => null,
                'saldo'   => $saldoQty,
                'avg'     => round($avg),
                'nilai'   => round($saldoNilai),
            ];
        }

        foreach ($mutasi as $m) {
            $masuk = null;
            $keluar = null;

            if ($m->tipe === 'MASUK') {
                $masuk = $m->qty;

                $saldoNilai += $m->qty * (float) $m->harga;
                $saldoQty += $m->qty;

                if ($saldoQty > 0) {
                    $avg = $saldoNilai / $saldoQty;
                }
            }

            if ($m->tipe === 'KELUAR') {
                $keluar = $m->qty;

                $saldoQty -= $m->qty;
                $saldoNilai -= $m->qty * $avg;
            }

            $rows[] = [
                'group'   => Carbon::parse($m->tanggal)->format('F Y'),
                'tanggal' => Carbon::parse($m->tanggal)->format('d/m/Y'),
                'ref'     => $m->ref_tipe . ' #' . $m->ref_id,
                'masuk'   => $masuk,
                'keluar'  => $keluar,
                'saldo'   => max($saldoQty, 0),
                'avg'     => round($avg),
                'nilai'   => round($saldoNilai),
            ];
        }

        return [
            'produk' => $produk,
            'rows' => $rows,
            'summary' => [
                'qty'   => $saldoQty,
                'avg'   => round($avg),
                'nilai' => round($saldoNilai),
            ],
        ];
    }
}
