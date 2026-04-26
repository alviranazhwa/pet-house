<?php

namespace App\Filament\Pages;

use App\Models\Akun;
use App\Models\JurnalDetail;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class JurnalUmum extends Page
{
    protected static ?string $navigationLabel = 'Jurnal Umum';
    protected static ?string $title = 'Jurnal Umum';
    protected static ?string $slug = 'jurnal-umum';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string|\UnitEnum|null $navigationGroup = 'Laporan Keuangan';
    protected static ?int $navigationSort = 20;

    // FILTER STATE (tanpa Form class)
    public ?string $from = null;
    public ?string $until = null;
    public ?int $akun_id = null;

    // Search sederhana (mirip search table)
    public string $q = '';

    // OUTPUT
    public array $rows = [];
    public array $akunOptions = [];
    public string $periodeLabel = '';

    // TOTAL
    public float $totalDebit = 0;
    public float $totalKredit = 0;

    public function getView(): string
    {
        return 'filament.pages.jurnal-umum';
    }

    public function mount(): void
    {
        $this->from  = Carbon::now()->startOfMonth()->toDateString();
        $this->until = Carbon::now()->endOfMonth()->toDateString();

        $this->akunOptions = Akun::query()
            ->where('is_aktif', true)
            ->orderBy('kode_akun')
            ->get()
            ->mapWithKeys(fn ($a) => [$a->id => "{$a->kode_akun} — {$a->nama_akun}"])
            ->toArray();

        $this->apply();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->color('success')
                ->icon('heroicon-o-arrow-path')
                ->action('apply'),
        ];
    }

    public function apply(): void
    {
        $from  = $this->from ? Carbon::parse($this->from)->startOfDay() : Carbon::now()->startOfMonth();
        $until = $this->until ? Carbon::parse($this->until)->endOfDay() : Carbon::now()->endOfMonth();

        $this->periodeLabel = $from->format('d/m/Y') . ' — ' . $until->format('d/m/Y');

        $query = JurnalDetail::query()
            ->select('jurnal_detail.*')
            ->join('jurnal', 'jurnal.id', '=', 'jurnal_detail.jurnal_id')
            ->with(['jurnal', 'akun'])
            ->whereDate('jurnal.tanggal', '>=', $from->toDateString())
            ->whereDate('jurnal.tanggal', '<=', $until->toDateString());

        // Filter akun (optional)
        if (!empty($this->akun_id)) {
            $query->where('jurnal_detail.akun_id', (int) $this->akun_id);
        }

        // Search (ke nomor_jurnal, referensi_transaksi, kode akun, nama akun)
        $search = trim($this->q);
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('jurnal.nomor_jurnal', 'like', "%{$search}%")
                    ->orWhere('jurnal.referensi_transaksi', 'like', "%{$search}%")
                    ->orWhereHas('akun', function (Builder $aq) use ($search) {
                        $aq->where('kode_akun', 'like', "%{$search}%")
                            ->orWhere('nama_akun', 'like', "%{$search}%");
                    });
            });
        }

        // Urut dari awal bulan → akhir bulan (ASC)
        $details = $query
            ->orderBy('jurnal.tanggal', 'asc')
            ->orderBy('jurnal.nomor_jurnal', 'asc')
            ->orderBy('jurnal_detail.id', 'asc')
            ->get();

        $rows = [];
        $totalDebit = 0;
        $totalKredit = 0;

        foreach ($details as $d) {
            $debit  = (float) $d->debit;
            $kredit = (float) $d->kredit;

            $totalDebit  += $debit;
            $totalKredit += $kredit;

            $kodeAkun = $d->akun?->kode_akun ?? '-';
            $namaAkun = $d->akun?->nama_akun ?? '-';

            $rows[] = [
                'tanggal' => $d->jurnal?->tanggal ? Carbon::parse($d->jurnal->tanggal)->format('d/m/Y') : '-',
                'nomor'   => $d->jurnal?->nomor_jurnal ?? '-',
                'ref'     => $d->jurnal?->referensi_transaksi ?? '-',
                'akun'    => "{$kodeAkun} — {$namaAkun}",
                'debit'   => $debit,
                'kredit'  => $kredit,
            ];
        }

        $this->rows = $rows;
        $this->totalDebit = $totalDebit;
        $this->totalKredit = $totalKredit;
    }

    public function resetFilter(): void
    {
        $this->akun_id = null;
        $this->q = '';
        $this->apply();
    }
}
