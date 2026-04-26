<?php

namespace App\Filament\Pages;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class BukuBesar extends Page implements HasForms
{
    use InteractsWithForms;

    // ✅ Filament v4 friendly
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';
    protected static string|\UnitEnum|null $navigationGroup = 'Laporan Keuangan';
    protected static ?string $navigationLabel = 'Buku Besar';
    protected static ?string $title = 'Buku Besar';
    protected static ?string $slug = 'buku-besar';

    // ✅ FIX UTAMA: di Filament v4, $view itu NON-STATIC
    protected string $view = 'filament.pages.buku-besar';

    /**
     * Form state (best practice Filament)
     */
    public array $data = [];

    /**
     * Output ke view
     */
    public array $ledgers = [];
    public string $periodeLabel = '';

    public function mount(): void
    {
        $this->data = [
            'from' => Carbon::now()->startOfMonth()->toDateString(),
            'until' => Carbon::now()->endOfMonth()->toDateString(),
            'akun_ids' => [],
            'sumber_transaksi' => null,
        ];

        // isi nilai default ke form
        $this->form->fill($this->data);

        $this->buildLedgers();
    }

    /**
     * ✅ NOTE:
     * Kita sengaja TANPA type-hint Form di signature
     * biar Intelephense gak “Undefined type”.
     * Runtime Filament tetap aman.
     */
    public function form($form)
    {
        return $form
            ->schema([
                DatePicker::make('from')
                    ->label('Dari')
                    ->native(false)
                    ->closeOnDateSelection()
                    ->required(),

                DatePicker::make('until')
                    ->label('Sampai')
                    ->native(false)
                    ->closeOnDateSelection()
                    ->required(),

                Select::make('akun_ids')
                    ->label('Akun (Opsional)')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    // ->helperText('Kosongkan jika ingin tampilkan semua akun yang ada transaksi di periode.')
                    ->options(fn () => Akun::query()
                        ->where('is_aktif', true)
                        ->orderBy('kode_akun')
                        ->get()
                        ->mapWithKeys(fn ($a) => [$a->id => "{$a->kode_akun} — {$a->nama_akun}"])
                        ->toArray()
                    ),

                Select::make('sumber_transaksi')
                    ->label('Sumber Transaksi (Opsional)')
                    ->searchable()
                    ->preload()
                    ->options(fn () => $this->sumberOptions())
                    ->placeholder('Semua'),
            ])
            ->columns(4)
            ->statePath('data');
    }

    public function tampilkan(): void
    {
        $this->data = $this->form->getState();

        $this->buildLedgers();

        Notification::make()
            ->title('Buku Besar diperbarui')
            ->body('Data sudah disesuaikan dengan filter yang kamu pilih.')
            ->success()
            ->send();
    }

    public function downloadPdf()
    {
        Notification::make()
            ->title('PDF belum aktif')
            ->body('Install dompdf dulu ya. Nanti aku bantu nyalain tombol PDF-nya.')
            ->warning()
            ->send();

        return null;
    }

    protected function buildLedgers(): void
    {
        $from = Carbon::parse($this->data['from'])->startOfDay();
        $until = Carbon::parse($this->data['until'])->endOfDay();

        $this->periodeLabel = $from->format('d/m/Y') . ' – ' . $until->format('d/m/Y');

        $base = JurnalDetail::query()
            ->select('jurnal_detail.*')
            ->join('jurnal', 'jurnal.id', '=', 'jurnal_detail.jurnal_id')
            ->with(['akun', 'jurnal'])
            ->whereDate('jurnal.tanggal', '>=', $from->toDateString())
            ->whereDate('jurnal.tanggal', '<=', $until->toDateString());

        if (!empty($this->data['akun_ids'])) {
            $base->whereIn('jurnal_detail.akun_id', $this->data['akun_ids']);
        }

        if (!empty($this->data['sumber_transaksi'])) {
            $base->where('jurnal.sumber_transaksi', $this->data['sumber_transaksi']);
        }

        $details = $base
            ->orderBy('jurnal.tanggal', 'asc')
            ->orderBy('jurnal.nomor_jurnal', 'asc')
            ->orderBy('jurnal_detail.id', 'asc')
            ->get();

        if ($details->isEmpty()) {
            $this->ledgers = [];
            return;
        }

        $grouped = $details->groupBy('akun_id');
        $result = [];

        foreach ($grouped as $akunId => $rows) {
            $akun = $rows->first()->akun;

            $opening = $this->openingBalance((int) $akunId, $from, $this->data['sumber_transaksi']);

            $running = $opening['amount'];
            $runningSide = $opening['side'];
            $runningDisplayAmount = $opening['display_amount'];

            $ledgerRows = [];
            $totalDebit = 0.0;
            $totalKredit = 0.0;

            foreach ($rows as $d) {
                $debit = (float) $d->debit;
                $kredit = (float) $d->kredit;

                $totalDebit += $debit;
                $totalKredit += $kredit;

                if ($akun->posisi_saldo === 'debit') {
                    $running += ($debit - $kredit);
                } else {
                    $running += ($kredit - $debit);
                }

                $runningSide = $running >= 0
                    ? (($akun->posisi_saldo === 'debit') ? 'D' : 'C')
                    : (($akun->posisi_saldo === 'debit') ? 'C' : 'D');

                $runningDisplayAmount = abs($running);

                $ledgerRows[] = [
                    'tanggal' => optional($d->jurnal?->tanggal)->format('d/m/Y') ?? '-',
                    'nomor'   => $d->jurnal?->nomor_jurnal ?? '-',
                    'keterangan' => trim(($d->jurnal?->sumber_transaksi ?? '') . ' - ' . ($d->jurnal?->referensi_transaksi ?? '')),
                    'debit'   => $debit,
                    'kredit'  => $kredit,
                    'saldo_amount' => $runningDisplayAmount,
                    'saldo_side'   => $runningSide,
                ];
            }

            $result[] = [
                'akun' => [
                    'id' => $akun->id,
                    'kode_akun' => $akun->kode_akun,
                    'nama_akun' => $akun->nama_akun,
                    'posisi_saldo' => $akun->posisi_saldo,
                ],
                'opening' => [
                    'amount' => $opening['display_amount'],
                    'side' => $opening['side'],
                ],
                'rows' => $ledgerRows,
                'total_debit' => $totalDebit,
                'total_kredit' => $totalKredit,
                'ending' => [
                    'amount' => $runningDisplayAmount,
                    'side' => $runningSide,
                ],
            ];
        }

        usort($result, fn ($a, $b) => strcmp($a['akun']['kode_akun'], $b['akun']['kode_akun']));

        $this->ledgers = $result;
    }

    protected function openingBalance(int $akunId, Carbon $from, ?string $sumber = null): array
    {
        $akun = Akun::find($akunId);

        if (!$akun) {
            return ['amount' => 0.0, 'display_amount' => 0.0, 'side' => 'D'];
        }

        $q = JurnalDetail::query()
            ->join('jurnal', 'jurnal.id', '=', 'jurnal_detail.jurnal_id')
            ->where('jurnal_detail.akun_id', $akunId)
            ->whereDate('jurnal.tanggal', '<', $from->toDateString());

        if (!empty($sumber)) {
            $q->where('jurnal.sumber_transaksi', $sumber);
        }

        $sumDebit = (float) (clone $q)->sum('jurnal_detail.debit');
        $sumKredit = (float) (clone $q)->sum('jurnal_detail.kredit');

        $running = ($akun->posisi_saldo === 'debit')
            ? ($sumDebit - $sumKredit)
            : ($sumKredit - $sumDebit);

        $side = $running >= 0
            ? (($akun->posisi_saldo === 'debit') ? 'D' : 'C')
            : (($akun->posisi_saldo === 'debit') ? 'C' : 'D');

        return [
            'amount' => $running,
            'display_amount' => abs($running),
            'side' => $side,
        ];
    }

    protected function sumberOptions(): array
    {
        return Jurnal::query()
            ->select('sumber_transaksi')
            ->distinct()
            ->orderBy('sumber_transaksi')
            ->pluck('sumber_transaksi', 'sumber_transaksi')
            ->toArray();
    }
}
