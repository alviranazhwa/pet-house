<?php

namespace App\Filament\Resources\MutasiPersediaans\Schemas;

use App\Models\Produk;
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
                Select::make('produk_id')
                    ->label('Produk')
                    ->options(fn () => Produk::query()
                        ->where('is_aktif', true)
                        ->orderBy('nama_produk')
                        ->pluck('nama_produk', 'id')
                        ->toArray())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $produk = $state ? Produk::find($state) : null;

                        $set('kode_produk', $produk?->kode_produk);
                        $set('nama_produk', $produk?->nama_produk);
                        $set('satuan', $produk?->satuan ?? 'pcs');
                        $set('harga', $produk?->harga_beli ?? null);
                    }),

                TextInput::make('kode_produk')
                    ->label('Kode Produk')
                    ->required()
                    ->maxLength(255),

                TextInput::make('nama_produk')
                    ->label('Nama Produk')
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
