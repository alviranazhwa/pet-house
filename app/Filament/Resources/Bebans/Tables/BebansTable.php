<?php

namespace App\Filament\Resources\Bebans\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class BebansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal', 'desc')
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('nomor_jurnal')
                    ->label('No. Jurnal')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('referensi_transaksi')
                    ->label('Ref')
                    ->searchable()
                    ->alignCenter(),

                TextColumn::make('akun_beban')
                    ->label('Akun Beban')
                    ->state(function ($record) {
                        $line = $record->detail
                            ->first(fn ($d) => str_starts_with((string)($d->akun?->kode_akun ?? ''), '6'));

                        if (!$line) return '-';

                        $kode = $line->akun?->kode_akun ?? '-';
                        $nama = $line->akun?->nama_akun ?? '-';
                        return "{$kode} — {$nama}";
                    })
                    ->wrap(),

                TextColumn::make('nominal')
                    ->label('Nominal')
                    ->alignEnd()
                    ->state(function ($record) {
                        $line = $record->detail
                            ->first(fn ($d) => str_starts_with((string)($d->akun?->kode_akun ?? ''), '6'));

                        return (float) ($line?->debit ?? 0);
                    })
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.')),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->striped()
            ->paginated([25, 50, 100]);
    }
}
