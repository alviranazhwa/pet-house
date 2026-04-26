<?php

namespace App\Filament\Resources\Pembelians\Schemas;

use App\Models\Produk;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PembelianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('kode_pembelian')
                    ->label('Kode Pembelian')
                    ->disabled()
                    ->dehydrated()
                    ->placeholder('AUTO'),

                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required()
                    ->native(false),

                Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'nama_supplier')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                TextInput::make('no_invoice')
                    ->label('No Invoice / Nota')
                    ->maxLength(255)
                    ->nullable(),

                // ✅ mode pembayaran: untuk jurnal (kas/bank)
                Select::make('mode_pembayaran')
                    ->label('Mode Pembayaran')
                    ->options([
                        'kas'  => 'Kas',
                        'bank' => 'Bank',
                    ])
                    ->default('kas')
                    ->required()
                    ->dehydrated(false), // ✅ jangan masuk $data create/update

                FileUpload::make('bukti_url')
                    ->label('Bukti Pembelian')
                    ->directory('bukti/pembelian')
                    ->disk('public')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->maxSize(4096)
                    ->openable()
                    ->downloadable()
                    ->nullable()
                    ->columnSpanFull(),

                Hidden::make('user_id')
                    ->default(fn () => Auth::id())
                    ->dehydrated(),

                Repeater::make('items')
                    ->label('Item Pembelian')
                    ->minItems(1)
                    ->defaultItems(1)
                    ->columnSpanFull()
                    ->columns(4)
                    ->live()
                    ->schema([
                        // ✅ penting untuk edit-resync
                        Hidden::make('detail_id')->dehydrated(),
                        Hidden::make('mutasi_persediaan_id')->dehydrated(),
                        Hidden::make('nama_item')->dehydrated(),

                        Select::make('produk_id')
                            ->label('Nama Barang')
                            ->options(fn () => Produk::query()
                                ->where('is_aktif', true)
                                ->orderBy('nama_produk')
                                ->pluck('nama_produk', 'id')
                                ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if (! $state) {
                                    $set('nama_item', null);
                                    $set('harga', 0);
                                    $set('subtotal', 0);
                                    return;
                                }

                                $p = Produk::find($state);
                                if (! $p) return;

                                // ✅ snapshot nama untuk histori
                                $set('nama_item', $p->nama_produk);

                                $hargaSekarang = (float) ($get('harga') ?? 0);
                                if ($hargaSekarang <= 0) {
                                    $set('harga', (float) ($p->harga_beli ?? 0));
                                }

                                $qty = (int) ($get('qty') ?? 0);
                                if ($qty <= 0) {
                                    $set('qty', 1);
                                }

                                $qtyFix   = (int) ($get('qty') ?? 1);
                                $hargaFix = (float) ($get('harga') ?? 0);
                                $set('subtotal', $qtyFix * $hargaFix);
                            }),

                        TextInput::make('qty')
                            ->label('Qty')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $qty   = (int) ($state ?? 0);
                                $harga = (float) ($get('harga') ?? 0);
                                $set('subtotal', $qty * $harga);
                            }),

                        TextInput::make('harga')
                            ->label('Harga Beli')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $qty   = (int) ($get('qty') ?? 0);
                                $harga = (float) ($state ?? 0);
                                $set('subtotal', $qty * $harga);
                            }),

                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->disabled()
                            ->dehydrated()
                            ->numeric()
                            ->prefix('Rp'),
                    ])
                    ->afterStateUpdated(function ($state, callable $set) {
                        $total = 0;
                        foreach (($state ?? []) as $row) {
                            $total += (float) ($row['subtotal'] ?? 0);
                        }
                        $set('total', $total);
                    }),

                TextInput::make('total')
                    ->label('Total')
                    ->disabled()
                    ->dehydrated()
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->columnSpanFull(),

                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }
}
