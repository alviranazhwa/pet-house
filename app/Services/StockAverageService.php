<?php

namespace App\Services;

use App\Models\MutasiPersediaan;
use App\Models\Produk;
use Illuminate\Support\Carbon;

class StockAverageService
{
    public function averageCostBefore(int $produkId, ?string $until = null): float
    {
        $state = $this->stateBefore($produkId, $until);

        return $state['qty'] > 0 ? (float) $state['avg'] : 0.0;
    }

    public function stateBefore(int $produkId, ?string $until = null): array
    {
        $untilDate = $until ? Carbon::parse($until)->endOfDay() : null;

        $query = MutasiPersediaan::where('produk_id', $produkId)
            ->when($untilDate, fn ($q) => $q->whereDate('tanggal', '<=', $untilDate->toDateString()))
            ->orderBy('tanggal')
            ->orderBy('id');

        $saldoQty = 0.0;
        $saldoNilai = 0.0;
        $avg = 0.0;

        foreach ($query->get() as $mutasi) {
            $qty = (float) $mutasi->qty;
            $harga = (float) ($mutasi->harga ?? 0);

            if ($mutasi->tipe === MutasiPersediaan::TIPE_MASUK) {
                $saldoNilai += $qty * $harga;
                $saldoQty += $qty;
                $avg = $saldoQty > 0 ? $saldoNilai / $saldoQty : 0.0;
                continue;
            }

            if ($mutasi->tipe === MutasiPersediaan::TIPE_KELUAR) {
                $cost = $harga > 0 ? $harga : $avg;
                $saldoQty -= $qty;
                $saldoNilai -= $qty * $cost;
                $avg = $saldoQty > 0 ? $saldoNilai / $saldoQty : 0.0;
                continue;
            }

            if ($mutasi->tipe === MutasiPersediaan::TIPE_PENYESUAIAN) {
                if ($qty >= 0) {
                    $saldoNilai += $qty * ($harga > 0 ? $harga : $avg);
                    $saldoQty += $qty;
                } else {
                    $cost = $harga > 0 ? $harga : $avg;
                    $saldoQty += $qty;
                    $saldoNilai += $qty * $cost;
                }

                $avg = $saldoQty > 0 ? $saldoNilai / $saldoQty : 0.0;
            }
        }

        return [
            'qty' => $saldoQty,
            'nilai' => $saldoNilai,
            'avg' => $avg,
        ];
    }

    public function build(int $produkId, ?string $from = null, ?string $until = null): array
    {
        $produk = Produk::findOrFail($produkId);

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $untilDate = $until ? Carbon::parse($until)->endOfDay() : null;

        // =========================
        // SALDO AWAL
        // =========================
        $opening = $fromDate
            ? $this->stateBefore($produkId, $fromDate->copy()->subDay()->toDateString())
            : ['qty' => 0.0, 'nilai' => 0.0, 'avg' => 0.0];

        $saldoQty = (float) $opening['qty'];
        $saldoNilai = (float) $opening['nilai'];
        $avg = (float) $opening['avg'];

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
                $saldoNilai -= $m->qty * ((float) ($m->harga ?? 0) > 0 ? (float) $m->harga : $avg);
            }

            if ($m->tipe === 'PENYESUAIAN') {
                if ((float) $m->qty >= 0) {
                    $masuk = $m->qty;
                    $saldoNilai += $m->qty * ((float) ($m->harga ?? 0) > 0 ? (float) $m->harga : $avg);
                    $saldoQty += $m->qty;
                    $avg = $saldoQty > 0 ? $saldoNilai / $saldoQty : 0;
                } else {
                    $keluar = abs((float) $m->qty);
                    $saldoQty += $m->qty;
                    $saldoNilai += $m->qty * ((float) ($m->harga ?? 0) > 0 ? (float) $m->harga : $avg);
                }
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
