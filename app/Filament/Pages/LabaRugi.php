<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LabaRugi extends Page
{
    // ✅ Navigation (Filament v4 aman: nullable string)
    protected static ?string $navigationLabel = 'Laba Rugi';
    protected static ?string $title = 'Laba Rugi';
    protected static ?string $slug = 'laba-rugi';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null $navigationGroup = 'Laporan Keuangan';
    protected static ?int $navigationSort = 2;

    // ✅ Filament v4: JANGAN static $view. Pakai getView()
    public function getView(): string
    {
        return 'filament.pages.laba-rugi';
    }

    // =========================
    // FILTER (tanpa Filament Form)
    // =========================
    public string $from = '';
    public string $until = '';

    // =========================
    // OUTPUT
    // =========================
    public array $summary = [
        'pendapatan'        => 0,
        'hpp'               => 0,
        'laba_kotor'        => 0,
        'beban_operasional' => 0,
        'laba_bersih'       => 0,
    ];

    public string $periodeLabel = '';

    public function mount(): void
    {
        $this->from  = Carbon::now()->startOfMonth()->toDateString();
        $this->until = Carbon::now()->endOfMonth()->toDateString();

        $this->buildSummary();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('apply')
                ->label('Refresh')
                ->color('success')
                ->icon('heroicon-o-arrow-path')
                ->action('apply'),
        ];
    }

    public function apply(): void
    {
        if (empty($this->from) || empty($this->until)) {
            Notification::make()
                ->title('Tanggal wajib diisi')
                ->danger()
                ->send();
            return;
        }

        if (Carbon::parse($this->from)->gt(Carbon::parse($this->until))) {
            Notification::make()
                ->title('Range tanggal tidak valid')
                ->body('Tanggal "Dari" tidak boleh lebih besar dari "Sampai".')
                ->danger()
                ->send();
            return;
        }

        $this->buildSummary();

        Notification::make()
            ->title('Ringkasan diperbarui')
            ->success()
            ->send();
    }

    private function buildSummary(): void
    {
        $from  = Carbon::parse($this->from)->startOfDay()->toDateString();
        $until = Carbon::parse($this->until)->endOfDay()->toDateString();

        $this->periodeLabel = Carbon::parse($from)->format('d/m/Y') . ' – ' . Carbon::parse($until)->format('d/m/Y');

        $base = DB::table('jurnal_detail as jd')
            ->join('jurnal as j', 'j.id', '=', 'jd.jurnal_id')
            ->join('akun as a', 'a.id', '=', 'jd.akun_id')
            ->whereDate('j.tanggal', '>=', $from)
            ->whereDate('j.tanggal', '<=', $until);

        // Pendapatan (kategori pendapatan) => kredit - debit
        $pendapatan = (clone $base)
            ->where('a.kategori', 'pendapatan')
            ->selectRaw('COALESCE(SUM(jd.kredit),0) - COALESCE(SUM(jd.debit),0) as net')
            ->value('net') ?? 0;

        // HPP = akun beban kode 5xxx => debit - kredit
        $hpp = (clone $base)
            ->where('a.kategori', 'beban')
            ->where('a.kode_akun', 'like', '5%')
            ->selectRaw('COALESCE(SUM(jd.debit),0) - COALESCE(SUM(jd.kredit),0) as net')
            ->value('net') ?? 0;

        // Beban Operasional = akun beban kode 6xxx => debit - kredit
        $bebanOperasional = (clone $base)
            ->where('a.kategori', 'beban')
            ->where('a.kode_akun', 'like', '6%')
            ->selectRaw('COALESCE(SUM(jd.debit),0) - COALESCE(SUM(jd.kredit),0) as net')
            ->value('net') ?? 0;

        $labaKotor  = (float) $pendapatan - (float) $hpp;
        $labaBersih = (float) $labaKotor - (float) $bebanOperasional;

        $this->summary = [
            'pendapatan'        => (float) $pendapatan,
            'hpp'               => (float) $hpp,
            'laba_kotor'        => (float) $labaKotor,
            'beban_operasional' => (float) $bebanOperasional,
            'laba_bersih'       => (float) $labaBersih,
        ];
    }

    public function rupiah($angka): string
    {
        return 'Rp ' . number_format((float) $angka, 0, ',', '.');
    }
}
