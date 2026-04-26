<?php

namespace App\Filament\Resources\Jurnals\Pages;

use App\Filament\Resources\Jurnals\JurnalResource;
use App\Models\JurnalDetail;
use BackedEnum;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Grouping\Group;

class JurnalUmum extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = JurnalResource::class;

    protected static ?string $navigationLabel = 'Jurnal Umum';

    // ✅ v4 type
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    // ✅ PENTING: NON-static (biar gak bentrok sama Filament\Pages\Page::$view)
    protected string $view = 'filament.resources.jurnals.pages.jurnal-umum';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JurnalDetail::query()->with(['akun', 'jurnal'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('akun_line')
                    ->label('Akun (COA)')
                    ->state(fn ($r) => $r->akun->kode_akun . ' - ' . $r->akun->nama_akun)
                    ->searchable(),

                Tables\Columns\TextColumn::make('debit')
                    ->label('Debit')
                    ->state(fn ($r) => ((float) $r->debit) > 0 ? $r->debit : '')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('kredit')
                    ->label('Kredit')
                    ->state(fn ($r) => ((float) $r->kredit) > 0 ? $r->kredit : '')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd(),
            ])
            ->groups([
                Group::make('jurnal.nomor_jurnal')
                    ->label('No. Jurnal')
                    ->collapsible()
                    ->getTitleFromRecordUsing(fn ($r) =>
                        $r->jurnal->nomor_jurnal
                        . ' • ' . $r->jurnal->tanggal->format('d M Y')
                        . ' • ' . $r->jurnal->referensi_transaksi
                    ),
            ])
            ->defaultGroup('jurnal.nomor_jurnal')
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50]);
    }
}
