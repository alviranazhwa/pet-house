<?php

namespace App\Filament\Resources\Produks\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProdukForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kode_produk')
                    ->required(),
                TextInput::make('nama_produk')
                    ->required(),
                TextInput::make('kategori_produk_id')
                    ->required()
                    ->numeric(),
                TextInput::make('satuan')
                    ->required()
                    ->default('pcs'),
                TextInput::make('harga_beli')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('harga_jual')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Toggle::make('is_aktif')
                    ->required(),
                Textarea::make('keterangan')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
