<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class FinanceStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /**
     * Helper format rupiah
     */
    private function rupiah(float|int $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }

    /**
     * Ambil total berdasarkan prefix kode_akun + sisi (debit/kredit)
     */
    private function totalByPrefix(string $prefix, string $side, string $from, string $until): float
    {
        // side: 'debit' / 'kredit'
        $col = $side === 'kredit' ? 'jd.kredit' : 'jd.debit';

        return (float) DB::table('jurnal_detail as jd')
            ->join('jurnal as j', 'j.id', '=', 'jd.jurnal_id')
            ->join('akun as a', 'a.id', '=', 'jd.akun_id')
            ->whereBetween('j.tanggal', [$from, $until])
            ->where('a.kode_akun', 'like', $prefix . '%')
            ->sum(DB::raw($col));
    }

    protected function getStats(): array
    {
        $from  = Carbon::now()->startOfMonth()->toDateString();
        $until = Carbon::now()->endOfMonth()->toDateString();

        // Rules kamu:
        // 4xxx = Pendapatan (kredit)
        // 5xxx = HPP (debit)
        // 6xxx = Beban (debit)
        $pendapatan = $this->totalByPrefix('4', 'kredit', $from, $until);
        $hpp        = $this->totalByPrefix('5', 'debit',  $from, $until);
        $beban      = $this->totalByPrefix('6', 'debit',  $from, $until);

        $labaBersih = $pendapatan - $hpp - $beban;

        // subtext periode biar jelas
        $periodeLabel = Carbon::parse($from)->format('d M Y') . ' – ' . Carbon::parse($until)->format('d M Y');

        return [
            Stat::make('Pendapatan (Bulan ini)', $this->rupiah($pendapatan))
                ->description($periodeLabel)
                ->icon('heroicon-o-banknotes'),

            Stat::make('HPP (Bulan ini)', $this->rupiah($hpp))
                ->description($periodeLabel)
                ->icon('heroicon-o-archive-box'),

            Stat::make('Beban (Bulan ini)', $this->rupiah($beban))
                ->description($periodeLabel)
                ->icon('heroicon-o-receipt-refund'),

            Stat::make('Laba Bersih (Bulan ini)', $this->rupiah($labaBersih))
                ->description($periodeLabel)
                ->icon('heroicon-o-chart-bar'),
        ];
    }
}
