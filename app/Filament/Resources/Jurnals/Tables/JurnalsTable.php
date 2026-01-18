<?php

namespace App\Filament\Resources\Jurnals\Tables;

use App\Filament\Resources\Jurnals\JurnalResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;

class JurnalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // ✅ klik baris = langsung buka halaman VIEW
            ->recordUrl(fn ($record) => JurnalResource::getUrl('view', ['record' => $record]))

            ->columns([
                Tables\Columns\TextColumn::make('nomor_jurnal')
                    ->label('No. Jurnal')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('referensi_transaksi')
                    ->label('Referensi')
                    ->searchable(),

                // ✅ kalau jurnal punya kolom total_debit / total_kredit
                // Kalau belum ada: biarin dulu tampil 0.00 (atau hapus 2 kolom ini)
                Tables\Columns\TextColumn::make('total_debit')
                    ->label('Debit')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->default('0.00'),

                Tables\Columns\TextColumn::make('total_kredit')
                    ->label('Kredit')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->default('0.00'),
            ])
            ->recordActions([
                // ✅ v4: pakai Filament\Actions\ViewAction
                ViewAction::make()->label('Lihat'),
            ])
            ->toolbarActions([
                // ✅ v4: pakai Filament\Actions\CreateAction
                CreateAction::make(),
            ]);
    }
}
