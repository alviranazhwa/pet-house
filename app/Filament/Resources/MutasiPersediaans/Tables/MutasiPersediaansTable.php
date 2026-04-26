<?php

namespace App\Filament\Resources\MutasiPersediaans\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class MutasiPersediaansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal', 'desc')
            ->columns([
                // ✅ FIX: DB field = kode_produk
                TextColumn::make('kode_produk')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),

                // ✅ FIX: DB field = nama_produk
                TextColumn::make('nama_produk')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->alignStart(),

                TextColumn::make('satuan')
                    ->label('Satuan')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->colors([
                        'success' => 'MASUK',
                        'danger'  => 'KELUAR',
                        'warning' => 'PENYESUAIAN',
                    ])
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('qty')
                    ->label('Qty')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('harga')
                    ->label('Harga')
                    ->money('IDR', locale: 'id_ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignEnd(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('ref_tipe')
                    ->label('Ref Tipe')
                    ->formatStateUsing(fn ($state) => $state ?: '-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),

                TextColumn::make('ref_id')
                    ->label('Ref ID')
                    ->numeric()
                    ->formatStateUsing(fn ($state) => $state ?: '-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(60)
                    ->wrap()
                    ->formatStateUsing(fn ($state) => $state ?: '-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignStart(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),

                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('tipe')
                    ->label('Filter Tipe')
                    ->options([
                        'MASUK'       => 'MASUK (Barang masuk)',
                        'KELUAR'      => 'KELUAR (Barang keluar)',
                        'PENYESUAIAN' => 'PENYESUAIAN (Adjustment)',
                    ]),
            ])
            // READ-ONLY: tanpa action apa pun
            ->recordActions([]);
    }
}
