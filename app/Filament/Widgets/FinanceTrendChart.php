<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class FinanceTrendChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Pendapatan vs HPP vs Beban (Per Bulan)';
    protected ?string $maxHeight = '320px';

    /**
     * Berapa bulan terakhir yang ditampilkan (termasuk bulan ini)
     * Silakan ubah: 6 / 12
     */
    protected int $months = 6;

    protected function getData(): array
    {
        $end   = Carbon::now()->endOfMonth();
        $start = Carbon::now()->startOfMonth()->subMonths($this->months - 1);

        // Ambil agregat per bulan:
        // 4xxx = Pendapatan (kredit)
        // 5xxx = HPP (debit)
        // 6xxx = Beban (debit)
        $rows = DB::table('jurnal_detail as jd')
            ->join('jurnal as j', 'j.id', '=', 'jd.jurnal_id')
            ->join('akun as a', 'a.id', '=', 'jd.akun_id')
            ->selectRaw("DATE_FORMAT(j.tanggal, '%Y-%m') as ym")
            ->selectRaw("SUM(CASE WHEN a.kode_akun LIKE '4%' THEN jd.kredit ELSE 0 END) as pendapatan")
            ->selectRaw("SUM(CASE WHEN a.kode_akun LIKE '5%' THEN jd.debit  ELSE 0 END) as hpp")
            ->selectRaw("SUM(CASE WHEN a.kode_akun LIKE '6%' THEN jd.debit  ELSE 0 END) as beban")
            ->whereBetween('j.tanggal', [$start->toDateString(), $end->toDateString()])
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $labels = [];
        $pendapatan = [];
        $hpp = [];
        $beban = [];

        // Pastikan bulan yang kosong tetap muncul sebagai 0
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $ym = $cursor->format('Y-m');
            $labels[] = $cursor->translatedFormat('M Y');

            $pendapatan[] = (float) ($rows[$ym]->pendapatan ?? 0);
            $hpp[]        = (float) ($rows[$ym]->hpp ?? 0);
            $beban[]      = (float) ($rows[$ym]->beban ?? 0);

            $cursor->addMonth();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Pendapatan',
                    'data'  => $pendapatan,
                ],
                [
                    'label' => 'HPP',
                    'data'  => $hpp,
                ],
                [
                    'label' => 'Beban',
                    'data'  => $beban,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
