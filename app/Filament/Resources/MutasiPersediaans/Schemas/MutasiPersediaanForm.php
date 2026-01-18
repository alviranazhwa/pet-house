<?php

namespace App\Filament\Resources\MutasiPersediaans\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MutasiPersediaanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kode_barang')
                    ->label('Kode Barang')
                    ->required()
                    ->maxLength(255),

                TextInput::make('nama_barang')
                    ->label('Nama Barang')
                    ->required()
                    ->maxLength(255),

                TextInput::make('satuan')
                    ->label('Satuan')
                    ->required()
                    ->maxLength(255),

                TextInput::make('qty')
                    ->label('Qty')
                    ->required()
                    ->numeric(),

                Select::make('tipe')
                    ->label('Tipe Mutasi')
                    ->options([
                        'MASUK' => 'Masuk',
                        'KELUAR' => 'Keluar',
                        'PENYESUAIAN' => 'Penyesuaian',
                    ])
                    ->required(),

                // Referensi transaksi asal (opsional)
                TextInput::make('ref_tipe')
                    ->label('Ref Tipe')
                    ->placeholder('penjualan / pembelian / adjustment')
                    ->default(null)
                    ->maxLength(255),

                TextInput::make('ref_id')
                    ->label('Ref ID')
                    ->numeric()
                    ->default(null),

                TextInput::make('harga')
                    ->label('Harga')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(null),

                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required(),

                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->default(null)
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
