<?php

namespace App\Filament\Resources\Bebans\Schemas;

use App\Models\Akun;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BebanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('tanggal')
                ->label('Tanggal')
                ->required()
                ->default(now())
                ->native(false),

            Select::make('akun_beban_id')
                ->label('Akun Beban (6xxx)')
                ->required()
                ->searchable()
                ->preload()
                ->options(function () {
                    return Akun::query()
                        ->where('is_aktif', true)
                        ->where('kategori', 'beban')
                        ->where('kode_akun', 'like', '6%')
                        ->orderBy('kode_akun')
                        ->get()
                        ->mapWithKeys(fn ($a) => [
                            $a->id => "{$a->kode_akun} — {$a->nama_akun}",
                        ])
                        ->toArray();
                }),

            Select::make('mode_pembayaran')
                ->label('Bayar Dari')
                ->required()
                ->options([
                    'kas'  => 'Kas (1001)',
                    'bank' => 'Bank (1002)',
                ])
                ->default('kas'),

            TextInput::make('nominal')
                ->label('Nominal')
                ->required()
                ->numeric()
                ->minValue(1)
                ->prefix('Rp')
                ->rule('gt:0'),

            Textarea::make('keterangan')
                ->label('Keterangan')
                ->rows(3)
                ->maxLength(255)
                ->placeholder('Contoh: Bayar listrik & air Januari'),
        ]);
    }
}
