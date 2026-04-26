<?php

namespace App\Filament\Pages;

use App\Models\Produk;
use App\Services\StockAverageService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class KartuStok extends Page
{
    protected static ?string $navigationLabel = 'Kartu Stok';
    protected static ?string $title = 'Kartu Stok';
    protected static ?string $slug = 'kartu-stok';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube-transparent';
    protected static string|\UnitEnum|null $navigationGroup = 'Laporan Keuangan';

    public function getView(): string
    {
        return 'filament.pages.kartu-stok';
    }

    public ?int $produk_id = null;
    public string $from = '';
    public string $until = '';

    public array $rows = [];
    public array $summary = [];
    public array $produkOptions = [];

    public function mount(): void
    {
        $this->from = Carbon::now()->startOfMonth()->toDateString();
        $this->until = Carbon::now()->endOfMonth()->toDateString();

        $this->produkOptions = Produk::orderBy('nama_produk')->pluck('nama_produk', 'id')->toArray();
        $this->produk_id = array_key_first($this->produkOptions);

        $this->apply();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action('apply'),
        ];
    }

    public function apply(): void
    {
        if (!$this->produk_id) return;

        $result = app(StockAverageService::class)->build(
            $this->produk_id,
            $this->from,
            $this->until
        );

        $this->rows = $result['rows'];
        $this->summary = $result['summary'];
    }
}
